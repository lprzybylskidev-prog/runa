import { computed, getCurrentInstance, onScopeDispose, ref, watch } from 'vue';
import { authFailure, authGet, authPost, data } from './entry';
import { useLocale } from '../../../resources/js/composables/useLocale';
import {
  usePasswordConfirmation,
  type PasswordConfirmation,
} from './passwordConfirmation';
import { operationProofCopy } from './operationProofCopy';

export type ProofRequirement = 'none' | 'password';
/** Uses the caller's admitted session identity; never owns a second session or retries a business write. */
export function useOperationProof(options: {
  identity: () => string | undefined;
  action: () => string;
  requirement: () => ProofRequirement;
  locale?: () => 'pl' | 'en';
  confirmation?: PasswordConfirmation | undefined;
}) {
  const { locale } = getCurrentInstance()
    ? useLocale()
    : { locale: ref<'pl' | 'en'>('pl') };
  const copy = computed(() =>
    operationProofCopy(options.locale?.() ?? locale.value),
  );
  const host =
    options.confirmation ??
    (getCurrentInstance() ? usePasswordConfirmation() : undefined);
  const password = ref('');
  const open = ref(false),
    busy = ref(false),
    confirmed = ref(false);
  const errorKey = ref<
    | 'failed'
    | 'fields'
    | 'invalidProof'
    | 'throttled'
    | 'csrf'
    | 'signedOut'
    | ''
  >('');
  const error = computed(() =>
    errorKey.value ? copy.value[errorKey.value] : '',
  );
  let epoch = 0,
    alive = true,
    abort = new AbortController();
  const identity = () =>
    JSON.stringify([
      options.identity(),
      options.action(),
      options.requirement(),
    ]);
  function reset(): void {
    host?.cancel(controller);
    ++epoch;
    abort.abort();
    abort = new AbortController();
    password.value = '';
    open.value = false;
    busy.value = false;
    confirmed.value = false;
    errorKey.value = '';
  }
  async function perform(action: (signal: AbortSignal) => Promise<void>) {
    if (!alive || busy.value || !options.identity()) return;
    const own = ++epoch,
      stamp = identity();
    busy.value = true;
    errorKey.value = '';
    try {
      await action(abort.signal);
    } catch (cause) {
      if (alive && own === epoch && stamp === identity()) {
        const result = authFailure(cause);
        if (result.code === 'csrf') {
          try {
            await authGet('csrf', abort.signal);
          } catch {
            if (alive && own === epoch && stamp === identity())
              errorKey.value = 'failed';
            return;
          }
          if (!alive || own !== epoch || stamp !== identity()) return;
        }
        errorKey.value =
          result.code === 'security_proof_invalid'
            ? 'invalidProof'
            : result.code === 'throttled'
              ? 'throttled'
              : result.code === 'csrf'
                ? 'csrf'
                : result.code === 'signed_out'
                  ? 'signedOut'
                  : 'failed';
      }
    } finally {
      if (own === epoch) {
        password.value = '';
        busy.value = false;
      }
    }
  }
  async function begin() {
    reset();
    open.value = !!options.identity();
  }

  async function confirm() {
    const stamp = identity();
    if (!password.value) {
      errorKey.value = 'fields';
      return;
    }
    await perform(async (signal) => {
      const result = data(
        await authPost(
          'step-up',
          {
            action: options.action(),
            current_password: password.value,
          },
          signal,
        ),
      );
      if (!alive || stamp !== identity() || signal.aborted) return;
      if (result.confirmed !== true)
        throw new TypeError('Proof not confirmed.');
      confirmed.value = true;
      open.value = false;
    });
  }
  watch(identity, reset, { flush: 'sync' });
  onScopeDispose(() => {
    alive = false;
    reset();
  });
  const controller = {
    password,
    open,
    busy,
    error,
    confirmed,
    requirement: computed(options.requirement),
    begin,
    confirm,
    reset,
    request: (): Promise<boolean> =>
      host ? host.request(controller) : Promise.resolve(false),
  };
  return controller;
}
export type OperationProofController = ReturnType<typeof useOperationProof>;
