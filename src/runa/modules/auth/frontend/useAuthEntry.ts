import {
  computed,
  nextTick,
  onMounted,
  onScopeDispose,
  reactive,
  ref,
  shallowRef,
} from 'vue';
import { useLocale } from '../../../resources/js/composables/useLocale';
import {
  authFailure,
  authGet,
  authPost,
  data,
  decodeOptions,
  decodeSecurity,
  decodeSession,
  factorMethods,
  safeReturnPath,
  takeEntryToken,
} from './entry';
import type {
  EntryOptions,
  EntryPage,
  EntrySession,
  FactorMethod,
  SecurityOptions,
} from './entry';
import { entryMessages } from './messages';
import type { EntryCopyKey } from './messages';

export function useAuthEntry(page: EntryPage) {
  const { locale } = useLocale();
  const t = (key: EntryCopyKey) => entryMessages[locale.value][key];
  const options = shallowRef<EntryOptions>();
  const session = shallowRef<EntrySession>();
  const security = shallowRef<SecurityOptions>();
  const busy = ref(false),
    ready = ref(false),
    failure = ref(''),
    notice = ref<EntryCopyKey>();
  const complete = ref(false),
    enrollmentSecret = ref(''),
    recoveryCodes = ref<string[]>([]);
  const token = ref(takeEntryToken());
  const returnPath = safeReturnPath(
    new URLSearchParams(window.location.search).get('return'),
  );
  const values = reactive<Record<string, string>>({
    identifier: '',
    password: '',
    password_confirmation: '',
    current_password: '',
    email: '',
    name: '',
    first_name: '',
    surname: '',
    login: '',
    code: '',
    enrollment_code: '',
  });
  const errors = reactive<Record<string, EntryCopyKey>>({});
  const naming = ref('single'),
    method = ref<FactorMethod>('totp'),
    remember = ref(false);
  const form = ref<{ focusInvalid: () => void }>();
  const heading = ref<HTMLElement>();
  const abort = new AbortController();
  let alive = true;
  const isTokenPage = [
    'invitation',
    'reset-password',
    'verify-email',
    'confirm-address',
  ].includes(page);
  const enrollmentRequired = computed(
    () =>
      session.value?.restrictions.includes('two_factor_enrollment_required') ||
      (session.value?.restrictions.includes('password_change_required') &&
        security.value?.password_change_factor &&
        factorMethods(security.value).length === 0),
  );
  const screen = computed(() => {
    if (recoveryCodes.value.length) return 'recoveryCodes';
    if (complete.value) return 'complete';
    if (
      page === 'register' &&
      options.value &&
      !options.value.public_registration
    )
      return 'disabled';
    if (isTokenPage && !token.value) return 'missingToken';
    if (session.value && (page === 'login' || page === 'security')) {
      const restrictions = session.value.restrictions;
      if (restrictions.includes('email_verification_required')) return 'verify';
      if (enrollmentRequired.value)
        return !security.value?.email_verified &&
          (method.value === 'email' || security.value?.required === 'email')
          ? 'verify'
          : 'enrollment';
      if (restrictions.includes('two_factor_required')) return 'challenge';
      if (restrictions.includes('password_change_required'))
        return 'passwordChange';
      return 'signedIn';
    }
    return (
      (
        {
          'forgot-password': 'forgot',
          'reset-password': 'reset',
          'verify-email': 'verify',
          'confirm-address': 'address',
          security: 'login',
        } as Partial<Record<EntryPage, string>>
      )[page] ?? page
    );
  });
  const title = computed((): string =>
    t(
      (
        {
          disabled: 'register',
          missingToken:
            page === 'reset-password'
              ? 'reset'
              : page === 'invitation'
                ? 'invitation'
                : 'verify',
          complete: 'completed',
          signedIn: 'signedIn',
          recoveryCodes: 'recoveryTitle',
        } as Record<string, EntryCopyKey>
      )[screen.value] ?? (screen.value as EntryCopyKey),
    ),
  );
  const description = computed(() => {
    const key = `${screen.value}Help`;
    return key in entryMessages.pl ? t(key as EntryCopyKey) : '';
  });
  const methods = computed(() =>
    security.value
      ? factorMethods(security.value, !!enrollmentRequired.value)
      : [],
  );
  const proofMethods = computed(() =>
    security.value ? factorMethods(security.value) : [],
  );
  const newPassword = computed(() =>
    ['register', 'invitation', 'reset', 'passwordChange'].includes(
      screen.value,
    ),
  );
  const currentPassword = computed(() =>
    ['enrollment', 'passwordChange'].includes(screen.value),
  );
  const needsProof = computed(
    () =>
      screen.value === 'challenge' ||
      (screen.value === 'passwordChange' &&
        security.value?.password_change_factor) ||
      (screen.value === 'enrollment' && proofMethods.value.length > 0),
  );
  const fieldNames = computed(() => [
    ...(screen.value === 'register'
      ? [
          'email',
          ...(options.value?.identifier_mode !== 'email' ? ['login'] : []),
          ...(naming.value === 'single' ? ['name'] : ['first_name', 'surname']),
        ]
      : []),
    ...(['login', 'forgot'].includes(screen.value) ? ['identifier'] : []),
    ...(currentPassword.value ? ['current_password'] : []),
    ...(screen.value === 'login' || newPassword.value ? ['password'] : []),
    ...(newPassword.value ? ['password_confirmation'] : []),
    ...(needsProof.value ? ['code'] : []),
    ...(screen.value === 'enrollment' && enrollmentSecret.value
      ? ['enrollment_code']
      : []),
  ]);
  const passwordHint = computed(() =>
    options.value
      ? [
          t('passwordHint').replace(
            '{min}',
            String(options.value.password.min_length),
          ),
          ...(options.value.password.mixed_case ? [t('mixedHint')] : []),
          ...(options.value.password.numbers ? [t('numbersHint')] : []),
          ...(options.value.password.symbols ? [t('symbolsHint')] : []),
        ].join(' ')
      : '',
  );
  const hasSubmit = computed(
    () =>
      [
        'login',
        'register',
        'forgot',
        'reset',
        'invitation',
        'address',
        'challenge',
        'passwordChange',
        'enrollment',
      ].includes(screen.value) ||
      (screen.value === 'verify' && isTokenPage),
  );
  function clearSecrets() {
    for (const key of [
      'password',
      'password_confirmation',
      'current_password',
      'code',
      'enrollment_code',
    ])
      values[key] = '';
  }
  function clearErrors() {
    Object.keys(errors).forEach((key) => delete errors[key]);
    failure.value = '';
  }
  function label(name: string): string {
    if (name === 'identifier')
      return t(
        options.value?.identifier_mode === 'email'
          ? 'email'
          : options.value?.identifier_mode === 'login'
            ? 'loginIdentifier'
            : 'identifier',
      );
    return t(
      (
        {
          first_name: 'firstName',
          current_password: 'currentPassword',
          password_confirmation: 'confirmation',
          enrollment_code: 'enrollmentCode',
          login:
            options.value?.identifier_mode === 'login'
              ? 'loginIdentifier'
              : 'optionalLogin',
          password: newPassword.value ? 'newPassword' : 'password',
        } as Record<string, EntryCopyKey>
      )[name] ?? (name as EntryCopyKey),
    );
  }
  function validate() {
    clearErrors();
    for (const key of fieldNames.value) {
      const value = values[key] ?? '';
      if (
        !value.trim() &&
        !(key === 'login' && options.value?.identifier_mode !== 'login')
      )
        errors[key] = 'required';
      if (
        key === 'email' &&
        value &&
        (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value) || value.length > 254)
      )
        errors[key] = 'invalidEmail';
      if (
        key === 'login' &&
        value &&
        !/^[a-zA-Z0-9][a-zA-Z0-9._-]{0,63}$/.test(value)
      )
        errors[key] = 'invalidLogin';
      if (
        ['name', 'first_name', 'surname'].includes(key) &&
        ([...value].length > 160 ||
          value.trim() !== value ||
          /\p{C}/u.test(value))
      )
        errors[key] = 'invalidField';
      if (key === 'identifier' && value.length > 254)
        errors[key] = 'invalidField';
    }
    if (newPassword.value && options.value) {
      const p = options.value.password,
        value = values.password ?? '';
      if (
        [...value].length < p.min_length ||
        new TextEncoder().encode(value).length > p.max_bytes ||
        value.includes('\0') ||
        (p.mixed_case && (!/\p{Ll}/u.test(value) || !/\p{Lu}/u.test(value))) ||
        (p.numbers && !/\p{N}/u.test(value)) ||
        (p.symbols && !/[\p{S}\p{P}]/u.test(value))
      )
        errors.password = 'passwordRules';
      if (value !== values.password_confirmation)
        errors.password_confirmation = 'passwordMismatch';
    }
    if (
      needsProof.value &&
      !(method.value === 'recovery' ? /^[0-9a-f]{32}$/ : /^[0-9]{6}$/).test(
        values.code ?? '',
      )
    )
      errors.code = 'invalidCode';
    if (
      enrollmentSecret.value &&
      !/^[0-9]{6}$/.test(values.enrollment_code ?? '')
    )
      errors.enrollment_code = 'invalidCode';
    return Object.keys(errors).length === 0;
  }
  async function focusHeading() {
    await nextTick();
    if (alive) heading.value?.focus();
  }
  async function readSession() {
    const result = decodeSession(await authGet('session', abort.signal));
    const factors = decodeSecurity(await authGet('security', abort.signal));
    if (!alive) return;
    session.value = result;
    security.value = factors;
    method.value = methods.value[0] ?? 'totp';
  }
  async function handleFailure(error: unknown) {
    if (!alive) return;
    const result = authFailure(error);
    failure.value = result.code;
    for (const key of result.fields)
      if (fieldNames.value.includes(key)) errors[key] = 'invalidField';
    if (result.code === 'signed_out') {
      session.value = undefined;
      security.value = undefined;
      enrollmentSecret.value = '';
      recoveryCodes.value = [];
    }
  }
  async function run(action: () => Promise<void>) {
    if (busy.value) return;
    busy.value = true;
    notice.value = undefined;
    clearErrors();
    try {
      await action();
    } catch (error: unknown) {
      await handleFailure(error);
    } finally {
      if (alive) {
        busy.value = false;
        clearSecrets();
        await nextTick();
        if (failure.value) form.value?.focusInvalid();
      }
    }
  }
  async function initialize() {
    await run(async () => {
      ready.value = false;
      await authGet('csrf', abort.signal);
      const result = decodeOptions(
        await authGet('entry-options', abort.signal),
      );
      if (!alive) return;
      options.value = result;
      if (page === 'login' || page === 'security') {
        try {
          await readSession();
        } catch (error: unknown) {
          if (authFailure(error).code !== 'signed_out') throw error;
        }
      }
      if (alive) {
        ready.value = true;
        await focusHeading();
      }
    });
  }
  async function submit() {
    if (!ready.value || busy.value || !hasSubmit.value) return;
    if (!validate()) {
      await nextTick();
      form.value?.focusInvalid();
      return;
    }
    const input: Record<string, string | boolean> = Object.fromEntries(
      fieldNames.value.map((key) => [key, values[key] ?? '']),
    );
    if (needsProof.value) input.method = method.value;
    const currentScreen = screen.value;
    await run(async () => {
      let path: string;
      if (currentScreen === 'login') {
        path = 'login';
        if (options.value?.remember_enabled) input.remember = remember.value;
      } else if (currentScreen === 'register') path = 'register';
      else if (currentScreen === 'forgot') path = 'password/forgot';
      else if (currentScreen === 'reset') path = 'password/reset';
      else if (currentScreen === 'invitation') path = 'invitations/accept';
      else if (currentScreen === 'verify') path = 'verification/complete';
      else if (currentScreen === 'address') path = 'email/complete';
      else if (currentScreen === 'challenge') path = 'security/challenge';
      else if (currentScreen === 'passwordChange') path = 'password/change';
      else
        path =
          method.value === 'email'
            ? 'security/email-enable'
            : enrollmentSecret.value
              ? 'security/totp-confirm'
              : 'security/totp-begin';
      if (isTokenPage) input.token = token.value;
      const response = data(await authPost(path, input, abort.signal));
      if (!alive) return;
      if (path === 'security/totp-begin') {
        if (
          typeof response.secret !== 'string' ||
          !/^[A-Z2-7]{16,128}$/.test(response.secret)
        )
          throw new TypeError('Invalid enrollment.');
        enrollmentSecret.value = response.secret;
      } else if (currentScreen === 'enrollment') {
        enrollmentSecret.value = '';
        if (
          !Array.isArray(response.recovery_codes) ||
          !response.recovery_codes.every(
            (code) => typeof code === 'string' && /^[0-9a-f]{32}$/.test(code),
          )
        )
          throw new TypeError('Invalid recovery codes.');
        recoveryCodes.value = response.recovery_codes as string[];
        if (!recoveryCodes.value.length) await readSession();
      } else if (
        ['login', 'challenge', 'passwordChange'].includes(currentScreen)
      ) {
        if (response.reauthenticate === true) {
          session.value = undefined;
          security.value = undefined;
          notice.value = 'completed';
        } else await readSession();
      } else {
        token.value = '';
        complete.value = true;
        notice.value = ['forgot', 'register'].includes(currentScreen)
          ? 'accepted'
          : ['verify', 'address'].includes(currentScreen)
            ? 'verificationCompleted'
            : 'completed';
      }
      await focusHeading();
    });
  }
  async function refresh() {
    await run(async () => {
      await readSession();
      await focusHeading();
    });
  }
  async function refreshCsrf() {
    await run(async () => {
      await authGet('csrf', abort.signal);
      if (alive) notice.value = 'csrfReady';
    });
  }
  async function sendEmail() {
    await run(async () => {
      await authPost(
        screen.value === 'verify'
          ? 'verification/resend'
          : 'security/email-send',
        {},
        abort.signal,
      );
      if (alive) notice.value = 'sent';
    });
  }
  async function logout() {
    await run(async () => {
      await authPost('logout', {}, abort.signal);
      if (alive) {
        session.value = undefined;
        security.value = undefined;
        enrollmentSecret.value = '';
        recoveryCodes.value = [];
        await focusHeading();
      }
    });
  }
  async function codesSaved() {
    recoveryCodes.value = [];
    await refresh();
  }
  function changeMethod(value: string) {
    method.value = value as FactorMethod;
    enrollmentSecret.value = '';
    clearSecrets();
    clearErrors();
  }
  const failureMessages: Record<string, EntryCopyKey> = Object.fromEntries(
    [
      'unavailable',
      'invalid_credentials',
      'security_proof_invalid',
      'password_reused',
      'validation_failed',
      'csrf',
      'throttled',
      'conflict',
      'signed_out',
      'account_restricted',
      'token_invalid',
      'registration_unavailable',
    ].map((key) => [key, key as EntryCopyKey]),
  );
  const errorMessage = computed(() =>
    t(
      Object.hasOwn(failureMessages, failure.value)
        ? failureMessages[failure.value]!
        : failure.value
          ? 'genericFailure'
          : 'unavailable',
    ),
  );
  onMounted(initialize);
  onScopeDispose(() => {
    alive = false;
    abort.abort();
    clearSecrets();
    token.value = '';
    enrollmentSecret.value = '';
    recoveryCodes.value = [];
  });
  return {
    t,
    options,
    session,
    security,
    busy,
    ready,
    failure,
    notice,
    complete,
    screen,
    title,
    description,
    values,
    errors,
    naming,
    method,
    remember,
    fieldNames,
    passwordHint,
    methods,
    enrollmentRequired,
    needsProof,
    newPassword,
    enrollmentSecret,
    recoveryCodes,
    token,
    isTokenPage,
    returnPath,
    hasSubmit,
    errorMessage,
    form,
    heading,
    label,
    submit,
    initialize,
    refresh,
    refreshCsrf,
    sendEmail,
    logout,
    codesSaved,
    changeMethod,
  };
}
