import { computed, onScopeDispose, ref, shallowRef, watch } from 'vue';
import {
  authGet,
  authPost,
  data,
  decodeSecurity,
  factorMethods,
} from './entry';
import type { FactorMethod } from './entry';
import { useLocale } from '../../../resources/js/composables/useLocale';
import { operationProofCopy } from './operationProofCopy';

export type ProofRequirement = 'none' | 'password' | 'second_factor' | 'both';
/** Uses the caller's admitted session identity; never owns a second session or retries a business write. */
export function useOperationProof(options: {
  identity: () => string | undefined;
  action: () => string;
  requirement: () => ProofRequirement;
  locale?: () => 'pl' | 'en';
}) {
  const { locale } = useLocale();
  const copy = computed(() =>
    operationProofCopy(options.locale?.() ?? locale.value),
  );
  const password = ref(''),
    code = ref(''),
    method = ref<FactorMethod>('totp');
  const methods = shallowRef<readonly FactorMethod[]>([]);
  const open = ref(false),
    busy = ref(false),
    confirmed = ref(false);
  const errorKey = ref<'failed' | 'noMethod' | 'fields' | ''>('');
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
  function reset() {
    ++epoch;
    abort.abort();
    abort = new AbortController();
    password.value = '';
    code.value = '';
    methods.value = [];
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
    } catch {
      if (alive && own === epoch && stamp === identity())
        errorKey.value = 'failed';
    } finally {
      if (own === epoch) {
        password.value = '';
        code.value = '';
        busy.value = false;
      }
    }
  }
  async function begin() {
    reset();
    open.value = true;
    const stamp = identity();
    await perform(async (signal) => {
      if (['second_factor', 'both'].includes(options.requirement())) {
        const security = decodeSecurity(await authGet('security', signal));
        if (!alive || stamp !== identity() || signal.aborted) return;
        methods.value = factorMethods(security);
        method.value = methods.value[0] ?? 'totp';
        if (!methods.value.length) errorKey.value = 'noMethod';
      }
    });
  }
  async function confirm() {
    const requirement = options.requirement(),
      stamp = identity();
    if (
      (['password', 'both'].includes(requirement) && !password.value) ||
      (['second_factor', 'both'].includes(requirement) &&
        (!code.value || !methods.value.includes(method.value)))
    ) {
      errorKey.value = 'fields';
      return;
    }
    await perform(async (signal) => {
      const result = data(
        await authPost(
          'step-up',
          {
            action: options.action(),
            requirement,
            ...(['password', 'both'].includes(requirement)
              ? { current_password: password.value }
              : {}),
            ...(['second_factor', 'both'].includes(requirement)
              ? { method: method.value, code: code.value }
              : {}),
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
  async function sendEmail() {
    if (method.value !== 'email' || !methods.value.includes('email')) return;
    await perform(async (signal) => {
      await authPost('security/email-send', {}, signal);
    });
  }
  watch(identity, reset, { flush: 'sync' });
  onScopeDispose(() => {
    alive = false;
    reset();
  });
  return {
    password,
    code,
    method,
    methods,
    open,
    busy,
    error,
    confirmed,
    requirement: computed(options.requirement),
    begin,
    confirm,
    sendEmail,
    reset,
  };
}
export type OperationProofController = ReturnType<typeof useOperationProof>;
