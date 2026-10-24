import { enrollmentQrCode } from './totpEnrollment';
import {
  ApiError,
  RequestCancelledError,
} from '../../../resources/js/lib/http';
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
import { accountPreferences } from '../../../resources/js/lib/preferenceStore';
import {
  authFailure,
  authGet,
  authPost,
  data,
  decodeOptions,
  decodeSecurity,
  factorMethods,
  record,
} from './entry';
import type { EntryOptions, SecurityOptions } from './entry';
import {
  accountRequest,
  profileResponse,
  pageResponse,
  rememberAccountDraft,
  takeAccountDraft,
  clearAccountDraft,
  accountDraftEpoch,
} from './account';
import type { AccountRow, Profile } from './account';
export type AccountTab =
  | 'details'
  | 'security'
  | 'addresses'
  | 'avatar'
  | 'sessions'
  | 'trust'
  | 'preferences'
  | 'loginHistory'
  | 'accountHistory';
export type AccountTask =
  | 'details'
  | 'password'
  | 'email'
  | 'secondaryAdd'
  | 'secondaryChange'
  | 'secondaryRemove'
  | 'avatar'
  | 'avatarRemove'
  | 'totpBegin'
  | 'totpConfirm'
  | 'emailEnable'
  | 'disable'
  | 'recoveryRotate'
  | 'trustIssue'
  | 'deactivate'
  | 'delete';
export type AccountSection = 'details' | 'avatar' | 'password' | 'security';
interface DeliveryChoice {
  module: string;
  key: string;
  names: { pl: string; en: string };
  mandatory: boolean;
  sensitive: boolean;
  enabled: boolean;
  recipients: string[];
}
export function useAuthAccount(accountId: string) {
  const { locale } = useLocale();
  const text = (pl: string, en: string) => (locale.value === 'pl' ? pl : en);
  const profile = shallowRef<Profile>(),
    security = shallowRef<SecurityOptions>(),
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
  const avatarConflict = ref(false);
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
  const rows = ref<AccountRow[]>([]),
    cursor = ref<string | null>(null);
  const trustedRows = ref<AccountRow[]>([]),
    trustedCursor = ref<string | null>(null);
  let trustedReadEpoch = 0;
  const canTrustDevice = computed(
    () =>
      security.value?.trusted_devices_enabled === true &&
      proofMethods.value.length > 0,
  );
  const detailConflicts = reactive<Record<string, string>>({});
  let deliveryReadEpoch = 0;
  let refreshPromise: Promise<void> | undefined;
  let refreshAgain = false;
  const delivery = ref<DeliveryChoice[]>([]);
  const draft = shallowRef<Record<string, string> | null>(null);
  let draftEpoch = accountDraftEpoch();
  const abort = new AbortController();
  let alive = true,
    readEpoch = 0;
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
  const needsProof = computed(
    () =>
      proofMethods.value.length > 0 &&
      ([
        'totpBegin',
        'totpConfirm',
        'emailEnable',
        'disable',
        'recoveryRotate',
        'trustIssue',
      ].includes(task.value) ||
        (task.value === 'password' && security.value?.password_change_factor)),
  );
  const fieldNames = computed(() => [
    ...(task.value === 'details'
      ? [
          ...(has('own_name_editable')
            ? naming.value === 'single'
              ? ['name']
              : ['first_name', 'surname']
            : []),
          ...(has('own_login_editable') ? ['login'] : []),
        ]
      : []),
    ...(['email', 'secondaryAdd', 'secondaryChange'].includes(task.value)
      ? ['email']
      : []),
    ...([
      'password',
      'email',
      'totpBegin',
      'totpConfirm',
      'emailEnable',
      'disable',
      'recoveryRotate',
      'deactivate',
      'delete',
    ].includes(task.value)
      ? ['current_password']
      : []),
    ...(task.value === 'password' ? ['password', 'password_confirmation'] : []),
    ...(needsProof.value ? ['code'] : []),
    ...(task.value === 'totpConfirm' ? ['enrollment_code'] : []),
  ]);
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
  function emailError(value: string): string {
    return !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value) ||
      value.length > 254 ||
      /[^\x21-\x7e]/.test(value)
      ? text('Podaj poprawny adres e-mail.', 'Enter a valid email address.')
      : '';
  }
  function validate() {
    cleanErrors();
    for (const key of fieldNames.value) {
      const value = fields[key] ?? '';
      if (
        !value.trim() &&
        !(key === 'login' && options.value?.identifier_mode !== 'login')
      )
        errors[key] = text('Wypełnij to pole.', 'Complete this field.');
      if (key === 'email' && emailError(value)) errors[key] = emailError(value);
      if (
        ['name', 'first_name', 'surname'].includes(key) &&
        (value.trim() !== value ||
          [...value].length > 160 ||
          /\p{C}/u.test(value))
      )
        errors[key] = text('Popraw dane.', 'Correct this value.');
      if (
        key === 'login' &&
        value &&
        !/^[a-zA-Z0-9][a-zA-Z0-9._-]{0,63}$/.test(value)
      )
        errors[key] = text('Niepoprawny login.', 'Invalid login.');
      if (
        key === 'code' &&
        !(method.value === 'recovery' ? /^[0-9a-f]{32}$/ : /^[0-9]{6}$/).test(
          value,
        )
      )
        errors[key] = text('Niepoprawny kod.', 'Invalid code.');
      if (key === 'enrollment_code' && !/^[0-9]{6}$/.test(value))
        errors[key] = text('Wpisz 6 cyfr.', 'Enter 6 digits.');
    }
    if (task.value === 'password' && options.value) {
      const p = options.value.password,
        v = fields.password ?? '';
      if (
        [...v].length < p.min_length ||
        new TextEncoder().encode(v).length > p.max_bytes ||
        v.includes('\0') ||
        (p.mixed_case && (!/\p{Ll}/u.test(v) || !/\p{Lu}/u.test(v))) ||
        (p.numbers && !/\p{N}/u.test(v)) ||
        (p.symbols && !/[\p{S}\p{P}]/u.test(v))
      )
        errors.password = text(
          'Hasło nie spełnia podanych wymagań.',
          'Password does not meet the listed requirements.',
        );
      if (v !== fields.password_confirmation)
        errors.password_confirmation = text(
          'Hasła muszą być takie same.',
          'Passwords must match.',
        );
    }
    return Object.keys(errors).length === 0;
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
  async function loadProfile(ownWrite = false) {
    const p = profileResponse(await authGet('profile', abort.signal));
    if (p.public_id !== accountId) throw new TypeError('Account changed.');
    if (alive && (!profile.value || p.version >= profile.value.version)) {
      const before = profile.value;
      if (before) {
        if (
          (!ownWrite || p.version > before.version + 1) &&
          p.version > before.version &&
          (files.value.length || removeAvatar.value)
        )
          avatarConflict.value = true;
        const keys = [
          'name',
          'first_name',
          'surname',
          'login',
          'email',
        ] as const;
        const oldNaming = before.name === null ? 'full' : 'single';
        const nextNaming = p.name === null ? 'full' : 'single';
        const nameKeys = ['name', 'first_name', 'surname'] as const;
        const dirtyName =
          naming.value !== oldNaming ||
          nameKeys.some((key) => (fields[key] ?? '') !== (before[key] ?? ''));
        const changedName = nameKeys.some((key) => p[key] !== before[key]);
        if (!dirtyName) naming.value = nextNaming;
        else if (
          changedName &&
          (naming.value !== nextNaming ||
            nameKeys.some((key) => (fields[key] ?? '') !== (p[key] ?? '')))
        ) {
          detailConflicts.naming = nextNaming;
          for (const key of nameKeys) detailConflicts[key] = p[key] ?? '';
        }
        for (const key of keys) {
          const groupedName = (nameKeys as readonly string[]).includes(key);
          if (
            groupedName
              ? !dirtyName
              : (fields[key] ?? '') === (before[key] ?? '')
          )
            fields[key] = p[key] ?? '';
          else if (
            !groupedName &&
            p[key] !== before[key] &&
            (fields[key] ?? '') !== (p[key] ?? '')
          )
            detailConflicts[key] = p[key] ?? '';
        }
      }
      if (
        naming.value === (p.name === null ? 'full' : 'single') &&
        ['name', 'first_name', 'surname'].every(
          (key) => (fields[key] ?? '') === (p[key as 'name'] ?? ''),
        )
      )
        for (const key of ['naming', 'name', 'first_name', 'surname'])
          delete detailConflicts[key];
      for (const key of ['login', 'email'] as const)
        if ((fields[key] ?? '') === (p[key] ?? '')) delete detailConflicts[key];
      profile.value = p;
    }
  }
  function resolveDetails(keepLocal = false) {
    if (busy.value || !profile.value) return;
    if (!keepLocal) {
      for (const key of Object.keys(detailConflicts)) {
        if (key === 'naming')
          naming.value = profile.value.name === null ? 'full' : 'single';
        else fields[key] = detailConflicts[key] ?? '';
      }
    }
    Object.keys(detailConflicts).forEach((key) => delete detailConflicts[key]);
    if (failure.value === 'conflict') failure.value = '';
  }
  function resolveAvatar(keepLocal = false) {
    if (busy.value) return;
    if (!keepLocal) {
      files.value = [];
      removeAvatar.value = false;
    }
    avatarConflict.value = false;
    if (failure.value === 'conflict') failure.value = '';
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
      if (accountPreferences.currentAccount() === accountId)
        await accountPreferences.refresh();
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
  async function readPage(append = false) {
    const own = ++readEpoch,
      currentTab = tab.value;
    const path =
      currentTab === 'sessions'
        ? 'sessions'
        : currentTab === 'trust'
          ? 'trusted-devices'
          : 'profile/history';
    const input: Record<string, string> = {};
    if (currentTab.endsWith('History'))
      input.kind = currentTab === 'loginHistory' ? 'login' : 'account';
    if (append && cursor.value) input.before = cursor.value;
    const response = await accountRequest(path, 'GET', input, abort.signal);
    const page = currentTab.endsWith('History')
      ? (() => {
          const p = data(response);
          return pageResponse({
            data: p.entries,
            meta: { next_cursor: p.next },
          });
        })()
      : pageResponse(response);
    if (alive && own === readEpoch && currentTab === tab.value) {
      rows.value = append ? [...rows.value, ...page.rows] : page.rows;
      cursor.value = page.cursor;
    }
  }
  async function readTrustedDevices(append = false) {
    const own = ++trustedReadEpoch;
    const input =
      append && trustedCursor.value ? { before: trustedCursor.value } : {};
    const page = pageResponse(
      await accountRequest('trusted-devices', 'GET', input, abort.signal),
    );
    if (alive && own === trustedReadEpoch) {
      trustedRows.value = append
        ? [...trustedRows.value, ...page.rows]
        : page.rows;
      trustedCursor.value = page.cursor;
    }
  }
  async function loadTrustedDevices(append = false) {
    if (!security.value?.trusted_devices_enabled) return false;
    return (await perform(() => readTrustedDevices(append), false)) === true;
  }
  async function revokeTrustedDevice(publicId: string) {
    if (
      !security.value?.trusted_devices_enabled ||
      !trustedRows.value.some((row) => row.public_id === publicId)
    )
      return false;
    return (
      (await perform(async () => {
        await authPost(
          'trusted-devices/revoke',
          { public_id: publicId },
          abort.signal,
        );
        if (!alive) return;
        trustedRows.value = trustedRows.value.filter(
          (row) => row.public_id !== publicId,
        );
        notice.value = text(
          'Zaufanie urządzeniu zostało unieważnione.',
          'Device trust revoked.',
        );
      }, false)) === true
    );
  }
  async function readDelivery() {
    await fetchDelivery();
  }
  async function fetchDelivery(signal = abort.signal) {
    const own = ++deliveryReadEpoch;
    const p = data(
      await authPost('delivery-preferences', { operation: 'read' }, signal),
    );
    if (
      p.account !== accountId ||
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
      alive &&
      own === deliveryReadEpoch &&
      accountPreferences.currentAccount() === accountId
    )
      delivery.value = list;
    return { revision: Number(p.revision), categories: list };
  }
  async function selectTab(value: AccountTab) {
    if (busy.value || !tabs.value.includes(value)) return;
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
    if (['sessions', 'trust', 'loginHistory', 'accountHistory'].includes(value))
      await perform(() => readPage());
    if (value === 'preferences') await perform(readDelivery);
    await refreshAccount().catch(() => {
      if (alive) failure.value = 'refresh_failed';
    });
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
          AbortSignal.any([signal, abort.signal]),
        );
        if (!alive || signal.aborted) throw new RequestCancelledError();
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
          AbortSignal.any([signal, abort.signal]),
        );
      });
      if (alive) {
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
      if (alive) {
        confirmed.value = false;
        await readDelivery();
        notice.value = text(
          'Przywrócono domyślne preferencje.',
          'Default preferences restored.',
        );
      }
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
    deliveryReadEpoch++;
    readEpoch++;
    trustedReadEpoch++;
    trustedRows.value = [];
    trustedCursor.value = null;
    abort.abort();
    cleanSecrets();
    for (const values of Object.values(sectionFields))
      for (const key of Object.keys(values)) values[key] = '';
    secret.value = '';
    qrCode.value = '';
    codes.value = [];
  });
  return {
    text,
    locale,
    profile,
    security,
    options,
    tab,
    task,
    busy,
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
