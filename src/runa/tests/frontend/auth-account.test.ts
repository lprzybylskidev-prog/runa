import type { Profile } from '../../modules/auth/frontend/account';
import AuthRecoveryCodesDialog from '../../modules/auth/frontend/AuthRecoveryCodesDialog.vue';
import { afterEach, beforeEach, expect, it, vi } from 'vitest';
import { defineComponent, type ComponentPublicInstance } from 'vue';
import AuthAccountEditor from '../../modules/auth/frontend/AuthAccountEditor.vue';
import AuthAccountPreferences from '../../modules/auth/frontend/AuthAccountPreferences.vue';
import { useDisplayPreferences } from '../../resources/js/composables/useDisplayPreferences';
import { enrollmentQrCode } from '../../modules/auth/frontend/totpEnrollment';
import AuthAvatarCropper from '../../modules/auth/frontend/AuthAvatarCropper.vue';
import {
  Avatar,
  ApplicationLayout,
  Button,
  Dialog,
  FileInput,
  ConfirmDialog,
  Switch,
  Select,
} from '@/runa/ui';
import CustomerHost from './fixtures/customization/CustomerHost.vue';
import CustomerAccount from './fixtures/customization/CustomerAccount.vue';
import CustomerApplication from './fixtures/customization/CustomerApplication.vue';
import {
  enableAutoUnmount,
  flushPromises,
  mount,
  DOMWrapper,
} from '@vue/test-utils';
import AuthAccount from '../../modules/auth/frontend/AuthAccount.vue';
import AuthWelcome from '../../modules/auth/frontend/AuthWelcome.vue';
import AuthApplication from '../../modules/auth/frontend/AuthApplication.vue';
import { useApplicationSession } from '../../modules/auth/frontend/useApplicationSession';
import {
  accountDraftEpoch,
  clearAccountDraft,
  rememberAccountDraft,
  takeAccountDraft,
} from '../../modules/auth/frontend/account';
import { accountPreferences } from '../../resources/js/lib/preferenceStore';
import { request } from '../../resources/js/lib/http';

type MultiSelectInstance = ComponentPublicInstance<
  Parameters<typeof Select<true>>[0]
>;

enableAutoUnmount(afterEach);
const id = '01900000-0000-7000-8000-000000000001';
const other = '01900000-0000-7000-8000-000000000002';
const options = {
  public_welcome_enabled: true,
  public_registration: false,
  identifier_mode: 'email',
  remember_enabled: false,
  password: {
    min_length: 12,
    max_bytes: 72,
    mixed_case: false,
    numbers: false,
    symbols: false,
  },
};
const security = {
  email_verified: true,
  required: 'optional',
  enrolled: [],
  totp_enabled: true,
  email_otp_enabled: true,
  recovery_codes_enabled: true,
  password_change_factor: false,
  trusted_devices_enabled: false,
};
const profile: Profile = {
  public_id: id,
  version: 5,
  display_name: 'Test User',
  initials: 'TU',
  name: 'Test User',
  first_name: null,
  surname: null,
  login: null,
  email: 'test@example.test',
  email_verified: true,
  has_avatar: false,
  secondary_emails: [],
  capabilities: {
    own_name_editable: true,
    own_login_editable: true,
    own_email_editable: true,
    secondary_emails_enabled: false,
    secondary_email_limit: 5,
    avatar_enabled: true,
    avatar_editor: 'both',
    avatar_max_kib: 2048,
    own_login_history: false,
    own_account_history: false,
    self_deactivate: false,
    self_delete: false,
  },
};
let sessionAccount = id;
const session = () => ({
  public_id: sessionAccount,
  display_name: 'Test User',
  restrictions: [],
  session: {
    public_id: other,
    current: true,
    activity_at: new Date().toISOString(),
    expires_at: new Date(Date.now() + 3600000).toISOString(),
  },
  activity: {
    idle_minutes: 120,
    heartbeat_seconds: 30,
    warning_seconds: 120,
    extension: true,
  },
});
const json = (data: unknown, status = 200) =>
  new Response(
    JSON.stringify(status >= 400 ? { error: { code: data } } : { data }),
    { status, headers: { 'Content-Type': 'application/json' } },
  );
let writes: { path: string; body: Record<string, unknown> }[];
let signedIn = true;
let currentProfile = structuredClone(profile);
let currentSecurity: Record<string, unknown> = { ...security };
let write: (
  path: string,
  body: Record<string, unknown>,
) => Response | Promise<Response>;
beforeEach(() => {
  accountPreferences.disconnect();
  clearAccountDraft();
  localStorage.clear();
  writes = [];
  signedIn = true;
  sessionAccount = id;
  options.public_registration = false;
  currentProfile = structuredClone(profile);
  currentSecurity = { ...security };
  write = (path) =>
    json(
      path.endsWith('/session/activity')
        ? session().session
        : { accepted: true },
    );
  vi.spyOn(window, 'fetch').mockImplementation(async (input, init) => {
    const path = String(input).split('?')[0]!;
    if (path.endsWith('/preferences'))
      return json({
        account: sessionAccount,
        revision: 0,
        legacy_claimed: true,
        values: [],
      });
    if (init?.method && init.method !== 'GET') {
      const body =
        init.body instanceof FormData
          ? Object.fromEntries(init.body)
          : (JSON.parse(String(init.body)) as Record<string, unknown>);
      writes.push({ path, body });
      return write(path, body);
    }
    if (path.endsWith('/trusted-devices'))
      return new Response(
        JSON.stringify({ data: [], meta: { next_cursor: null } }),
        { headers: { 'Content-Type': 'application/json' } },
      );
    if (path.endsWith('/entry-options')) return json(options);
    if (path.endsWith('/security')) return json(currentSecurity);
    if (path.endsWith('/profile')) return json(currentProfile);
    if (path.endsWith('/session'))
      return signedIn ? json(session()) : json('authentication_required', 401);
    return json({ ready: true });
  });
});
afterEach(() => {
  accountPreferences.disconnect();
  clearAccountDraft();
});
function mountController<T>(setup: () => T) {
  let state!: T;
  const wrapper = mount(
    defineComponent({
      setup() {
        state = setup();
        return () => null;
      },
    }),
  );
  return { state, wrapper };
}
function mountSession(navigate: (path: string) => void = vi.fn()) {
  return mountController(() => useApplicationSession(navigate));
}
async function loaded(w: ReturnType<typeof mount>) {
  await vi.waitFor(() => expect(w.find('form').exists()).toBe(true));
  await flushPromises();
}

it('loads session rows once with a centered spinner and no visible loading text', async () => {
  const original = vi.mocked(window.fetch).getMockImplementation()!;
  let reads = 0;
  let release!: () => void;
  const gate = new Promise<void>((resolve) => (release = resolve));
  vi.mocked(window.fetch).mockImplementation(async (input, init) => {
    if (String(input).split('?')[0]!.endsWith('/sessions')) {
      reads++;
      await gate;
      return new Response(
        JSON.stringify({ data: [], meta: { next_cursor: null } }),
        {
          headers: { 'Content-Type': 'application/json' },
        },
      );
    }
    return original(input, init);
  });
  const w = mount(AuthAccount, { props: { accountId: id } });
  await loaded(w);
  const button = w
    .findAll('nav button')
    .find((item) => /Aktywne sesje|Active sessions/.test(item.text()))!;
  await button.trigger('click');
  expect(w.find('[data-table-loading] [role="status"]').exists()).toBe(true);
  expect(w.text()).not.toMatch(/Ładowanie danych|Loading data/);
  expect(w.text()).not.toMatch(/Brak wyników|No results/);
  release();
  await vi.waitFor(() => expect(w.text()).toMatch(/Brak wyników|No results/));
  await flushPromises();
  expect(reads).toBe(1);
  expect(w.find('[data-table-loading]').exists()).toBe(false);
  expect(w.text()).not.toMatch(/Ładowanie danych|Loading data/);
});

it('keeps the destination shell and account skeleton through session and account loading without mounting protected forms early', async () => {
  const original = vi.mocked(window.fetch).getMockImplementation()!;
  let releaseSession!: () => void;
  let releaseProfile!: () => void;
  const sessionGate = new Promise<void>(
    (resolve) => (releaseSession = resolve),
  );
  const profileGate = new Promise<void>(
    (resolve) => (releaseProfile = resolve),
  );
  vi.mocked(window.fetch).mockImplementation(async (input, init) => {
    const path = String(input).split('?')[0]!;
    if (path.endsWith('/session')) await sessionGate;
    if (path.endsWith('/profile')) await profileGate;
    return original(input, init);
  });
  const w = mount(AuthApplication, { props: { account: true } });
  const shell = w.getComponent(ApplicationLayout).element;
  expect(w.find('[data-account-loading]').exists()).toBe(true);
  expect(w.findAll('[data-account-loading] h3')).toHaveLength(4);
  expect(w.find('form').exists()).toBe(false);
  releaseSession();
  await flushPromises();
  expect(w.getComponent(ApplicationLayout).element).toBe(shell);
  expect(w.find('[data-account-loading]').exists()).toBe(true);
  expect(w.find('form').exists()).toBe(false);
  releaseProfile();
  await loaded(w);
  expect(w.getComponent(ApplicationLayout).element).toBe(shell);
  expect(w.find('[data-account-loading]').exists()).toBe(false);
});

it('account boundary diagnostic: preserves only safe drafts for the same account and fences explicit cleanup', () => {
  rememberAccountDraft(id, {
    name: 'Draft',
    password: 'must-never-survive',
    code: '123456',
  });
  expect(takeAccountDraft(id)).toEqual({
    name: 'Draft',
    first_name: '',
    surname: '',
    login: '',
  });
  rememberAccountDraft(id, { name: 'Old account' });
  expect(takeAccountDraft(other)).toBeNull();
  const epoch = accountDraftEpoch();
  clearAccountDraft();
  rememberAccountDraft(id, { name: 'Late unmount' }, epoch);
  expect(takeAccountDraft(id)).toBeNull();
});
it('account boundary diagnostic: saves with the current revision and refuses stale writes without replay', async () => {
  write = () => json('identity_stale', 409);
  const w = mount(AuthAccount, { props: { accountId: id } });
  await loaded(w);
  await w.get('input[name="name"]').setValue('Changed name');
  await w.get('form').trigger('submit');
  await vi.waitFor(() =>
    expect(w.text() + document.body.textContent).toContain('Dane zmieniły się'),
  );
  expect(writes).toHaveLength(1);
  expect(writes[0]?.body).toMatchObject({
    version: 5,
    name: 'Changed name',
    first_name: null,
    surname: null,
  });
  await w.get('button').trigger('focus');
  await flushPromises();
  expect(writes).toHaveLength(1);
  expect(w.text()).not.toContain('Historia konta');
  expect(w.text()).not.toContain('Zaufane urządzenia');
});
it('account boundary diagnostic: restores a draft only after explicit review and never submits on login', async () => {
  rememberAccountDraft(id, { name: 'Recovered name' });
  const w = mount(AuthAccount, { props: { accountId: id } });
  await loaded(w);
  expect((w.get('input[name="name"]').element as HTMLInputElement).value).toBe(
    'Test User',
  );
  const restore = w
    .findAll('button')
    .find((b) => b.text() === 'Przywróć do przejrzenia')!;
  await restore.trigger('click');
  expect((w.get('input[name="name"]').element as HTMLInputElement).value).toBe(
    'Recovered name',
  );
  expect(writes).toHaveLength(0);
});
it('account boundary diagnostic: polling is not activity and real activity is throttled', async () => {
  const { state, wrapper: w } = mountSession();
  await vi.waitFor(() => expect(state.session.value?.public_id).toBe(id));
  await request('/health');
  expect(writes).toHaveLength(0);
  await state.activity('typing');
  await state.activity('click');
  expect(
    writes.filter((v) => v.path.endsWith('/session/activity')),
  ).toHaveLength(1);
  expect(state.failure.value).toBe('');
  w.unmount();
});
it('account boundary diagnostic: 401 fences late responses, removes the account and requires reauthentication', async () => {
  const { state } = mountSession();
  await vi.waitFor(() => expect(state.session.value?.public_id).toBe(id));
  let finish!: (r: Response) => void;
  write = () => new Promise((resolve) => (finish = resolve));
  const activity = state.activity('typing');
  await vi.waitFor(() => expect(finish).toBeTypeOf('function'));
  signedIn = false;
  await request('/runa/auth/session').catch(() => {});
  expect(state.paused.value).toBe(true);
  expect(accountPreferences.signedIn()).toBe(false);
  const original = state.session.value;
  finish(json({ ...session().session, activity_at: '2099-01-01T00:00:00Z' }));
  await activity;
  expect(state.session.value).toBe(original);
});
it('account boundary diagnostic: failed logout stays visible and successful logout clears state before returning through root', async () => {
  const navigate = vi.fn();
  const { state } = mountSession(navigate);
  await vi.waitFor(() => expect(state.session.value?.public_id).toBe(id));
  write = () => json('unavailable', 503);
  await state.logout();
  await flushPromises();
  expect(navigate).not.toHaveBeenCalled();
  expect(state.session.value?.public_id).toBe(id);
  expect(accountPreferences.currentAccount()).toBe(id);
  rememberAccountDraft(id, { name: 'Unsaved' });
  write = (path) =>
    json(
      path.endsWith('/session/activity')
        ? session().session
        : { accepted: true },
    );
  await state.logout();
  expect(navigate).toHaveBeenCalledWith('/');
  expect(state.session.value).toBeUndefined();
  expect(takeAccountDraft(id)).toBeNull();
});
it('account boundary diagnostic: welcome exposes registration only when enabled and resolves the current session', async () => {
  signedIn = false;
  const w = mount(AuthWelcome);
  await vi.waitFor(() =>
    expect(w.find('a[href="/auth/login"]').exists()).toBe(true),
  );
  expect(w.find('a[href="/auth/register"]').exists()).toBe(false);
  expect(w.find('header a[href="/auth/login"]').exists()).toBe(true);
  expect(w.find('main a[href="/auth/login"]').exists()).toBe(false);
  w.unmount();
  options.public_registration = true;
  const w2 = mount(AuthWelcome);
  await vi.waitFor(() =>
    expect(w2.find('a[href="/auth/register"]').exists()).toBe(true),
  );
  expect(w2.find('header a[href="/auth/register"]').exists()).toBe(true);
  w2.unmount();
  signedIn = true;
  sessionAccount = id;
  const w3 = mount(AuthWelcome);
  await vi.waitFor(() => expect(w3.find('a[href="/app"]').exists()).toBe(true));
  expect(w3.find('a[href="/auth/login"]').exists()).toBe(false);
});
it('account boundary diagnostic: application uses the existing dropdown for both profile and logout', async () => {
  const w = mount(AuthApplication);
  await vi.waitFor(() =>
    expect(
      w.find('.app-layout').exists() || w.find('.shell-workspace').exists(),
    ).toBe(true),
  );
  const trigger = w.find('button[aria-label^="Menu użytkownika"]');
  expect(trigger.exists()).toBe(true);
  expect(w.find('.shell-workspace h1').exists()).toBe(false);
  expect(w.find('.shell-workspace a[href="/account"]').exists()).toBe(false);
  expect(w.find('nav a[href="/account"]').exists()).toBe(false);
  await trigger.trigger('click');
  await vi.waitFor(() =>
    expect(document.body.textContent + w.text()).toContain('Wyloguj się'),
  );
});

it('edits secondary addresses in account details with identity, revision and explicit deletion confirmation', async () => {
  currentProfile.capabilities.secondary_emails_enabled = true;
  Object.assign(currentProfile.capabilities, {
    secondary_self_add: true,
    secondary_self_change: true,
    secondary_self_remove: true,
  });
  const address = {
    public_id: other,
    email: 'secondary@example.test',
    verified_at: null,
  };
  Object.assign(currentProfile, { secondary_emails: [address] });
  const w = mount(AuthAccount, { props: { accountId: id } });
  await loaded(w);
  expect(w.find('nav').text()).not.toContain('Dodatkowe adresy e-mail');
  const section = w.get('[data-secondary-emails]');
  expect(
    section.element.closest('section')?.querySelector('h4')?.textContent,
  ).toContain('Dodatkowe adresy');
  await section
    .get('[aria-label="Edytuj adres: secondary@example.test"]')
    .trigger('click');
  const form = section.get('[data-account-section=secondary]');
  await form.get('[name=secondary_email]').setValue('changed@example.test');
  write = () => {
    currentProfile = { ...currentProfile, version: 6 };
    return json({ accepted: true });
  };
  await form.trigger('submit');
  await vi.waitFor(() => expect(writes).toHaveLength(1));
  await vi.waitFor(() =>
    expect(
      form.findAll('button').some((button) => button.text() === 'Dodaj adres'),
    ).toBe(true),
  );
  expect(writes[0]?.body).toMatchObject({
    address_id: other,
    email: 'changed@example.test',
    version: 5,
  });
  await section
    .get('[aria-label="Usuń adres: secondary@example.test"]')
    .trigger('click');
  const modal = w
    .findAllComponents(ConfirmDialog)
    .find((dialog) => dialog.props('title') === 'Usunąć dodatkowy adres?')!;
  expect(modal.props('modelValue')).toBe(true);
  expect(writes).toHaveLength(1);
  modal.vm.$emit('update:modelValue', false);
  await flushPromises();
  expect(writes).toHaveLength(1);
  await section
    .get('[aria-label="Usuń adres: secondary@example.test"]')
    .trigger('click');
  write = () => {
    currentProfile = { ...currentProfile, version: 7, secondary_emails: [] };
    return json({ accepted: true });
  };
  modal.vm.$emit('confirm');
  await vi.waitFor(() => expect(modal.props('modelValue')).toBe(false));
  expect(writes[1]?.body).toMatchObject({ address_id: other, version: 6 });
});
it('optional TOTP setup retains its private key only until confirmation and requires saved-code acknowledgement', async () => {
  const w = mount(AuthAccount, { props: { accountId: id } });
  await loaded(w);
  await w
    .get('[data-account-security]')
    .findAll('button')
    .find((button) => button.text() === 'Skonfiguruj')!
    .trigger('click');
  await flushPromises();
  const qrImage =
    'data:image/svg+xml;base64,' +
    btoa(
      '<svg xmlns="http://www.w3.org/2000/svg" width="256" height="256"><rect width="256" height="256" fill="white"/></svg>',
    );
  write = (path) =>
    json(
      path.endsWith('/totp-begin')
        ? { secret: 'ABCDEFGHIJKLMNOP', qr_code: qrImage }
        : { recovery_codes: ['a'.repeat(32)] },
    );
  await new DOMWrapper(document.body)
    .get('[data-account-section=security] input[name="current_password"]')
    .setValue('a-long-test-password');
  await new DOMWrapper(document.body)
    .get('[data-account-section=security]')
    .trigger('submit');
  await flushPromises();
  expect(
    new DOMWrapper(document.body).get('[data-enrollment-secret]').text(),
  ).toBe('ABCDEFGHIJKLMNOP');
  expect(
    new DOMWrapper(document.body).get('[data-enrollment-qr]').attributes('src'),
  ).toBe(qrImage);
  expect(
    new DOMWrapper(document.body).get('[data-enrollment-qr]').attributes('alt'),
  ).toContain('Kod QR');
  expect(
    (
      new DOMWrapper(document.body).get(
        '[data-account-section=security] input[name="current_password"]',
      ).element as HTMLInputElement
    ).value,
  ).toBe('');
  await new DOMWrapper(document.body)
    .get('[data-account-section=security] input[name="current_password"]')
    .setValue('a-long-test-password');
  await new DOMWrapper(document.body)
    .get('input[name="enrollment_code"]')
    .setValue('123456');
  await new DOMWrapper(document.body)
    .get('[data-account-section=security]')
    .trigger('submit');
  await flushPromises();
  expect(
    new DOMWrapper(document.body).find('[data-enrollment-secret]').exists(),
  ).toBe(false);
  expect(
    new DOMWrapper(document.body).find('[data-enrollment-qr]').exists(),
  ).toBe(false);
  await vi.waitFor(() =>
    expect(document.querySelector('[data-recovery-code]')?.textContent).toBe(
      'a'.repeat(32),
    ),
  );
  const recovery = w.getComponent(AuthRecoveryCodesDialog);
  await vi.waitFor(() => expect(recovery.props('busy')).toBe(false));
  expect(
    document.querySelector('[data-recovery-code]')?.closest('[role=dialog]'),
  ).not.toBeNull();
  expect(w.find('[data-account-section=security]').exists()).toBe(false);
  await w
    .getComponent(AuthRecoveryCodesDialog)
    .getComponent(Button)
    .trigger('click');
  expect(w.find('[data-recovery-code]').exists()).toBe(false);
});
it('known password errors stop submission and a CSRF renewal does not replay the write', async () => {
  const w = mount(AuthAccount, { props: { accountId: id } });
  await loaded(w);
  await w
    .get('[data-account-section=password] input[name="current_password"]')
    .setValue('a-long-test-password');
  await w.get('input[name="password"]').setValue('short');
  await w.get('input[name="password_confirmation"]').setValue('different');
  await w.get('[data-account-section=password]').trigger('submit');
  await flushPromises();
  expect(writes).toHaveLength(0);
  await w.get('input[name="password"]').setValue('a-new-long-password');
  await w
    .get('input[name="password_confirmation"]')
    .setValue('a-new-long-password');
  write = () => json('csrf_token_mismatch', 419);
  await w.get('[data-account-section=password]').trigger('submit');
  await flushPromises();
  expect(writes).toHaveLength(1);
  const retry = Array.from(
    document.querySelectorAll<HTMLButtonElement>('.app-toast button'),
  ).find((button) => button.textContent?.trim() === 'Odśwież zabezpieczenie')!;
  retry.click();
  await flushPromises();
  expect(writes).toHaveLength(1);
  expect(
    (w.get('input[name="password"]').element as HTMLInputElement).value,
  ).toBe('');
});

it('account boundary diagnostic: a refreshed different account clears drafts and fences the prior form unmount', async () => {
  const { state } = mountSession();
  await vi.waitFor(() => expect(state.session.value?.public_id).toBe(id));
  const epoch = accountDraftEpoch();
  rememberAccountDraft(id, { name: 'Previous account' });
  sessionAccount = other;
  await state.refresh();
  expect(state.session.value?.public_id).toBe(other);
  expect(takeAccountDraft(id)).toBeNull();
  rememberAccountDraft(id, { name: 'Late previous form' }, epoch);
  expect(takeAccountDraft(id)).toBeNull();
});

it('customization: application composition retains the session boundary around customer content', async () => {
  const w = mount(CustomerHost, {
    props: { mode: 'application', components: {} },
  });
  await vi.waitFor(() =>
    expect(w.find('[data-customer-page]').exists()).toBe(true),
  );
  expect(w.get('[data-customer-page]').text()).toBe(id);
  signedIn = false;
  await request('/runa/auth/session').catch(() => undefined);
  await vi.waitFor(() =>
    expect(w.find('[data-customer-page]').exists()).toBe(false),
  );
  await vi.waitFor(() =>
    expect(w.find('input[name="identifier"]').exists()).toBe(true),
  );
});

it('customization: full customer shell uses public session orchestration and removes content on 401', async () => {
  const w = mount(CustomerApplication);
  await vi.waitFor(() =>
    expect(w.find('[data-customer-application]').exists()).toBe(true),
  );
  signedIn = false;
  await request('/runa/auth/session').catch(() => undefined);
  await vi.waitFor(() =>
    expect(w.find('[data-customer-application]').exists()).toBe(false),
  );
  await vi.waitFor(() =>
    expect(w.find('input[name="identifier"]').exists()).toBe(true),
  );
});

it('customization: account replacement consumes the public controller with current capabilities', async () => {
  const w = mount(CustomerHost, {
    props: {
      mode: 'account',
      accountId: id,
      components: { account: CustomerAccount },
    },
  });
  await vi.waitFor(() =>
    expect(w.text() + document.body.textContent).toContain('Test User'),
  );
  expect(w.find('[data-customer-account]').exists()).toBe(true);
  expect(w.text() + document.body.textContent).toContain('details');
  expect(w.text()).not.toContain('loginHistory');
});

it('account cards validate their changes before writing and stops after a partial failure without replay', async () => {
  const w = mount(AuthAccount, { props: { accountId: id } });
  await loaded(w);
  expect(w.findAll('form')).toHaveLength(4);
  expect(
    w.findAll('button').some((button) => button.text() === 'Zmień hasło'),
  ).toBe(false);
  expect(
    w.findAll('button').some((button) => button.text() === 'Dodaj aplikację'),
  ).toBe(false);
  expect(w.findComponent(AuthAccountEditor).exists()).toBe(true);
  await w.get('input[name="name"]').setValue('Edited name');
  await w.get('input[name="email"]').setValue('next@example.test');
  await w.get('form').trigger('submit');
  await flushPromises();
  expect(writes).toHaveLength(0);
  expect(
    w.get('input[name="current_password"]').attributes('aria-invalid'),
  ).toBe('true');
  await w
    .get('input[name="current_password"]')
    .setValue('a-long-test-password');
  write = (path, body) => {
    if (path.endsWith('/profile')) {
      currentProfile = {
        ...currentProfile,
        name: String(body.name),
        version: 6,
      };
      return json({ accepted: true });
    }
    return json('identity_stale', 409);
  };
  await w.get('form').trigger('submit');
  await flushPromises();
  await vi.waitFor(() =>
    expect(writes.map((item) => item.path)).toEqual([
      '/api/runa/auth/profile',
      '/api/runa/auth/profile/email',
    ]),
  );
  expect(writes[1]?.body).toMatchObject({
    version: 6,
    email: 'next@example.test',
  });
  await vi.waitFor(() =>
    expect(w.text() + document.body.textContent).toContain(
      'Część zmian zapisano',
    ),
  );
  expect(w.text() + document.body.textContent).toContain('Dane zmieniły się');
  expect(
    (w.get('input[name="current_password"]').element as HTMLInputElement).value,
  ).toBe('');
  await flushPromises();
  expect(writes).toHaveLength(2);
});

async function cropImageFile() {
  const canvas = document.createElement('canvas');
  canvas.width = 600;
  canvas.height = 300;
  const context = canvas.getContext('2d')!;
  context.fillStyle = '#ff0000';
  context.fillRect(0, 0, 300, 300);
  context.fillStyle = '#0000ff';
  context.fillRect(300, 0, 300, 300);
  const blob = await new Promise<Blob>((resolve) =>
    canvas.toBlob((value) => resolve(value!), 'image/png'),
  );
  return new File([blob], 'profile.png', { type: 'image/png' });
}
it('account avatar card crops and saves independently without clearing other card drafts or replaying removal', async () => {
  currentProfile.has_avatar = true;
  const w = mount(AuthAccount, { props: { accountId: id } });
  await loaded(w);
  await w.get('input[name="name"]').setValue('Changed name');
  await w.get('input[name="password"]').setValue('unsaved-new-password');
  w.getComponent(FileInput).vm.$emit('update:modelValue', [
    await cropImageFile(),
  ]);
  const dialog = w
    .findAllComponents(Dialog)
    .find((dialog) => dialog.props('title') === 'Kadrowanie zdjęcia')!;
  await vi.waitFor(() =>
    expect(w.getComponent(AuthAvatarCropper).find('canvas').exists()).toBe(
      true,
    ),
  );
  const apply = dialog
    .findAllComponents(Button)
    .find((button) => button.text() === 'Zastosuj kadr')!;
  await vi.waitFor(() => expect(apply.attributes('disabled')).toBeUndefined());
  expect(writes).toHaveLength(0);
  await apply.trigger('click');
  await vi.waitFor(() => expect(dialog.props('modelValue')).toBe(false));
  expect(writes).toHaveLength(0);
  write = () => {
    currentProfile = { ...currentProfile, version: 6 };
    return json({ accepted: true });
  };
  await w.get('[data-account-section=avatar]').trigger('submit');
  await flushPromises();
  await vi.waitFor(() => expect(writes).toHaveLength(1));
  await vi.waitFor(() =>
    expect(w.getComponent(FileInput).props('modelValue')).toHaveLength(0),
  );
  expect(writes[0]?.path).toBe('/api/runa/auth/profile/avatar');
  expect(writes[0]?.body.version).toBe('5');
  expect(writes[0]?.body.avatar).toBeInstanceOf(File);
  expect(
    (w.get('input[name="password"]').element as HTMLInputElement).value,
  ).toBe('unsaved-new-password');
  expect((w.get('input[name="name"]').element as HTMLInputElement).value).toBe(
    'Changed name',
  );
  expect(
    document.querySelectorAll('.app-toast [data-tone="success"]').length,
  ).toBeGreaterThan(0);
});
it('account password card submits independently of invalid account details', async () => {
  const w = mount(AuthAccount, { props: { accountId: id } });
  await loaded(w);
  await w
    .get('[data-account-security]')
    .findAll('button')
    .find((button) => button.text() === 'Skonfiguruj')!
    .trigger('click');
  await w.get('input[name="name"]').setValue('');
  await w.get('input[name="email"]').setValue('invalid');
  const form = w.get('[data-account-section=password]');
  await form.get('[name=current_password]').setValue('a-long-test-password');
  await form.get('[name=password]').setValue('a-new-long-password');
  await form
    .get('[name=password_confirmation]')
    .setValue('a-new-long-password');
  await form.trigger('submit');
  await flushPromises();
  expect(writes.map((item) => item.path)).toEqual([
    '/api/runa/auth/password/change',
  ]);
  await vi.waitFor(() =>
    expect(
      (form.get('[name=password]').element as HTMLInputElement).value,
    ).toBe(''),
  );
  expect((w.get('input[name="email"]').element as HTMLInputElement).value).toBe(
    'invalid',
  );
});
it('avatar cropper exports the selected square and refuses invalid or oversized originals', async () => {
  const w = mount(AuthAvatarCropper, {
    props: { file: await cropImageFile(), locale: 'pl', maxBytes: 2048 * 1024 },
  });
  await vi.waitFor(() => expect(w.find('canvas').exists()).toBe(true));
  expect(w.findAll('[role=slider]')).toHaveLength(0);
  for (let i = 0; i < 5; i++)
    await w
      .get('canvas')
      .trigger('keydown', { key: 'ArrowRight', shiftKey: true });
  const output = await w.vm.crop();
  const image = await createImageBitmap(output);
  expect([image.width, image.height]).toEqual([512, 512]);
  const canvas = document.createElement('canvas');
  canvas.width = canvas.height = 1;
  canvas.getContext('2d')!.drawImage(image, 0, 0, 1, 1);
  expect(
    Array.from(canvas.getContext('2d')!.getImageData(0, 0, 1, 1).data),
  ).toEqual([0, 0, 255, 255]);
  image.close();
  await w.setProps({
    file: new File(['bad'], 'bad.png', { type: 'image/png' }),
  });
  await vi.waitFor(() =>
    expect(
      document.querySelector('.app-toast [data-tone=danger]'),
    ).not.toBeNull(),
  );
  await expect(w.vm.crop()).rejects.toThrow();
  await w.setProps({
    file: new File([new Uint8Array(21 * 1024 * 1024)], 'big.png', {
      type: 'image/png',
    }),
  });
  expect(document.querySelector('.app-toast')?.textContent).toContain('20 MB');
  expect(w.find('canvas').exists()).toBe(false);
});

it('avatar crop modal cancels without upload and preserves an accepted crop when readjustment is canceled', async () => {
  const w = mount(AuthAccount, { props: { accountId: id } });
  await loaded(w);
  const picker = w.getComponent(FileInput);
  picker.vm.$emit('update:modelValue', [await cropImageFile()]);
  await flushPromises();
  let dialog = w.getComponent(Dialog);
  await vi.waitFor(() =>
    expect(w.getComponent(AuthAvatarCropper).find('canvas').exists()).toBe(
      true,
    ),
  );
  dialog.vm.$emit('update:modelValue', false);
  await flushPromises();
  expect(picker.props('modelValue')).toHaveLength(0);
  expect(writes).toHaveLength(0);
  picker.vm.$emit('update:modelValue', [await cropImageFile()]);
  await flushPromises();
  dialog = w.getComponent(Dialog);
  const apply = dialog
    .findAllComponents(Button)
    .find((button) => button.text() === 'Zastosuj kadr')!;
  await vi.waitFor(() => expect(apply.attributes('disabled')).toBeUndefined());
  await apply.trigger('click');
  await vi.waitFor(() => expect(dialog.props('modelValue')).toBe(false));
  const preview = w.get('[data-account-section=avatar] img').attributes('src');
  await w
    .findAll('button')
    .find((button) => button.text() === 'Popraw kadr')!
    .trigger('click');
  dialog.vm.$emit('update:modelValue', false);
  await flushPromises();
  expect(w.get('[data-account-section=avatar] img').attributes('src')).toBe(
    preview,
  );
  expect(writes).toHaveLength(0);
});
it('avatar crop supports wheel zoom and keeps the dimmed preview out of exported pixels', async () => {
  const w = mount(AuthAvatarCropper, {
    props: { file: await cropImageFile(), locale: 'pl', maxBytes: 2048 * 1024 },
  });
  await vi.waitFor(() => expect(w.find('canvas').exists()).toBe(true));
  const view = w.get('canvas');
  const bytesBefore = await (await w.vm.crop()).arrayBuffer();
  await view.trigger('wheel', { deltaY: -100 });
  const bytesAfter = await (await w.vm.crop()).arrayBuffer();
  expect(bytesAfter.byteLength).toBeGreaterThan(0);
  expect(new Uint8Array(bytesAfter)).not.toEqual(new Uint8Array(bytesBefore));
  const bitmap = await createImageBitmap(await w.vm.crop());
  const canvas = document.createElement('canvas');
  canvas.width = canvas.height = 512;
  canvas.getContext('2d')!.drawImage(bitmap, 0, 0);
  const corner = canvas.getContext('2d')!.getImageData(0, 0, 1, 1).data;
  expect(Array.from(corner)).toEqual([255, 0, 0, 255]);
  bitmap.close();
  const previewCorner = (view.element as HTMLCanvasElement)
    .getContext('2d')!
    .getImageData(0, 0, 1, 1).data;
  expect(previewCorner[0]).toBeLessThan(150);
  await view.trigger('touchstart', {
    touches: [
      new Touch({
        identifier: 1,
        target: view.element,
        clientX: 100,
        clientY: 100,
      }),
      new Touch({
        identifier: 2,
        target: view.element,
        clientX: 200,
        clientY: 100,
      }),
    ],
  });
  await view.trigger('touchmove', {
    touches: [
      new Touch({
        identifier: 1,
        target: view.element,
        clientX: 50,
        clientY: 100,
      }),
      new Touch({
        identifier: 2,
        target: view.element,
        clientX: 250,
        clientY: 100,
      }),
    ],
  });
  expect(new Uint8Array(await (await w.vm.crop()).arrayBuffer())).not.toEqual(
    new Uint8Array(bytesAfter),
  );
});

it('shell avatar follows guarded profile reads immediately on replacement/removal and rejects stale or foreign presentation', async () => {
  currentProfile.has_avatar = true;
  const w = mount(AuthApplication);
  await vi.waitFor(() =>
    expect(w.findComponent(ApplicationLayout).exists()).toBe(true),
  );
  const shell = w.getComponent(ApplicationLayout);
  await vi.waitFor(() => expect(shell.props('userAvatar')).toContain('v=5'));
  await w.get('button[aria-label^="Menu użytkownika"]').trigger('click');
  await vi.waitFor(() => expect(w.findAllComponents(Avatar)).toHaveLength(2));
  expect(
    w
      .findAllComponents(Avatar)
      .every((avatar) => avatar.props('src')?.includes('v=5')),
  ).toBe(true);
  currentProfile = {
    ...currentProfile,
    version: 6,
    display_name: 'Updated identity',
  };
  await request('/runa/auth/profile');
  await flushPromises();
  expect(shell.props('userAvatar')).toContain('v=6');
  expect(shell.props('userName')).toBe('Updated identity');
  expect(
    w
      .findAllComponents(Avatar)
      .every((avatar) => avatar.props('src')?.includes('v=6')),
  ).toBe(true);
  currentProfile = { ...currentProfile, version: 4, has_avatar: false };
  await request('/runa/auth/profile');
  await flushPromises();
  expect(shell.props('userAvatar')).toContain('v=6');
  currentProfile = { ...currentProfile, public_id: other, version: 9 };
  await request('/runa/auth/profile');
  await flushPromises();
  expect(shell.props('userName')).toBe('Updated identity');
  currentProfile = {
    ...currentProfile,
    public_id: id,
    version: 7,
    has_avatar: false,
  };
  await request('/runa/auth/profile');
  await flushPromises();
  expect(shell.props('userAvatar')).toBe('');
  expect(
    w.findAllComponents(Avatar).every((avatar) => avatar.props('src') === ''),
  ).toBe(true);
});
it('shared avatar falls back on image errors and retries only when its source changes', async () => {
  const w = mount(Avatar, {
    props: { src: '/missing-avatar-one.png', initials: 'TU', size: 40 },
  });
  await w.get('img').trigger('error');
  expect(w.find('img').exists()).toBe(false);
  expect(w.text()).toBe('TU');
  await w.setProps({ src: '/missing-avatar-two.png' });
  expect(w.get('img').attributes('src')).toBe('/missing-avatar-two.png');
  expect(w.get('img').attributes('alt')).toBe('');
});
it('session profile presentation clears on suspension instead of retaining the previous account image', async () => {
  currentProfile.has_avatar = true;
  const { state } = mountSession();
  await vi.waitFor(() => expect(state.profile.value?.has_avatar).toBe(true));
  signedIn = false;
  await expect(request('/runa/auth/session')).rejects.toBeDefined();
  expect(state.paused.value).toBe(true);
  expect(state.profile.value).toBeUndefined();
});
it('optional shell profile read never blocks the session and cannot restore presentation after disposal', async () => {
  const implementation = vi.mocked(window.fetch).getMockImplementation()!;
  let finish!: (value: Response) => void;
  vi.mocked(window.fetch).mockImplementation((input, init) =>
    String(input).endsWith('/profile')
      ? new Promise<Response>((resolve) => {
          finish = resolve;
        })
      : implementation(input, init),
  );
  const { state, wrapper } = mountSession();
  await vi.waitFor(() => expect(state.loading.value).toBe(false));
  expect(state.session.value?.public_id).toBe(id);
  expect(state.profile.value).toBeUndefined();
  wrapper.unmount();
  finish(json({ ...currentProfile, has_avatar: true }));
  await flushPromises();
  expect(state.profile.value).toBeUndefined();
});

it('avatar removal requires confirmation, cancel preserves the image and confirmation submits only deletion', async () => {
  currentProfile.has_avatar = true;
  const w = mount(AuthAccount, { props: { accountId: id } });
  await loaded(w);
  const remove = () =>
    w
      .get('[data-account-section=avatar]')
      .findAll('button')
      .find((button) => button.text() === 'Usuń avatar')!;
  await remove().trigger('click');
  const dialog = w.getComponent(ConfirmDialog);
  expect(dialog.props('modelValue')).toBe(true);
  expect(writes).toHaveLength(0);
  dialog.vm.$emit('update:modelValue', false);
  await flushPromises();
  expect(currentProfile.has_avatar).toBe(true);
  expect(writes).toHaveLength(0);
  await remove().trigger('click');
  write = () => {
    currentProfile = { ...currentProfile, has_avatar: false, version: 6 };
    return json({ accepted: true });
  };
  dialog.vm.$emit('confirm');
  await flushPromises();
  expect(writes).toHaveLength(1);
  expect(writes[0]?.path).toContain('/profile/avatar');
  await vi.waitFor(() => expect(dialog.props('modelValue')).toBe(false));
  expect(currentProfile.has_avatar).toBe(false);
  expect(
    vi
      .mocked(window.fetch)
      .mock.calls.find(
        ([path, init]) =>
          String(path).endsWith('/profile/avatar') && init?.method === 'DELETE',
      ),
  ).toBeDefined();
  await vi.waitFor(() =>
    expect(w.find('[data-account-section=avatar]').text()).not.toContain(
      'Usuń avatar',
    ),
  );
});

it('two-factor method controls open isolated email setup dialogs and cancel private drafts', async () => {
  const w = mount(AuthAccount, {
    props: { accountId: id },
    attachTo: document.body,
  });
  await loaded(w);
  const card = w.get('[data-account-security]');
  expect(card.find('form').exists()).toBe(false);
  expect(card.find('[name=current_password]').exists()).toBe(false);
  await card
    .findAll('button')
    .filter((button) => button.text() === 'Skonfiguruj')[0]!
    .trigger('click');
  let form = new DOMWrapper(document.body).get(
    '[data-account-section=security]',
  );
  await form
    .get('[name=current_password]')
    .setValue('discard-this-security-draft');
  await form
    .findAll('button')
    .find((button) => button.text() === 'Anuluj')!
    .trigger('click');
  expect(
    new DOMWrapper(document.body)
      .find('[data-account-section=security]')
      .exists(),
  ).toBe(false);
  expect(writes).toHaveLength(0);
  await card
    .findAll('button')
    .filter((button) => button.text() === 'Skonfiguruj')[1]!
    .trigger('click');
  form = new DOMWrapper(document.body).get('[data-account-section=security]');
  expect(
    (form.get('[name=current_password]').element as HTMLInputElement).value,
  ).toBe('');
  await form.get('[name=current_password]').setValue('a-long-test-password');
  await form.trigger('submit');
  await vi.waitFor(() =>
    expect(writes.map((item) => item.path)).toEqual([
      '/api/runa/auth/security/email-enable',
    ]),
  );
});

it('failed avatar deletion keeps confirmation open and reports one modal error before an explicit retry', async () => {
  currentProfile.has_avatar = true;
  const w = mount(AuthAccount, { props: { accountId: id } });
  await loaded(w);
  await w
    .get('[data-account-section=avatar]')
    .findAll('button')
    .find((button) => button.text() === 'Usuń avatar')!
    .trigger('click');
  const dialog = w.getComponent(ConfirmDialog);
  write = () => json('unexpected', 500);
  dialog.vm.$emit('confirm');
  await vi.waitFor(() =>
    expect(
      document.querySelectorAll('.app-toast > .app-alert[data-tone=danger]'),
    ).toHaveLength(1),
  );
  expect(dialog.props('modelValue')).toBe(true);
  expect(currentProfile.has_avatar).toBe(true);
  expect(writes).toHaveLength(1);
  write = () => {
    currentProfile = { ...currentProfile, has_avatar: false, version: 6 };
    return json({ accepted: true });
  };
  dialog.vm.$emit('confirm');
  await vi.waitFor(() => expect(dialog.props('modelValue')).toBe(false));
  expect(writes).toHaveLength(2);
});
it('two-factor disable acts on the chosen enrolled method and retains current-factor proof', async () => {
  currentSecurity = { ...security, enrolled: ['totp', 'email'] };
  const w = mount(AuthAccount, { props: { accountId: id } });
  await loaded(w);
  const card = w.get('[data-account-security]');
  await card
    .findAll('button')
    .filter((button) => button.text() === 'Wyłącz')[1]!
    .trigger('click');
  const form = new DOMWrapper(document.body).get(
    '[data-account-section=security]',
  );
  await form.get('[name=current_password]').setValue('a-long-test-password');
  await form.get('[name=code]').setValue('123456');
  await form.trigger('submit');
  await vi.waitFor(() => expect(writes).toHaveLength(1));
  expect(writes[0]).toMatchObject({
    path: '/api/runa/auth/security/disable',
    body: {
      target: 'email',
      code: '123456',
      current_password: 'a-long-test-password',
    },
  });
});

it('TOTP QR accepts only bounded local image data and cancellation clears both enrollment values', async () => {
  for (const image of [
    'https://qr.example.test/render?secret=private',
    'data:text/html;base64,PHNjcmlwdD4=',
    'data:image/svg+xml;base64,' + 'A'.repeat(131072),
  ])
    expect(() => enrollmentQrCode(image)).toThrow();
  const w = mount(AuthAccount, { props: { accountId: id } });
  await loaded(w);
  const card = w.get('[data-account-security]');
  await card
    .findAll('button')
    .find((button) => button.text() === 'Skonfiguruj')!
    .trigger('click');
  const form = new DOMWrapper(document.body).get(
    '[data-account-section=security]',
  );
  write = () =>
    json({
      secret: 'ABCDEFGHIJKLMNOP',
      qr_code: 'data:image/svg+xml;base64,PHN2Zy8+',
    });
  await form.get('[name=current_password]').setValue('a-long-test-password');
  await form.trigger('submit');
  await vi.waitFor(() =>
    expect(
      new DOMWrapper(document.body).find('[data-enrollment-qr]').exists(),
    ).toBe(true),
  );
  await form
    .findAll('button')
    .find((button) => button.text() === 'Anuluj')!
    .trigger('click');
  expect(
    new DOMWrapper(document.body).find('[data-enrollment-qr]').exists(),
  ).toBe(false);
  expect(
    new DOMWrapper(document.body).find('[data-enrollment-secret]').exists(),
  ).toBe(false);
  expect(w.getComponent(AuthAccountEditor).props('account').qrCode.value).toBe(
    '',
  );
  expect(writes).toHaveLength(1);
  const state = w.getComponent(AuthAccountEditor).props('account');
  state.secret.value = 'ABCDEFGHIJKLMNOP';
  state.qrCode.value = 'data:image/svg+xml;base64,PHN2Zy8+';
  w.unmount();
  expect(state.qrCode.value).toBe('');
});

it('recovery-code replacement uses a guarded modal and requires explicit saved acknowledgement', async () => {
  currentSecurity = { ...security, enrolled: ['totp'] };
  const w = mount(AuthAccount, { props: { accountId: id } });
  await loaded(w);
  const card = w.get('[data-account-security]');
  await card
    .findAll('button')
    .find((button) => button.text() === 'Wygeneruj nowe kody odzyskiwania')!
    .trigger('click');
  const form = new DOMWrapper(document.body).get(
    '[data-account-section=security]',
  );
  write = () => json({ recovery_codes: ['c'.repeat(32), 'd'.repeat(32)] });
  await form.get('[name=current_password]').setValue('a-long-test-password');
  await form.get('[name=code]').setValue('123456');
  await form.trigger('submit');
  await vi.waitFor(() =>
    expect(document.querySelectorAll('[data-recovery-code]')).toHaveLength(2),
  );
  const modal = w.getComponent(AuthRecoveryCodesDialog);
  await vi.waitFor(() => expect(modal.props('busy')).toBe(false));
  const dialog = modal.getComponent(Dialog);
  await vi.waitFor(() =>
    expect(
      document.activeElement?.hasAttribute('data-recovery-acknowledge'),
    ).toBe(true),
  );
  expect(dialog.props()).toMatchObject({
    modelValue: true,
    closable: false,
    closeOnEscape: false,
    closeOnOutside: false,
  });
  expect(document.querySelector('[role=dialog]')?.textContent).toContain(
    'Zapisz kody odzyskiwania w bezpiecznym miejscu.',
  );
  document.dispatchEvent(
    new KeyboardEvent('keydown', { key: 'Escape', bubbles: true }),
  );
  await flushPromises();
  expect(modal.props('codes')).toHaveLength(2);
  expect(writes.map((item) => item.path)).toEqual([
    '/api/runa/auth/security/recovery-rotate',
  ]);
  await modal.getComponent(Button).trigger('click');
  await vi.waitFor(() => expect(modal.props('codes')).toHaveLength(0));
});

it('secondary additions validate locally and preserve unrelated identity, password and avatar drafts', async () => {
  currentProfile.capabilities.secondary_emails_enabled = true;
  Object.assign(currentProfile.capabilities, {
    secondary_self_add: true,
    secondary_self_change: true,
    secondary_self_remove: true,
  });
  const w = mount(AuthAccount, { props: { accountId: id } });
  await loaded(w);
  await w
    .get('[data-account-section=details] [name=name]')
    .setValue('Unsaved name');
  await w
    .get('[data-account-section=details] [name=email]')
    .setValue('unsaved-primary@example.test');
  await w
    .get('[data-account-section=password] [name=password]')
    .setValue('unsaved-password-draft');
  const controller = w.getComponent(AuthAccountEditor).props('account');
  const image = new File(['pending'], 'avatar.png', { type: 'image/png' });
  controller.files.value = [image];
  const form = w.get('[data-account-section=secondary]');
  await form.get('[name=secondary_email]').setValue('invalid');
  await form.trigger('submit');
  await flushPromises();
  expect(writes).toHaveLength(0);
  expect(form.get('[name=secondary_email]').attributes('aria-invalid')).toBe(
    'true',
  );
  await form
    .get('[name=secondary_email]')
    .setValue('notifications@example.test');
  let finish: (() => void) | undefined;
  write = () =>
    new Promise<Response>((resolve) => {
      finish = () => {
        Object.assign(currentProfile, {
          version: 6,
          secondary_emails: [
            {
              public_id: other,
              email: 'notifications@example.test',
              verified_at: null,
            },
          ],
        });
        resolve(json({ accepted: true }));
      };
    });
  await form.trigger('submit');
  await vi.waitFor(() => expect(finish).toBeDefined());
  expect(
    (
      w.get('[data-account-section=details] [name=email]')
        .element as HTMLInputElement
    ).value,
  ).toBe('unsaved-primary@example.test');
  finish!();
  await vi.waitFor(() =>
    expect(w.find('[data-secondary-address]').exists()).toBe(true),
  );
  expect(writes[0]).toMatchObject({
    path: '/api/runa/auth/profile/secondary-emails',
    body: { email: 'notifications@example.test', version: 5 },
  });
  expect(
    (
      w.get('[data-account-section=details] [name=name]')
        .element as HTMLInputElement
    ).value,
  ).toBe('Unsaved name');
  expect(
    (
      w.get('[data-account-section=details] [name=email]')
        .element as HTMLInputElement
    ).value,
  ).toBe('unsaved-primary@example.test');
  expect(
    (
      w.get('[data-account-section=password] [name=password]')
        .element as HTMLInputElement
    ).value,
  ).toBe('unsaved-password-draft');
  expect(controller.files.value).toHaveLength(1);
  expect(controller.files.value[0]).toBe(image);
});
it('secondary address controls honor feature permissions, limit and verified state', async () => {
  currentProfile.capabilities.secondary_emails_enabled = true;
  Object.assign(currentProfile.capabilities, {
    secondary_self_add: true,
    secondary_self_change: true,
    secondary_self_remove: false,
    secondary_email_limit: 1,
  });
  Object.assign(currentProfile, {
    secondary_emails: [
      {
        public_id: other,
        email: 'verified@example.test',
        verified_at: '2026-10-24T12:00:00Z',
      },
    ],
  });
  const w = mount(AuthAccount, { props: { accountId: id } });
  await loaded(w);
  const section = w.get('[data-secondary-emails]');
  expect(section.text()).toContain('Potwierdzony');
  expect(section.find('[data-account-section=secondary]').exists()).toBe(false);
  expect(section.text()).not.toContain('Wyślij ponownie');
  expect(section.text()).not.toContain('Usuń');
  await section
    .get('[aria-label="Edytuj adres: verified@example.test"]')
    .trigger('click');
  expect(section.find('[data-account-section=secondary]').exists()).toBe(true);
});
it('secondary verification reissue preserves an unsent new-address draft and uses the existing address identity', async () => {
  currentProfile.capabilities.secondary_emails_enabled = true;
  Object.assign(currentProfile.capabilities, {
    secondary_self_add: true,
    secondary_self_change: true,
    secondary_self_remove: true,
  });
  Object.assign(currentProfile, {
    secondary_emails: [
      { public_id: other, email: 'pending@example.test', verified_at: null },
    ],
  });
  const w = mount(AuthAccount, { props: { accountId: id } });
  await loaded(w);
  await w.get('[name=secondary_email]').setValue('unsent@example.test');
  await w
    .get(
      '[aria-label="Wyślij ponownie potwierdzenie na: pending@example.test"]',
    )
    .trigger('click');
  await vi.waitFor(() => expect(writes).toHaveLength(1));
  expect(writes[0]?.body).toMatchObject({
    address_id: other,
    email: 'pending@example.test',
    version: 5,
  });
  expect(
    (w.get('[name=secondary_email]').element as HTMLInputElement).value,
  ).toBe('unsent@example.test');
});

it('secondary address conflicts keep both drafts and never replay the write automatically', async () => {
  currentProfile.capabilities.secondary_emails_enabled = true;
  Object.assign(currentProfile.capabilities, { secondary_self_add: true });
  const w = mount(AuthAccount, { props: { accountId: id } });
  await loaded(w);
  await w
    .get('[data-account-section=details] [name=name]')
    .setValue('Unsaved identity');
  await w.get('[name=secondary_email]').setValue('pending@example.test');
  write = () => json('conflict', 409);
  await w.get('[data-account-section=secondary]').trigger('submit');
  await vi.waitFor(() =>
    expect(
      w.getComponent(AuthAccountEditor).props('account').failure.value,
    ).toBe('conflict'),
  );
  expect(writes).toHaveLength(1);
  expect(
    (w.get('[name=secondary_email]').element as HTMLInputElement).value,
  ).toBe('pending@example.test');
  expect((w.get('[name=name]').element as HTMLInputElement).value).toBe(
    'Unsaved identity',
  );
});
it('secondary deletion failure stays in its confirmation modal with one error and preserves the address', async () => {
  currentProfile.capabilities.secondary_emails_enabled = true;
  Object.assign(currentProfile.capabilities, {
    secondary_self_add: true,
    secondary_self_remove: true,
  });
  Object.assign(currentProfile, {
    secondary_emails: [
      { public_id: other, email: 'keep@example.test', verified_at: null },
    ],
  });
  const w = mount(AuthAccount, { props: { accountId: id } });
  await loaded(w);
  await w.get('[aria-label="Usuń adres: keep@example.test"]').trigger('click');
  const modal = w
    .findAllComponents(ConfirmDialog)
    .find((dialog) => dialog.props('title') === 'Usunąć dodatkowy adres?')!;
  write = () => json('unexpected', 500);
  modal.vm.$emit('confirm');
  await vi.waitFor(() =>
    expect(
      document.querySelectorAll('.app-toast > .app-alert[data-tone=danger]'),
    ).toHaveLength(1),
  );
  expect(modal.props('modelValue')).toBe(true);
  expect(writes).toHaveLength(1);
  expect(w.find('[data-secondary-address]').text()).toContain(
    'keep@example.test',
  );
});

it('account session sorting uses instants across month boundaries and concise End actions', async () => {
  const fetcher = vi.mocked(window.fetch).getMockImplementation()!;
  vi.mocked(window.fetch).mockImplementation(async (input, init) => {
    if (String(input).split('?')[0]!.endsWith('/sessions'))
      return new Response(
        JSON.stringify({
          data: [
            {
              public_id: id,
              current: true,
              authenticated_at: '2026-10-02T10:00:00Z',
              expires_at: '2026-11-01T12:00:00Z',
            },
            {
              public_id: other,
              current: false,
              authenticated_at: '2026-09-30T12:00:00+02:00',
              expires_at: '2026-10-31T12:00:00Z',
            },
          ],
          meta: { next_cursor: null },
        }),
        { headers: { 'Content-Type': 'application/json' } },
      );
    return fetcher(input, init);
  });
  const w = mount(AuthAccount, {
    props: { accountId: id },
    attachTo: document.body,
  });
  await loaded(w);
  await w
    .findAll('button')
    .find((button) => button.text() === 'Aktywne sesje')!
    .trigger('click');
  await vi.waitFor(() => expect(w.findAll('tbody tr')).toHaveLength(2));
  const end = () => w.findAll('tbody button').map((button) => button.text());
  expect(end()).toEqual(['Zakończ', 'Zakończ']);
  await w.get('th[data-column-key="created"] button').trigger('click');
  await flushPromises();
  expect(w.get('tbody tr').text()).toContain('Inna');
  expect(w.get('th[data-column-key="created"]').attributes('aria-sort')).toBe(
    'ascending',
  );
  await w.get('th[data-column-key="expires"] button').trigger('click');
  await flushPromises();
  expect(w.get('tbody tr').text()).toContain('Inna');
  await w.get('th[data-column-key="expires"] button').trigger('click');
  await flushPromises();
  expect(w.get('tbody tr').text()).toContain('Bieżąca');
});

it('trusted devices live inside two-factor settings and cannot be enabled without an enrolled factor', async () => {
  currentSecurity = { ...security, trusted_devices_enabled: true };
  const w = mount(AuthAccount, {
    props: { accountId: id },
    attachTo: document.body,
  });
  await loaded(w);
  const nav = w.get('nav[aria-label="Ustawienia konta"]');
  expect(nav.text()).not.toContain('Zaufane urządzenia');
  const card = w.get('[data-account-security]');
  const trigger = card
    .findAll('button')
    .find((button) => button.text() === 'Zaufaj tej przeglądarce')!;
  expect(trigger.attributes('disabled')).toBeDefined();
  expect(card.text()).toContain('Najpierw włącz uwierzytelnianie dwuetapowe');
  expect(card.text()).not.toContain('Zarządzaj urządzeniami');
  const state = w.getComponent(AuthAccountEditor).props('account');
  state.overviewAction.value = 'trustIssue';
  await state.saveSection('security');
  expect(writes).toHaveLength(0);
  expect(state.failure.value).toBe('unavailable');
  state.overviewAction.value = 'none';
  state.security.value = { ...state.security.value!, enrolled: ['email'] };
  await flushPromises();
  expect(trigger.attributes('disabled')).toBeUndefined();
});

it('trust issuance requires a fresh factor in its modal and leaves unrelated account drafts intact', async () => {
  currentSecurity = {
    ...security,
    enrolled: ['email'],
    trusted_devices_enabled: true,
  };
  const fetcher = vi.mocked(window.fetch).getMockImplementation()!;
  vi.mocked(window.fetch).mockImplementation(async (input, init) => {
    if (
      String(input).split('?')[0]!.endsWith('/trusted-devices') &&
      (!init?.method || init.method === 'GET')
    )
      return new Response(
        JSON.stringify({ data: [], meta: { next_cursor: null } }),
        { headers: { 'Content-Type': 'application/json' } },
      );
    return fetcher(input, init);
  });
  const w = mount(AuthAccount, {
    props: { accountId: id },
    attachTo: document.body,
  });
  await loaded(w);
  await w.get('input[name=name]').setValue('Unsaved identity');
  await w
    .get('[data-account-section=password] input[name=password]')
    .setValue('unsaved-password');
  await w
    .get('[data-trusted-devices]')
    .findAll('button')
    .find((button) => button.text() === 'Zaufaj tej przeglądarce')!
    .trigger('click');
  const form = new DOMWrapper(document.body).get(
    '[data-account-section=security]',
  );
  expect(form.element.closest('[role=dialog]')).not.toBeNull();
  expect(form.find('[name=current_password]').exists()).toBe(false);
  await form.trigger('submit');
  expect(writes).toHaveLength(0);
  await form.get('[name=code]').setValue('123456');
  await form.trigger('submit');
  await vi.waitFor(() => expect(writes).toHaveLength(1));
  expect(writes[0]).toMatchObject({
    path: '/api/runa/auth/trusted-devices',
    body: { method: 'email', code: '123456' },
  });
  await vi.waitFor(() =>
    expect(
      document.querySelector('[data-account-section=security]'),
    ).toBeNull(),
  );
  expect((w.get('input[name=name]').element as HTMLInputElement).value).toBe(
    'Unsaved identity',
  );
  expect(
    (
      w.get('[data-account-section=password] input[name=password]')
        .element as HTMLInputElement
    ).value,
  ).toBe('unsaved-password');
  const state = w.getComponent(AuthAccountEditor).props('account');
  expect(state.sectionFields.security.code).toBe('');
});

it('trusted device management keeps isolated cursor rows and confirms revocation inside a dialog', async () => {
  currentSecurity = {
    ...security,
    enrolled: ['email'],
    trusted_devices_enabled: true,
  };
  const fetcher = vi.mocked(window.fetch).getMockImplementation()!;
  let revoked = false;
  vi.mocked(window.fetch).mockImplementation(async (input, init) => {
    const url = new URL(String(input), window.location.origin);
    if (
      url.pathname.endsWith('/trusted-devices') &&
      (!init?.method || init.method === 'GET')
    )
      return new Response(
        JSON.stringify({
          data: revoked
            ? []
            : [
                {
                  public_id: url.searchParams.has('before') ? other : id,
                  created_at: '2026-10-01T12:00:00Z',
                  expires_at: '2026-11-01T12:00:00Z',
                },
              ],
          meta: {
            next_cursor:
              !revoked && !url.searchParams.has('before') ? id : null,
          },
        }),
        { headers: { 'Content-Type': 'application/json' } },
      );
    return fetcher(input, init);
  });
  const w = mount(AuthAccount, {
    props: { accountId: id },
    attachTo: document.body,
  });
  await loaded(w);
  const state = w.getComponent(AuthAccountEditor).props('account');
  state.rows.value = [{ public_id: other, current: true }];
  await w
    .get('[data-trusted-devices]')
    .findAll('button')
    .find((button) => button.text() === 'Zarządzaj urządzeniami')!
    .trigger('click');
  await vi.waitFor(() =>
    expect(document.querySelectorAll('[role=dialog] tbody tr')).toHaveLength(1),
  );
  await vi.waitFor(() => expect(state.busy.value).toBe(false));
  const body = new DOMWrapper(document.body);
  await body
    .findAll('[role=dialog] button')
    .find((button) => button.text() === 'Wczytaj starsze')!
    .trigger('click');
  await vi.waitFor(() => expect(state.trustedRows.value).toHaveLength(2));
  expect(state.rows.value).toEqual([{ public_id: other, current: true }]);
  await body.get('[role=dialog] tbody button').trigger('click');
  await vi.waitFor(() =>
    expect(document.querySelectorAll('[role=dialog]')).toHaveLength(2),
  );
  expect(writes).toHaveLength(0);
  write = () => json('csrf_token_mismatch', 419);
  const confirmation = body.findAll('[role=dialog]')[1]!;
  await confirmation
    .findAll('button')
    .find((button) => button.text() === 'Unieważnij')!
    .trigger('click');
  await vi.waitFor(() => expect(writes).toHaveLength(1));
  await vi.waitFor(() => expect(state.failure.value).toBe('csrf'));
  expect(state.trustedRows.value).toHaveLength(2);
  await vi.waitFor(() =>
    expect(
      body
        .findAll('.app-toast button')
        .some((button) => button.text() === 'Odśwież zabezpieczenie'),
    ).toBe(true),
  );
  await body
    .findAll('.app-toast button')
    .find((button) => button.text() === 'Odśwież zabezpieczenie')!
    .trigger('click');
  await flushPromises();
  expect(writes).toHaveLength(1);
  write = () => {
    revoked = true;
    return json({ revoked: true });
  };
  await confirmation
    .findAll('button')
    .find((button) => button.text() === 'Unieważnij')!
    .trigger('click');
  await vi.waitFor(() => expect(writes).toHaveLength(2));
  expect(writes[1]).toMatchObject({
    path: '/api/runa/auth/trusted-devices/revoke',
    body: { public_id: id },
  });
  await vi.waitFor(() =>
    expect(state.trustedRows.value.map((row) => row.public_id)).toEqual([
      other,
    ]),
  );
});

it('factor dialogs keep failures inside their focus boundary and never replay after CSRF renewal', async () => {
  const w = mount(AuthAccount, {
    props: { accountId: id },
    attachTo: document.body,
  });
  await loaded(w);
  const setup = w
    .get('[data-account-security]')
    .findAll('button')
    .find((button) => button.text() === 'Skonfiguruj')!;
  await setup.trigger('click');
  const body = new DOMWrapper(document.body);
  let form = body.get('[data-account-section=security]');
  expect(form.element.closest('[role=dialog]')).not.toBeNull();
  write = () => json('csrf', 419);
  await form.get('[name=current_password]').setValue('a-long-test-password');
  await form.trigger('submit');
  await vi.waitFor(() =>
    expect(
      w.getComponent(AuthAccountEditor).props('account').failure.value,
    ).toBe('csrf'),
  );
  expect(writes).toHaveLength(1);
  await vi.waitFor(() =>
    expect(
      body
        .findAll('.app-toast button')
        .some((button) => button.text() === 'Odśwież zabezpieczenie'),
    ).toBe(true),
  );
  const renewal = body
    .findAll('.app-toast button')
    .find((button) => button.text() === 'Odśwież zabezpieczenie')!;
  expect(renewal.element.closest('.dialog-backdrop')).not.toBeNull();
  await renewal.trigger('click');
  await flushPromises();
  expect(writes).toHaveLength(1);
  await form.get('[name=current_password]').setValue('discard-on-escape');
  document.dispatchEvent(
    new KeyboardEvent('keydown', { key: 'Escape', bubbles: true }),
  );
  await vi.waitFor(() =>
    expect(
      document.querySelector('[data-account-section=security]'),
    ).toBeNull(),
  );
  expect(
    w.getComponent(AuthAccountEditor).props('account').sectionFields.security
      .current_password,
  ).toBe('');
  await setup.trigger('click');
  form = body.get('[data-account-section=security]');
  expect(
    (form.get('[name=current_password]').element as HTMLInputElement).value,
  ).toBe('');
});

function preferenceDeliveryFixture() {
  let revision = 3;
  const categories = [
    {
      module: 'runa.auth',
      key: 'optional',
      names: { pl: 'Nowe logowanie', en: 'New sign-in' },
      mandatory: false,
      sensitive: false,
      enabled: true,
      recipients: ['primary'],
    },
    {
      module: 'runa.auth',
      key: 'required',
      names: { pl: 'Zmiana hasła', en: 'Password change' },
      mandatory: true,
      sensitive: true,
      enabled: true,
      recipients: ['primary'],
    },
  ];
  const fetcher = vi.mocked(window.fetch).getMockImplementation()!;
  vi.mocked(window.fetch).mockImplementation(async (input, init) => {
    if (String(input).split('?')[0]!.endsWith('/preferences')) {
      const body = JSON.parse(String(init?.body)) as Record<string, unknown>;
      if (body.operation === 'snapshot')
        return json({
          account: id,
          revision,
          legacy_claimed: true,
          values: [],
        });
      if (body.operation === 'write') {
        if (body.revision !== revision) return json('version_conflict', 409);
        revision++;
        return json({
          account: id,
          revision,
          scope: `${body.application}:${body.module}:${body.view}:${body.key}`,
          version: 1,
          value: body.value,
        });
      }
    }
    if (String(input).split('?')[0]!.endsWith('/delivery-preferences')) {
      const body = JSON.parse(String(init?.body)) as Record<string, unknown>;
      if (body.operation === 'read')
        return json({
          account: id,
          revision,
          categories: structuredClone(categories),
        });
    }
    return fetcher(input, init);
  });
  write = (path, body) => {
    if (path.endsWith('/delivery-preferences')) {
      if (body.revision !== revision) return json('version_conflict', 409);
      const category = categories.find(
        (choice) => choice.key === body.category,
      )!;
      category.enabled = body.enabled as boolean;
      category.recipients = body.recipients as string[];
      revision++;
      return json({ account: id, revision });
    }
    return json({ accepted: true });
  };
  return categories;
}
async function openPreferences(w: ReturnType<typeof mount>) {
  await loaded(w);
  await w
    .get('nav[aria-label="Ustawienia konta"]')
    .findAll('button')
    .find((button) => button.text() === 'Preferencje')!
    .trigger('click');
  await vi.waitFor(() =>
    expect(w.find('[data-delivery-category=optional]').exists()).toBe(true),
  );
}

it('preferences expose header reset and explicit language/theme options with inline delivery', async () => {
  preferenceDeliveryFixture();
  const w = mount(AuthAccount, {
    props: { accountId: id },
    attachTo: document.body,
  });
  await openPreferences(w);
  const labels = w
    .get('nav[aria-label="Ustawienia konta"]')
    .findAll('button')
    .map((button) => button.text());
  expect(labels.slice(0, 3)).toEqual([
    'Konto i zabezpieczenia',
    'Preferencje',
    'Aktywne sesje',
  ]);
  expect(w.findAll('[data-account-preferences] > section')).toHaveLength(2);
  expect(w.get('[data-account-heading] [data-preferences-reset]').text()).toBe(
    'Przywróć domyślne',
  );
  expect(
    w.get('[data-preferences-display]').findAll('input[type=radio]'),
  ).toHaveLength(5);
  expect(w.find('[data-required-delivery]').exists()).toBe(false);
  expect(w.find('[data-delivery-category=required]').exists()).toBe(false);
  expect(w.get('[data-account-preferences]').classes()).not.toContain(
    'xl:grid-cols-2',
  );
  expect(w.get('[data-preferences-display]').text()).not.toContain(
    'Motyw systemowy dopasowuje się',
  );
  expect(w.get('[data-preferences-display]').text()).not.toContain(
    'Zmiany zapisują się automatycznie',
  );
  expect(
    w
      .get('[data-delivery-category=optional]')
      .find('input[role=switch]')
      .exists(),
  ).toBe(true);
  expect(document.querySelector('[role=dialog]')).toBeNull();
  expect(writes).toHaveLength(0);
});

it('inline delivery drafts validate recipients, revert one row and preserve another unsaved row after a revisioned save', async () => {
  const categories = preferenceDeliveryFixture();
  categories.push({
    ...categories[0]!,
    key: 'another',
    names: { pl: 'Inne wiadomości', en: 'Other messages' },
    recipients: ['primary'],
  });
  currentProfile.secondary_emails = [
    {
      public_id: other,
      email: 'verified@example.test',
      verified_at: '2026-10-24T00:00:00Z',
    },
    { public_id: 'pending', email: 'pending@example.test', verified_at: null },
  ];
  const w = mount(AuthAccount, {
    props: { accountId: id },
    attachTo: document.body,
  });
  await openPreferences(w);
  const state = w.getComponent(AuthAccountPreferences).props('account');
  const row = w.get('[data-delivery-category=optional]');
  const another = w.get('[data-delivery-category=another]');
  await row.get('input[role=switch]').setValue(false);
  await another.get('input[role=switch]').setValue(false);
  expect(state.delivery.value[0]?.enabled).toBe(true);
  await row
    .findAll('button')
    .find((button) => button.text() === 'Cofnij zmiany')!
    .trigger('click');
  expect(row.attributes('data-dirty')).toBe('false');
  expect(another.attributes('data-dirty')).toBe('true');
  expect(writes).toHaveLength(0);
  const selection = row.getComponent<MultiSelectInstance>(Select);
  expect(selection.props('options')?.map((option) => option.label)).toEqual([
    'test@example.test',
    'verified@example.test',
  ]);
  selection.vm.$emit('update:modelValue', []);
  await flushPromises();
  await row.trigger('submit');
  expect(writes).toHaveLength(0);
  expect(row.text()).toContain('Wybierz co najmniej jeden adres e-mail.');
  await selection.get('button').trigger('click');
  await flushPromises();
  const chooseRecipient = async (label: string) => {
    const option = Array.from(
      document.querySelectorAll<HTMLElement>('[role=option]'),
    ).find((element) => element.textContent?.includes(label));
    expect(option).toBeDefined();
    option!.click();
    await flushPromises();
  };
  await chooseRecipient('test@example.test');
  await chooseRecipient('verified@example.test');
  expect(selection.props('modelValue')).toEqual(['primary', other]);
  expect(
    document
      .querySelector('[role=listbox]')
      ?.getAttribute('aria-multiselectable'),
  ).toBe('true');
  await selection.get('button').trigger('click');
  await row.trigger('submit');
  await vi.waitFor(() => expect(row.attributes('data-dirty')).toBe('false'));
  expect(writes).toHaveLength(1);
  expect(writes[0]).toMatchObject({
    path: '/api/runa/auth/delivery-preferences',
    body: {
      operation: 'write',
      category: 'optional',
      revision: 3,
      enabled: true,
      recipients: ['primary', other],
    },
  });
  expect(another.attributes('data-dirty')).toBe('true');
  expect(another.getComponent(Switch).props('modelValue')).toBe(false);
});

it('inline delivery conflicts refresh automatically and preserve the draft without replay', async () => {
  const categories = preferenceDeliveryFixture();
  const w = mount(AuthAccount, {
    props: { accountId: id },
    attachTo: document.body,
  });
  await openPreferences(w);
  const row = w.get('[data-delivery-category=optional]');
  await row.get('input[role=switch]').setValue(false);
  row
    .getComponent<MultiSelectInstance>(Select)
    .vm.$emit('update:modelValue', ['primary', other]);
  await flushPromises();
  categories[0]!.recipients = [other];
  write = () => json('version_conflict', 409);
  await row.trigger('submit');
  await vi.waitFor(() =>
    expect(row.find('[data-form-conflict]').exists()).toBe(true),
  );
  expect(writes).toHaveLength(0);
  expect(row.getComponent(Switch).props('modelValue')).toBe(false);
  expect(row.text()).not.toContain('Wczytaj zapisane');
  await row
    .findAll('button')
    .find((button) => button.text() === 'Zachowaj moje zmiany')!
    .trigger('click');
  expect(row.find('[data-form-conflict]').exists()).toBe(false);
  expect(row.attributes('data-dirty')).toBe('true');
  expect(writes).toHaveLength(0);
});

it('header preference reset requires confirmation and cancel never resets the account', async () => {
  const categories = preferenceDeliveryFixture();
  categories[0]!.enabled = false;
  const reset = vi
    .spyOn(accountPreferences, 'resetAll')
    .mockImplementation(async () => {
      categories[0]!.enabled = true;
    });
  const w = mount(AuthAccount, {
    props: { accountId: id },
    attachTo: document.body,
  });
  await openPreferences(w);
  const row = w.get('[data-delivery-category=optional]');
  await row.get('input[role=switch]').setValue(true);
  await w.get('[data-preferences-reset]').trigger('click');
  const body = new DOMWrapper(document.body);
  expect(body.get('[role=dialog]').text()).toContain('Zapisane układy kolumn');
  await body.get('[data-dialog-cancel]').trigger('click');
  expect(reset).not.toHaveBeenCalled();
  expect(row.attributes('data-dirty')).toBe('true');
  await w.get('[data-preferences-reset]').trigger('click');
  await body
    .findAll('[role=dialog] button')
    .find((button) => button.text() === 'Przywróć domyślne')!
    .trigger('click');
  await vi.waitFor(() => expect(reset).toHaveBeenCalledTimes(1));
  await vi.waitFor(() =>
    expect(document.querySelector('[role=dialog]')).toBeNull(),
  );
  expect(row.attributes('data-dirty')).toBe('false');
  expect(row.getComponent(Switch).props('modelValue')).toBe(true);
  const legacy = vi
    .spyOn(accountPreferences, 'reset')
    .mockResolvedValue(undefined);
  const state = w.getComponent(AuthAccountPreferences).props('account');
  state.confirmed.value = true;
  await state.resetPreferences();
  expect(legacy).toHaveBeenCalledTimes(1);
  expect(reset).toHaveBeenCalledTimes(1);
});

it('explicit display choices apply their selected value only after the shared cover and preserve cycling controls', async () => {
  const { state: display, wrapper: w } = mountController(() =>
    useDisplayPreferences({ locale: 'pl', theme: 'system' }),
  );
  display.selectPreference('theme', 'system');
  expect(display.changingPreference.value).toBe(false);
  display.selectPreference('theme', 'dark');
  expect(display.theme.value).toBe('system');
  expect(display.coverVisible.value).toBe(true);
  display.selectPreference('language', 'en');
  await display.applyPreference();
  expect(display.theme.value).toBe('dark');
  expect(display.locale.value).toBe('pl');
  await display.finishPreferenceChange();
  display.changePreference('theme', new Event('click'));
  await display.applyPreference();
  expect(display.theme.value).toBe('system');
  await display.finishPreferenceChange();
  display.selectPreference('language', 'en');
  await display.applyPreference();
  expect(display.locale.value).toBe('en');
  w.unmount();
});

it('preference categories recover from failed reads without losing their card or issuing writes', async () => {
  preferenceDeliveryFixture();
  const fetcher = vi.mocked(window.fetch).getMockImplementation()!;
  let fail = true;
  vi.mocked(window.fetch).mockImplementation(async (input, init) => {
    if (fail && String(input).endsWith('/delivery-preferences')) {
      fail = false;
      return json('unavailable', 503);
    }
    return fetcher(input, init);
  });
  const w = mount(AuthAccount, {
    props: { accountId: id },
    attachTo: document.body,
  });
  await loaded(w);
  await w
    .get('nav[aria-label="Ustawienia konta"]')
    .findAll('button')
    .find((button) => button.text() === 'Preferencje')!
    .trigger('click');
  await vi.waitFor(() =>
    expect(w.get('[data-preferences-delivery]').text()).toContain(
      'Ustawienia powiadomień są niedostępne.',
    ),
  );
  await w.get('[data-preferences-delivery] button').trigger('click');
  await vi.waitFor(() =>
    expect(w.find('[data-delivery-category=optional]').exists()).toBe(true),
  );
  expect(writes).toHaveLength(0);
});

it('account refresh merges untouched fields and preserves dirty identity, password and avatar drafts', async () => {
  const w = mount(AuthAccount, { props: { accountId: id } });
  await loaded(w);
  const state = w.getComponent(AuthAccountEditor).props('account');
  state.fields.name = 'My unsaved name';
  state.sectionFields.password.password = 'Private unsaved password';
  const file = new File(['image'], 'draft.png', { type: 'image/png' });
  state.files.value = [file];
  currentProfile = { ...currentProfile, version: 6, login: 'updated-login' };
  await state.refresh();
  expect(state.fields.name).toBe('My unsaved name');
  expect(state.fields.login).toBe('updated-login');
  expect(state.sectionFields.password.password).toBe(
    'Private unsaved password',
  );
  expect(state.files.value[0]).toBe(file);
  expect(state.profile.value?.version).toBe(6);
  expect(Object.keys(state.detailConflicts)).toHaveLength(0);
  expect(state.avatarConflict.value).toBe(true);
  expect(writes).toHaveLength(0);
});

it('overlapping identity refresh requires a local decision before a versioned save and never replays writes', async () => {
  const w = mount(AuthAccount, {
    props: { accountId: id },
    attachTo: document.body,
  });
  await loaded(w);
  const state = w.getComponent(AuthAccountEditor).props('account');
  state.fields.name = 'My name';
  currentProfile = { ...currentProfile, version: 6, name: 'Remote name' };
  window.dispatchEvent(new Event('focus'));
  await vi.waitFor(() => expect(state.profile.value?.version).toBe(6));
  await flushPromises();
  const details = w.get('[data-account-section=details]');
  expect(details.get('[data-form-conflict]').text()).toContain('Remote name');
  await details.trigger('submit');
  expect(writes).toHaveLength(0);
  await details
    .findAll('button')
    .find((button) => button.text() === 'Zachowaj moje zmiany')!
    .trigger('click');
  expect(state.fields.name).toBe('My name');
  write = (_path, body) => {
    currentProfile = { ...currentProfile, version: 7, name: String(body.name) };
    return json({ accepted: true });
  };
  await details.trigger('submit');
  await vi.waitFor(() => expect(writes).toHaveLength(1));
  expect(writes[0]!.body).toMatchObject({ version: 6, name: 'My name' });
});

it('preference refresh merges disjoint delivery changes without replacing local switches', async () => {
  const categories = preferenceDeliveryFixture();
  const w = mount(AuthAccount, {
    props: { accountId: id },
    attachTo: document.body,
  });
  await openPreferences(w);
  const row = w.get('[data-delivery-category=optional]');
  await row.get('input[role=switch]').setValue(false);
  categories[0]!.recipients = [other];
  await w.getComponent(AuthAccountPreferences).props('account').refresh();
  await flushPromises();
  expect(row.getComponent(Switch).props('modelValue')).toBe(false);
  expect(
    row.getComponent<MultiSelectInstance>(Select).props('modelValue'),
  ).toEqual([other]);
  expect(row.find('[data-form-conflict]').exists()).toBe(false);
  expect(writes).toHaveLength(0);
});

it('inline delivery rejection refreshes reads without replay or a reload action', async () => {
  preferenceDeliveryFixture();
  const w = mount(AuthAccount, {
    props: { accountId: id },
    attachTo: document.body,
  });
  await openPreferences(w);
  const row = w.get('[data-delivery-category=optional]');
  await row.get('input[role=switch]').setValue(false);
  write = () => json('version_conflict', 409);
  await row.trigger('submit');
  const state = w.getComponent(AuthAccountPreferences).props('account');
  await vi.waitFor(() => expect(state.busy.value).toBe(false));
  expect(state.failure.value).toBe('conflict');
  expect(writes).toHaveLength(1);
  expect(row.getComponent(Switch).props('modelValue')).toBe(false);
  expect(w.text()).not.toContain('Wczytaj aktualne dane');
  expect(row.find('[data-form-conflict]').exists()).toBe(false);
});

it('secondary address refresh preserves dirty email and resolves only that editor', async () => {
  currentProfile.capabilities.secondary_emails_enabled = true;
  Object.assign(currentProfile.capabilities, {
    secondary_self_add: true,
    secondary_self_change: true,
  });
  Object.assign(currentProfile, {
    secondary_emails: [
      { public_id: other, email: 'before@example.test', verified_at: null },
    ],
  });
  const w = mount(AuthAccount, { props: { accountId: id } });
  await loaded(w);
  await w
    .get('button[aria-label="Edytuj adres: before@example.test"]')
    .trigger('click');
  await w.get('[name=secondary_email]').setValue('mine@example.test');
  const state = w.getComponent(AuthAccountEditor).props('account');
  state.fields.name = 'Unsaved identity';
  Object.assign(currentProfile, {
    version: 6,
    secondary_emails: [
      { public_id: other, email: 'remote@example.test', verified_at: null },
    ],
  });
  await state.refresh();
  await flushPromises();
  const collection = w.get('[data-secondary-emails]');
  expect(collection.get('[data-form-conflict]').text()).toContain(
    'remote@example.test',
  );
  await collection.get('form').trigger('submit');
  expect(writes).toHaveLength(0);
  await collection
    .findAll('button')
    .find((button) => button.text() === 'Użyj aktualnych danych')!
    .trigger('click');
  expect(
    (w.get('[name=secondary_email]').element as HTMLInputElement).value,
  ).toBe('remote@example.test');
  expect(state.fields.name).toBe('Unsaved identity');
});

it('account refresh retains a trailing focus invalidation while an earlier read is pending', async () => {
  const w = mount(AuthAccount, { props: { accountId: id } });
  await loaded(w);
  const state = w.getComponent(AuthAccountEditor).props('account');
  state.fields.name = 'My pending draft';
  const original = vi.mocked(window.fetch).getMockImplementation()!;
  let release: ((response: Response) => void) | undefined;
  let delayed = true;
  vi.mocked(window.fetch).mockImplementation((input, init) => {
    if (delayed && String(input).endsWith('/profile')) {
      delayed = false;
      return new Promise<Response>((done) => {
        release = done;
      });
    }
    return original(input, init);
  });
  const first = state.refresh();
  await vi.waitFor(() => expect(release).toBeDefined());
  currentProfile = { ...currentProfile, version: 6, name: 'Remote update' };
  window.dispatchEvent(new Event('focus'));
  release!(json(profile));
  await first;
  await vi.waitFor(() => expect(state.profile.value?.version).toBe(6));
  expect(state.fields.name).toBe('My pending draft');
  expect(state.detailConflicts.name).toBe('Remote update');
  expect(writes).toHaveLength(0);
});

it('trusted management appears only while an active device exists and disappears after refresh', async () => {
  currentSecurity = { ...security, trusted_devices_enabled: true };
  let active = true;
  const original = vi.mocked(window.fetch).getMockImplementation()!;
  vi.mocked(window.fetch).mockImplementation((input, init) =>
    String(input).split('?')[0]!.endsWith('/trusted-devices')
      ? Promise.resolve(
          new Response(
            JSON.stringify({
              data: active
                ? [
                    {
                      public_id: other,
                      created_at: '2026-10-25T00:00:00Z',
                      expires_at: '2026-11-25T00:00:00Z',
                    },
                  ]
                : [],
              meta: { next_cursor: null },
            }),
            { headers: { 'Content-Type': 'application/json' } },
          ),
        )
      : original(input, init),
  );
  const w = mount(AuthAccount, { props: { accountId: id } });
  await loaded(w);
  const card = w.get('[data-account-security]');
  expect(card.text()).toContain('Zarządzaj urządzeniami');
  expect(card.text()).not.toContain('Zaufana przeglądarka pomija kod');
  active = false;
  await w.getComponent(AuthAccountEditor).props('account').refresh();
  await flushPromises();
  expect(card.text()).not.toContain('Zarządzaj urządzeniami');
});
