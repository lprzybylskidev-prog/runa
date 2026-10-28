import { createAccountProfile } from './account/profile';
import { createAccountLists } from './account/lists';
import { createAccountDelivery } from './account/delivery';
import { createAccountValidation } from './account/validation';
import { enrollmentQrCode } from './totpEnrollment';
import { RequestCancelledError } from '../../../resources/js/lib/http';
import {
  computed,
  watch,
  nextTick,
  onMounted,
  onScopeDispose,
  reactive,
  ref,
  shallowRef,
} from 'vue';
import { useLocale } from '../../../resources/js/composables/useLocale';
import { accountPreferences } from './preferences/session';
import {
  authFailure,
  authGet,
  authPost,
  data,
  decodeOptions,
  decodeSecurity,
  factorMethods,
} from './entry';
import type { EntryOptions, SecurityOptions } from './entry';
import {
  accountRequest,
  rememberAccountDraft,
  takeAccountDraft,
  clearAccountDraft,
  accountDraftEpoch,
} from './account';
import type { AccountTab, AccountTask, AccountSection } from './account/types';
export type { AccountTab, AccountTask, AccountSection } from './account/types';
export function useAuthAccount(accountId: string) {
  const { locale } = useLocale();
  const text = (pl: string, en: string) => (locale.value === 'pl' ? pl : en);
  const security = shallowRef<SecurityOptions>(),
    options = shallowRef<EntryOptions>();
  const tab = ref<AccountTab>('details'),
    task = ref<AccountTask>('details');
  const busy = ref(false),
    ready = ref(false),
    failure = ref(''),
    notice = ref(''),
    confirmed = ref(false);
  const form = ref<{ focusInvalid: () => void }>(),
    heading = ref<HTMLElement>(),
    feedback = ref<HTMLElement>();
  const fields = reactive<Record<string, string>>({});
  const errors = reactive<Record<string, string>>({});
  const secondaryErrors = reactive<Record<string, string>>({});
  const naming = ref('single'),
    method = ref('totp'),
    target = ref('totp');
  const secret = ref(''),
    qrCode = ref(''),
    codes = ref<string[]>([]),
    files = ref<readonly File[]>([]);
  const overviewAction = ref<
    | 'none'
    | 'totpBegin'
    | 'totpConfirm'
    | 'emailEnable'
    | 'disable'
    | 'recoveryRotate'
    | 'trustIssue'
  >('none');
  const removeAvatar = ref(false);
  const sectionFields = reactive<
    Record<AccountSection, Record<string, string>>
  >({
    details: { current_password: '' },
    avatar: {},
    password: {
      current_password: '',
      password: '',
      password_confirmation: '',
      code: '',
    },
    security: { current_password: '', code: '', enrollment_code: '' },
  });
  const sectionErrors = reactive<
    Record<AccountSection, Record<string, string>>
  >({ details: {}, avatar: {}, password: {}, security: {} });
  const overviewNeedsProof = computed(
    () =>
      proofMethods.value.length > 0 &&
      (overviewAction.value !== 'none' ||
        (Boolean(fields.password || fields.password_confirmation) &&
          security.value?.password_change_factor === true)),
  );
  const canTrustDevice = computed(
    () =>
      security.value?.trusted_devices_enabled === true &&
      proofMethods.value.length > 0,
  );
  let refreshPromise: Promise<void> | undefined;
  let refreshAgain = false;
  let refreshingPreferenceSnapshot = false;
  const draft = shallowRef<Record<string, string> | null>(null);
  let draftEpoch = accountDraftEpoch();
  const abort = new AbortController();
  let alive = true;
  const lifetime = {
    accountId,
    signal: abort.signal,
    get active() {
      return alive;
    },
  };
  const {
    profile,
    detailConflicts,
    avatarConflict,
    loadProfile,
    resolveDetails,
    resolveAvatar,
  } = createAccountProfile(lifetime, {
    fields,
    naming,
    files,
    removeAvatar,
    busy,
    failure,
  });
  const {
    pageLoading,
    trustedLoading,
    rows,
    cursor,
    trustedRows,
    trustedCursor,
    readPage,
    readTrustedDevices,
    loadTrustedDevices,
    revokeTrustedDevice,
    historyDetail,
    historyDetailOpen,
    historyDetailLoading,
    historyDetailFailed,
    openHistoryDetail,
    closeHistoryDetail,
    retryHistoryDetail,
  } = createAccountLists(lifetime, { tab, security, notice }, perform, text);
  const { delivery, readDelivery, saveDelivery, resetPreferences } =
    createAccountDelivery(
      lifetime,
      { confirmed, failure, notice },
      perform,
      text,
    );
  const caps = computed(() => profile.value?.capabilities ?? {});
  const has = (key: string) => caps.value[key] === true;
  const canAvatar = computed(
    () =>
      has('avatar_enabled') &&
      ['both', 'user'].includes(String(caps.value.avatar_editor)),
  );
  const proofMethods = computed(() =>
    security.value ? factorMethods(security.value) : [],
  );
  const { needsProof, fieldNames, validate, emailError } =
    createAccountValidation({
      task,
      proofMethods,
      security,
      options,
      naming,
      method,
      fields,
      errors,
      has,
      cleanErrors,
      text,
    });
  const tabs = computed<AccountTab[]>(() => [
    'details',
    'security',
    ...(has('secondary_emails_enabled') ? ['addresses' as const] : []),
    ...(has('avatar_enabled') ? ['avatar' as const] : []),
    'sessions',
    ...(security.value?.trusted_devices_enabled ? ['trust' as const] : []),
    'preferences',
    ...(has('own_login_history') ? ['loginHistory' as const] : []),
    ...(has('own_account_history') ? ['accountHistory' as const] : []),
  ]);
  function cleanSecrets(clearFiles = true) {
    for (const key of [
      'current_password',
      'password',
      'password_confirmation',
      'code',
      'enrollment_code',
    ])
      fields[key] = '';
    if (clearFiles) files.value = [];
  }
  function cleanErrors() {
    Object.keys(errors).forEach((k) => delete errors[k]);
    failure.value = '';
    notice.value = '';
  }
  function fillDetails() {
    if (!profile.value) return;
    for (const key of ['name', 'first_name', 'surname', 'login'] as const)
      fields[key] = profile.value[key] ?? '';
    naming.value = profile.value.name === null ? 'full' : 'single';
  }
  function choose(
    next: AccountTask,
    address?: { public_id: string; email: string },
  ) {
    if (busy.value) return;
    cleanErrors();
    cleanSecrets();
    secret.value = '';
    codes.value = [];
    confirmed.value = false;
    task.value = next;
    overviewAction.value = 'none';
    removeAvatar.value = false;
    for (const values of Object.values(sectionFields))
      for (const key of Object.keys(values)) values[key] = '';
    fields.email =
      next === 'details'
        ? (profile.value?.email ?? '')
        : (address?.email ?? '');
    fields.address_id = address?.public_id ?? '';
    method.value = proofMethods.value[0] ?? 'totp';
    target.value = security.value?.enrolled[0] ?? 'totp';
    if (next === 'details') fillDetails();
  }
  async function perform(action: () => Promise<void>, clearSecrets = true) {
    return performTask(action, clearSecrets);
  }
  async function performTask(
    action: () => Promise<void>,
    clearSecrets = true,
    acceptedFields?: readonly string[],
  ) {
    if (busy.value || !alive) return;
    if (refreshPromise) {
      try {
        await refreshPromise;
      } catch {
        /* The operation owns subsequent failure feedback. */
      }
      if (busy.value || !alive) return;
    }
    busy.value = true;
    cleanErrors();
    try {
      await action();
      return alive;
    } catch (error) {
      if (!alive || error instanceof RequestCancelledError) return;
      const result = authFailure(error);
      failure.value = result.code;
      if (result.code === 'conflict') {
        if (['avatar', 'avatarRemove'].includes(task.value))
          avatarConflict.value = true;
        try {
          await refreshAccount(true);
        } catch {
          if (alive) failure.value = 'refresh_failed';
        }
        if (!alive) return;
      }
      result.fields
        .filter(
          (k) =>
            (acceptedFields ?? fieldNames.value).includes(k) ||
            (task.value === 'avatar' && k === 'avatar'),
        )
        .forEach(
          (k) => (errors[k] = text('Sprawdź wartość.', 'Check this value.')),
        );
    } finally {
      if (alive) {
        busy.value = false;
        if (clearSecrets) cleanSecrets();
        if (failure.value) {
          await nextTick();
          if (Object.keys(errors).length) form.value?.focusInvalid();
          else feedback.value?.focus();
        }
      }
    }
  }
  async function refreshAccount(duringOperation = false): Promise<void> {
    if (!alive || !ready.value || (busy.value && !duringOperation)) return;
    if (refreshPromise) return refreshPromise;
    refreshPromise = (async () => {
      await loadProfile();
      const next = decodeSecurity(await authGet('security', abort.signal));
      const entry = decodeOptions(await authGet('entry-options', abort.signal));
      if (!alive) return;
      security.value = next;
      options.value = entry;
      if (accountPreferences.currentAccount() === accountId) {
        refreshingPreferenceSnapshot = true;
        try {
          await accountPreferences.refresh();
        } finally {
          refreshingPreferenceSnapshot = false;
        }
      }
      if (!alive) return;
      if (tab.value === 'preferences') await readDelivery();
      if (
        ['sessions', 'trust', 'loginHistory', 'accountHistory'].includes(
          tab.value,
        )
      )
        await readPage();
      if (security.value?.trusted_devices_enabled) await readTrustedDevices();
      if (alive && failure.value === 'refresh_failed') failure.value = '';
    })();
    try {
      await refreshPromise;
    } finally {
      refreshPromise = undefined;
      if (refreshAgain && alive && !busy.value) refreshVisible();
    }
  }
  function refreshVisible() {
    if (document.visibilityState !== 'visible') return;
    if (refreshPromise || busy.value) {
      refreshAgain = true;
      return;
    }
    refreshAgain = false;
    void refreshAccount().catch((error) => {
      if (alive && !(error instanceof RequestCancelledError))
        failure.value = 'refresh_failed';
    });
  }
  async function initialize() {
    await perform(async () => {
      await loadProfile();
      const o = decodeOptions(await authGet('entry-options', abort.signal));
      const s = decodeSecurity(await authGet('security', abort.signal));
      if (!alive) return;
      options.value = o;
      security.value = s;
      overviewAction.value = 'none';
      removeAvatar.value = false;
      secret.value = '';
      ready.value = true;
      fillDetails();
      if (accountPreferences.currentAccount() !== accountId)
        await accountPreferences.connect(accountId).catch(() => {});
      fields.email = profile.value?.email ?? '';
      draft.value = takeAccountDraft(accountId);
      method.value = proofMethods.value[0] ?? 'totp';
      if (s.trusted_devices_enabled) {
        try {
          await readTrustedDevices();
        } catch (error) {
          if (alive && !(error instanceof RequestCancelledError))
            failure.value = 'refresh_failed';
        }
      }
    });
  }
  async function selectTab(value: AccountTab) {
    if (busy.value || !tabs.value.includes(value)) return;
    closeHistoryDetail();
    tab.value = value;
    rows.value = [];
    cursor.value = null;
    choose(
      value === 'security'
        ? 'password'
        : value === 'addresses'
          ? 'secondaryAdd'
          : value === 'avatar'
            ? 'avatar'
            : value === 'trust'
              ? 'trustIssue'
              : 'details',
    );
    if (
      [
        'preferences',
        'sessions',
        'trust',
        'loginHistory',
        'accountHistory',
      ].includes(value)
    ) {
      pageLoading.value = true;
      try {
        await perform(() => refreshAccount(true));
      } finally {
        pageLoading.value = false;
      }
    } else {
      await refreshAccount().catch(() => {
        if (alive) failure.value = 'refresh_failed';
      });
    }
    await nextTick();
    heading.value?.focus();
  }
  async function submitTask(preserveSecrets = false, quiet = false) {
    if (!ready.value || busy.value || codes.value.length) return;
    if (!validate()) {
      await nextTick();
      form.value?.focusInvalid();
      return;
    }
    if (
      ['secondaryRemove', 'avatarRemove', 'deactivate', 'delete'].includes(
        task.value,
      ) &&
      !confirmed.value
    ) {
      failure.value = 'confirmation';
      return;
    }
    if (task.value === 'avatar' && !files.value[0]) {
      failure.value = 'file_required';
      return;
    }
    const op = task.value;
    if (['avatar', 'avatarRemove'].includes(op) && avatarConflict.value) {
      failure.value = 'conflict';
      return false;
    }
    if (
      ['details', 'email'].includes(op) &&
      Object.keys(detailConflicts).length
    ) {
      failure.value = 'conflict';
      return false;
    }
    const input: Record<string, unknown> = Object.fromEntries(
      fieldNames.value.map((k) => [k, fields[k] ?? '']),
    );
    if (needsProof.value) input.method = method.value;
    let reauthenticating = false;
    const saved = await performTask(async () => {
      let path = '',
        verb: 'POST' | 'PATCH' | 'DELETE' = 'POST';
      let body: Record<string, unknown> | FormData = input;
      if (op === 'details') {
        path = 'profile';
        verb = 'PATCH';
        Object.assign(input, { version: profile.value!.version });
        for (const key of ['name', 'first_name', 'surname', 'login'])
          input[key] = fields[key] || null;
        if (naming.value === 'single') {
          input.first_name = null;
          input.surname = null;
        } else input.name = null;
      } else if (op === 'email') {
        path = 'profile/email';
        input.version = profile.value!.version;
      } else if (op.startsWith('secondary')) {
        path = 'profile/secondary-emails';
        verb =
          op === 'secondaryRemove'
            ? 'DELETE'
            : op === 'secondaryChange'
              ? 'PATCH'
              : 'POST';
        input.version = profile.value!.version;
        if (op !== 'secondaryAdd') input.address_id = fields.address_id;
      } else if (op === 'avatar' || op === 'avatarRemove') {
        path = 'profile/avatar';
        input.version = profile.value!.version;
        if (op === 'avatarRemove') verb = 'DELETE';
        else {
          const form = new FormData();
          form.set('version', String(profile.value!.version));
          form.set('avatar', files.value[0]!);
          body = form;
        }
      } else if (op === 'password') path = 'password/change';
      else if (op === 'trustIssue') path = 'trusted-devices';
      else if (op === 'deactivate' || op === 'delete') {
        await authPost(
          'step-up',
          {
            action: `runa.auth.self.${op}`,
            requirement: 'password',
            current_password: String(input.current_password),
          },
          abort.signal,
        );
        path = 'profile/lifecycle';
        body = { operation: op, version: profile.value!.version };
      } else {
        const suffix = (
          {
            totpBegin: 'totp-begin',
            totpConfirm: 'totp-confirm',
            emailEnable: 'email-enable',
            disable: 'disable',
            recoveryRotate: 'recovery-rotate',
          } as Partial<Record<AccountTask, string>>
        )[op];
        if (!suffix) throw new TypeError('Invalid security action.');
        path = 'security/' + suffix;
        if (op === 'disable') input.target = target.value;
      }
      const result = data(await accountRequest(path, verb, body, abort.signal));
      if (!alive) return;
      if (result.reauthenticate === true) {
        reauthenticating = true;
        cleanSecrets();
        clearAccountDraft();
        await authGet('session', abort.signal);
        return;
      }
      if (op === 'totpBegin') {
        if (
          typeof result.secret !== 'string' ||
          !/^[A-Z2-7]{16,128}$/.test(result.secret)
        )
          throw new TypeError('Invalid enrollment.');
        qrCode.value = enrollmentQrCode(result.qr_code);
        secret.value = result.secret;
        task.value = 'totpConfirm';
      } else {
        secret.value = '';
        if (result.recovery_codes !== undefined) {
          if (
            !Array.isArray(result.recovery_codes) ||
            !result.recovery_codes.every(
              (v) => typeof v === 'string' && /^[0-9a-f]{32}$/.test(v),
            )
          )
            throw new TypeError('Invalid recovery codes.');
          codes.value = result.recovery_codes as string[];
        }
        await loadProfile(true);
        const s = decodeSecurity(await authGet('security', abort.signal));
        if (alive) {
          security.value = s;
          if (s.trusted_devices_enabled) await readTrustedDevices();
        }
        if (op === 'details') {
          clearAccountDraft();
          draftEpoch = accountDraftEpoch();
          draft.value = null;
        }
        if (tab.value === 'trust') await readPage();
        if (op === 'totpConfirm') task.value = 'password';
      }
      if (alive && !quiet)
        notice.value = ['email', 'secondaryAdd', 'secondaryChange'].includes(op)
          ? text(
              'Sprawdź pocztę, aby potwierdzić adres.',
              'Check your email to confirm the address.',
            )
          : text('Zapisano.', 'Saved.');
    }, !preserveSecrets);
    return saved === true && !reauthenticating;
  }
  async function saveSection(section: AccountSection) {
    if (busy.value || !ready.value) return;
    cleanSecrets(false);
    Object.assign(fields, sectionFields[section]);
    Object.keys(sectionErrors[section]).forEach(
      (key) => delete sectionErrors[section][key],
    );
    await saveChanges(section);
    Object.assign(sectionErrors[section], errors);
    await nextTick();
    if (Object.keys(sectionErrors[section]).length) form.value?.focusInvalid();
  }
  async function saveChanges(section?: AccountSection) {
    if (!ready.value || busy.value || codes.value.length || !profile.value)
      return;
    const originalTask = task.value;
    const plan: AccountTask[] = [];
    const names =
      naming.value === 'single'
        ? { name: fields.name || null, first_name: null, surname: null }
        : {
            name: null,
            first_name: fields.first_name || null,
            surname: fields.surname || null,
          };
    if (
      (!section || section === 'details') &&
      ((has('own_name_editable') &&
        Object.entries(names).some(
          ([key, value]) => value !== profile.value![key as 'name'],
        )) ||
        (has('own_login_editable') &&
          (fields.login || null) !== profile.value.login))
    )
      plan.push('details');
    if (
      (!section || section === 'details') &&
      has('own_email_editable') &&
      fields.email !== profile.value.email
    )
      plan.push('email');
    if (
      (!section || section === 'avatar') &&
      canAvatar.value &&
      files.value.length
    )
      plan.push('avatar');
    else if (
      (!section || section === 'avatar') &&
      canAvatar.value &&
      removeAvatar.value &&
      profile.value.has_avatar
    )
      plan.push('avatarRemove');
    if ((!section || section === 'security') && overviewAction.value !== 'none')
      plan.push(overviewAction.value);
    if (
      (!section || section === 'password') &&
      (section === 'password' ||
        fields.password ||
        fields.password_confirmation)
    )
      plan.push('password');
    if (!plan.length) {
      cleanErrors();
      notice.value = text('Brak zmian do zapisania.', 'No changes to save.');
      return;
    }
    if (plan.includes('trustIssue') && !canTrustDevice.value) {
      cleanErrors();
      failure.value = 'unavailable';
      return;
    }
    // A factor proof may be single-use. Never reuse it for a second protected write.
    if (
      plan.includes('password') &&
      plan.some((operation) =>
        [
          'totpBegin',
          'totpConfirm',
          'emailEnable',
          'disable',
          'recoveryRotate',
          'trustIssue',
        ].includes(operation),
      )
    ) {
      cleanErrors();
      errors.password = text(
        'Zapisz nowe hasło i zmianę weryfikacji osobno.',
        'Save password and verification changes separately.',
      );
      await nextTick();
      form.value?.focusInvalid();
      return;
    }
    const validation: Record<string, string> = {};
    for (const operation of plan) {
      task.value = operation;
      validate();
      Object.assign(validation, errors);
    }
    task.value = originalTask;
    cleanErrors();
    if (plan.includes('avatar')) {
      const file = files.value[0];
      if (
        files.value.length !== 1 ||
        !file ||
        file.size > Number(caps.value.avatar_max_kib) * 1024 ||
        !['image/jpeg', 'image/png', 'image/webp'].includes(file.type)
      )
        validation.avatar = text(
          'Wybierz jeden obraz JPEG, PNG lub WebP w dozwolonym rozmiarze.',
          'Choose one JPEG, PNG or WebP image within the size limit.',
        );
    }
    Object.assign(errors, validation);
    if (Object.keys(errors).length) {
      await nextTick();
      form.value?.focusInvalid();
      return;
    }
    let completed = 0;
    try {
      for (const operation of plan) {
        const submittedEmail = fields.email;
        task.value = operation;
        // Removal is explicitly selected in this editor; lifecycle operations are separate.
        confirmed.value = operation === 'avatarRemove';
        if (!(await submitTask(true, Boolean(section)))) {
          if (completed && failure.value)
            notice.value = text(
              'Część zmian zapisano. Pozostałe wymagają sprawdzenia przed ponowną próbą.',
              'Some changes were saved. Review the remaining changes before retrying.',
            );
          return;
        }
        completed++;
        if (operation === 'email' && fields.email === submittedEmail)
          fields.email = profile.value?.email ?? '';
        if (operation === 'avatarRemove') removeAvatar.value = false;
        if (operation === 'avatar') {
          files.value = [];
          removeAvatar.value = false;
        }
        if (operation === overviewAction.value)
          overviewAction.value =
            operation === 'totpBegin' ? 'totpConfirm' : 'none';
      }
      if (section && !plan.includes('email'))
        notice.value = {
          details: text(
            'Dane konta zostały zapisane.',
            'Account details saved.',
          ),
          avatar: plan.includes('avatarRemove')
            ? text(
                'Zdjęcie profilowe zostało usunięte.',
                'Profile image removed.',
              )
            : text(
                'Zdjęcie profilowe zostało zapisane.',
                'Profile image saved.',
              ),
          password: text('Hasło zostało zmienione.', 'Password changed.'),
          security: plan.includes('trustIssue')
            ? text(
                'Ta przeglądarka została dodana do zaufanych.',
                'This browser is now trusted.',
              )
            : secret.value
              ? text(
                  'Konfiguracja rozpoczęta. Potwierdź aplikację kodem.',
                  'Setup started. Confirm the authenticator with its code.',
                )
              : codes.value.length
                ? text(
                    'Metoda została zapisana. Zachowaj kody odzyskiwania.',
                    'Method saved. Save the recovery codes.',
                  )
                : text(
                    'Ustawienia weryfikacji zostały zapisane.',
                    'Verification settings saved.',
                  ),
        }[section];
      if (plan.includes('email'))
        notice.value = text(
          'Zapisano zmiany. Sprawdź pocztę, aby potwierdzić nowy adres.',
          'Changes saved. Check your email to confirm the new address.',
        );
    } finally {
      cleanSecrets(!section || section === 'avatar');
      if (section)
        for (const key of Object.keys(sectionFields[section]))
          sectionFields[section][key] = '';
      confirmed.value = false;
      task.value = originalTask;
    }
  }
  async function saveSecondary(
    action: 'secondaryAdd' | 'secondaryChange' | 'secondaryRemove',
    input: {
      email?: string;
      address_id?: string;
      confirmed?: boolean;
      baselineEmail?: string;
    },
  ): Promise<boolean> {
    if (!alive || !ready.value || busy.value || codes.value.length)
      return false;
    cleanErrors();
    Object.keys(secondaryErrors).forEach((key) => delete secondaryErrors[key]);
    const capability = {
      secondaryAdd: 'secondary_self_add',
      secondaryChange: 'secondary_self_change',
      secondaryRemove: 'secondary_self_remove',
    }[action];
    if (
      !profile.value ||
      !has('secondary_emails_enabled') ||
      !has(capability)
    ) {
      failure.value = 'unavailable';
      return false;
    }
    if (
      action === 'secondaryAdd' &&
      profile.value.secondary_emails.length >=
        Number(caps.value.secondary_email_limit)
    ) {
      errors.email = secondaryErrors.email = text(
        'Osiągnięto limit dodatkowych adresów.',
        'The secondary address limit has been reached.',
      );
      return false;
    }
    if (
      action !== 'secondaryAdd' &&
      !profile.value.secondary_emails.some(
        (address) => address.public_id === input.address_id,
      )
    ) {
      failure.value = 'conflict';
      return false;
    }
    if (
      action !== 'secondaryAdd' &&
      input.baselineEmail !== undefined &&
      profile.value.secondary_emails.find(
        (address) => address.public_id === input.address_id,
      )?.email !== input.baselineEmail
    ) {
      failure.value = 'conflict';
      secondaryErrors.email = text(
        'Ten adres zmienił się podczas edycji. Sprawdź aktualną wartość.',
        'This address changed while you were editing. Review its current value.',
      );
      return false;
    }
    if (action === 'secondaryRemove' && !input.confirmed) {
      failure.value = 'confirmation';
      return false;
    }
    if (action !== 'secondaryRemove') {
      const message = emailError(input.email ?? '');
      if (message) {
        errors.email = secondaryErrors.email = message;
        return false;
      }
    }
    const body = {
      version: profile.value.version,
      ...(action !== 'secondaryRemove' ? { email: input.email } : {}),
      ...(action !== 'secondaryAdd' ? { address_id: input.address_id } : {}),
    };
    const saved = await performTask(
      async () => {
        await accountRequest(
          'profile/secondary-emails',
          action === 'secondaryAdd'
            ? 'POST'
            : action === 'secondaryChange'
              ? 'PATCH'
              : 'DELETE',
          body,
          abort.signal,
        );
        if (!alive) return;
        await loadProfile(true);
        if (alive)
          notice.value =
            action === 'secondaryRemove'
              ? text(
                  'Dodatkowy adres e-mail został usunięty.',
                  'Secondary email address removed.',
                )
              : text(
                  'Sprawdź pocztę, aby potwierdzić adres.',
                  'Check your email to confirm the address.',
                );
      },
      false,
      ['email', 'address_id'],
    );
    if (alive) Object.assign(secondaryErrors, errors);
    return saved === true;
  }
  async function revoke(publicId: string) {
    const own = rows.value.find((row) => row.public_id === publicId)?.current;
    await perform(async () => {
      await authPost(
        tab.value === 'trust' ? 'trusted-devices/revoke' : 'sessions/revoke',
        { public_id: publicId },
        abort.signal,
      );
      if (own) {
        clearAccountDraft();
        await authGet('session', abort.signal);
      } else if (alive) await readPage();
    });
  }
  function restoreDraft() {
    if (!draft.value) return;
    for (const key of ['name', 'first_name', 'surname'])
      if (has('own_name_editable')) fields[key] = draft.value[key] ?? '';
    if (has('own_login_editable')) fields.login = draft.value.login ?? '';
    naming.value = fields.name ? 'single' : 'full';
    draft.value = null;
    notice.value = text(
      'Przejrzyj dane i zapisz je ponownie.',
      'Review the values and submit again.',
    );
  }
  const failureText = computed(() =>
    failure.value === 'conflict'
      ? text(
          'Nie zapisano zmian. Aktualne dane pobrano automatycznie; Twoje zmiany zostały zachowane.',
          'Changes were not saved. Current data was loaded automatically; your edits were preserved.',
        )
      : failure.value === 'refresh_failed'
        ? text(
            'Nie udało się pobrać aktualnych danych. Twoje zmiany zostały zachowane.',
            'Unable to load current data. Your edits were preserved.',
          )
        : failure.value === 'csrf'
          ? text(
              'Odśwież zabezpieczenie formularza i ponów zapis.',
              'Renew form security and submit again.',
            )
          : failure.value === 'confirmation'
            ? text('Potwierdź operację.', 'Confirm the operation.')
            : failure.value === 'file_required'
              ? text('Wybierz obraz.', 'Choose an image.')
              : failure.value === 'throttled'
                ? text(
                    'Zbyt wiele prób. Spróbuj później.',
                    'Too many attempts. Try again later.',
                  )
                : text(
                    'Nie udało się wykonać operacji. Sprawdź dane i spróbuj ponownie.',
                    'The operation failed. Check your values and try again.',
                  ),
  );
  watch(busy, (value) => {
    if (!value && refreshAgain) refreshVisible();
  });
  const unsubscribePreferences = accountPreferences.subscribe(() => {
    if (
      alive &&
      ready.value &&
      !busy.value &&
      !refreshingPreferenceSnapshot &&
      tab.value === 'preferences' &&
      accountPreferences.currentAccount() === accountId
    )
      void readDelivery().catch((error) => {
        if (alive && !(error instanceof RequestCancelledError))
          failure.value = 'refresh_failed';
      });
  });
  onMounted(() => {
    void initialize();
    document.addEventListener('visibilitychange', refreshVisible);
    window.addEventListener('focus', refreshVisible);
    window.addEventListener('pageshow', refreshVisible);
  });
  watch(
    secret,
    (value) => {
      if (!value) qrCode.value = '';
    },
    { flush: 'sync' },
  );
  onScopeDispose(() => {
    if (
      task.value === 'details' &&
      profile.value &&
      ['name', 'first_name', 'surname', 'login'].some(
        (k) => (fields[k] || null) !== profile.value![k as 'name'],
      )
    )
      rememberAccountDraft(accountId, fields, draftEpoch);
    alive = false;
    unsubscribePreferences();
    document.removeEventListener('visibilitychange', refreshVisible);
    window.removeEventListener('focus', refreshVisible);
    window.removeEventListener('pageshow', refreshVisible);
    abort.abort();
    cleanSecrets();
    for (const values of Object.values(sectionFields))
      for (const key of Object.keys(values)) values[key] = '';
    secret.value = '';
    qrCode.value = '';
    codes.value = [];
  });
  return {
    historyDetail,
    historyDetailOpen,
    historyDetailLoading,
    historyDetailFailed,
    openHistoryDetail,
    closeHistoryDetail,
    retryHistoryDetail,
    text,
    locale,
    profile,
    security,
    options,
    tab,
    task,
    busy,
    pageLoading,
    trustedLoading,
    ready,
    failure,
    failureText,
    notice,
    confirmed,
    fields,
    errors,
    naming,
    method,
    target,
    secret,
    qrCode,
    codes,
    files,
    rows,
    cursor,
    delivery,
    draft,
    caps,
    has,
    canAvatar,
    proofMethods,
    tabs,
    fieldNames,
    needsProof,
    form,
    heading,
    feedback,
    choose,
    initialize,
    refresh: () =>
      refreshAccount().catch(() => {
        if (alive) failure.value = 'refresh_failed';
      }),
    detailConflicts,
    resolveDetails,
    avatarConflict,
    resolveAvatar,
    selectTab,
    submit: () => submitTask(),
    saveOverview: () => saveChanges(),
    saveSection,
    sectionFields,
    sectionErrors,
    secondaryErrors,
    saveSecondary,
    overviewAction,
    overviewNeedsProof,
    removeAvatar,
    revoke,
    saveDelivery,
    resetPreferences,
    restoreDraft,
    readPage,
    trustedRows,
    trustedCursor,
    canTrustDevice,
    loadTrustedDevices,
    revokeTrustedDevice,
    perform,
    refreshCsrf: () =>
      perform(async () => {
        await authGet('csrf', abort.signal);
        notice.value = text(
          'Możesz ponownie zapisać formularz.',
          'You can submit the form again.',
        );
      }),
    sendVerification: () =>
      perform(async () => {
        await authPost('verification/resend', {}, abort.signal);
        notice.value = text(
          'Sprawdź pocztę, aby potwierdzić główny adres.',
          'Check your email to verify the primary address.',
        );
      }),
    sendCode: () =>
      perform(async () => {
        await authPost('security/email-send', {}, abort.signal);
        notice.value = text('Kod został wysłany.', 'Code sent.');
      }),
  };
}
