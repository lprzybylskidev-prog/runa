import { ref, onScopeDispose } from 'vue';
import type { Ref } from 'vue';
import {
  ApiError,
  RequestCancelledError,
} from '../../../../resources/js/lib/http';
import { accountPreferences } from '../preferences/session';
import { authPost, data, record } from '../entry';
import { accountRequest } from '../account';
import type { DeliveryChoice } from './types';
import type { AccountLifetime, AccountPerform, AccountText } from './context';

export function createAccountDelivery(
  lifetime: AccountLifetime,
  state: { confirmed: Ref<boolean>; failure: Ref<string>; notice: Ref<string> },
  perform: AccountPerform,
  text: AccountText,
) {
  const { confirmed, failure, notice } = state;
  const delivery = ref<DeliveryChoice[]>([]);
  let deliveryReadEpoch = 0;
  async function readDelivery() {
    await fetchDelivery();
  }
  async function fetchDelivery(signal = lifetime.signal) {
    const own = ++deliveryReadEpoch;
    const p = data(
      await authPost('delivery-preferences', { operation: 'read' }, signal),
    );
    if (
      p.account !== lifetime.accountId ||
      !Number.isSafeInteger(p.revision) ||
      !Array.isArray(p.categories)
    )
      throw new TypeError('Invalid delivery preferences.');
    const list = p.categories.map((item) => {
      const row = record(item),
        names = record(row.names);
      if (
        typeof row.module !== 'string' ||
        typeof row.key !== 'string' ||
        typeof names.pl !== 'string' ||
        typeof names.en !== 'string' ||
        ![row.mandatory, row.sensitive, row.enabled].every(
          (v) => typeof v === 'boolean',
        ) ||
        !Array.isArray(row.recipients) ||
        !row.recipients.every((v) => typeof v === 'string')
      )
        throw new TypeError('Invalid delivery category.');
      return row as unknown as DeliveryChoice;
    });
    if (
      lifetime.active &&
      own === deliveryReadEpoch &&
      accountPreferences.currentAccount() === lifetime.accountId
    )
      delivery.value = list;
    return { revision: Number(p.revision), categories: list };
  }
  async function saveDelivery(choice: DeliveryChoice) {
    const before = delivery.value.find(
      (item) => item.module === choice.module && item.key === choice.key,
    );
    if (!before) return;
    await perform(async () => {
      await accountPreferences.mutate(async (_revision, signal) => {
        // A shared snapshot can advance the revision before this row has reconciled.
        // Re-read and compare this row inside the same queue before using its token.
        const fresh = await fetchDelivery(
          AbortSignal.any([signal, lifetime.signal]),
        );
        if (!lifetime.active || signal.aborted)
          throw new RequestCancelledError();
        const current = fresh.categories.find(
          (item) => item.module === choice.module && item.key === choice.key,
        );
        const recipients = (item: DeliveryChoice) =>
          [...item.recipients].sort().join('\0');
        if (
          !current ||
          current.mandatory ||
          current.sensitive ||
          (recipients(choice) !== recipients(before) &&
            recipients(current) !== recipients(before) &&
            recipients(choice) !== recipients(current))
        )
          throw new ApiError(409, {});
        return accountRequest(
          'delivery-preferences',
          'POST',
          {
            operation: 'write',
            module: choice.module,
            category: choice.key,
            revision: fresh.revision,
            enabled:
              choice.enabled === before.enabled
                ? current.enabled
                : choice.enabled,
            recipients:
              recipients(choice) === recipients(before)
                ? current.recipients
                : choice.recipients,
          },
          AbortSignal.any([signal, lifetime.signal]),
        );
      });
      if (lifetime.active) {
        await readDelivery();
        notice.value = text(
          'Zapisano ustawienia powiadomień.',
          'Notification settings saved.',
        );
      }
    });
  }
  async function resetPreferences(includeDelivery = false) {
    if (!confirmed.value) {
      failure.value = 'confirmation';
      return;
    }
    await perform(async () => {
      if (includeDelivery) await accountPreferences.resetAll();
      else await accountPreferences.reset();
      if (lifetime.active) {
        confirmed.value = false;
        await readDelivery();
        notice.value = text(
          'Przywrócono domyślne preferencje.',
          'Default preferences restored.',
        );
      }
    });
  }
  onScopeDispose(() => {
    deliveryReadEpoch++;
  });
  return { delivery, readDelivery, saveDelivery, resetPreferences };
}
