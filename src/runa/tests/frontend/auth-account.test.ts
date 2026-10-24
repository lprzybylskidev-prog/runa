import { afterEach, beforeEach, expect, it, vi } from 'vitest';
import { defineComponent } from 'vue';
import AuthAccountEditor from '../../modules/auth/frontend/AuthAccountEditor.vue';
import AuthAvatarCropper from '../../modules/auth/frontend/AuthAvatarCropper.vue';
import { Button, Dialog, FileInput, Select } from '@/runa/ui';
import CustomerHost from './fixtures/customization/CustomerHost.vue';
import CustomerAccount from './fixtures/customization/CustomerAccount.vue';
import CustomerApplication from './fixtures/customization/CustomerApplication.vue';
import { enableAutoUnmount, flushPromises, mount } from '@vue/test-utils';
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
const profile = {
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
function mountSession(navigate: (path: string) => void = vi.fn()) {
  let state!: ReturnType<typeof useApplicationSession>;
  const wrapper = mount(
    defineComponent({
      setup() {
        state = useApplicationSession(navigate);
        return () => null;
      },
    }),
  );
  return { state, wrapper };
}
async function loaded(w: ReturnType<typeof mount>) {
  await vi.waitFor(() => expect(w.find('form').exists()).toBe(true));
  await flushPromises();
}

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
  expect(w.get('header a[href="/auth/login"]').exists()).toBe(true);
  expect(w.find('main a[href="/auth/login"]').exists()).toBe(false);
  w.unmount();
  options.public_registration = true;
  const w2 = mount(AuthWelcome);
  await vi.waitFor(() =>
    expect(w2.find('a[href="/auth/register"]').exists()).toBe(true),
  );
  expect(w2.get('header a[href="/auth/register"]').exists()).toBe(true);
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

// Prepared optional-feature regressions run with the integrated final frontend selection.
it('uses secondary address identity and account revision for change and explicit removal', async () => {
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
  await w
    .findAll('button')
    .find((b) => b.text() === 'Dodatkowe adresy e-mail')!
    .trigger('click');
  await flushPromises();
  await w
    .findAll('button')
    .find((b) => b.text() === 'Zmień / ponów')!
    .trigger('click');
  await w.get('input[name="email"]').setValue('changed@example.test');
  await w.get('form').trigger('submit');
  await flushPromises();
  expect(writes[0]?.body).toMatchObject({
    address_id: other,
    email: 'changed@example.test',
    version: 5,
  });
  await w
    .findAll('button')
    .find((b) => b.text() === 'Usuń')!
    .trigger('click');
  await w.get('form').trigger('submit');
  await flushPromises();
  expect(writes).toHaveLength(1);
});
it('optional TOTP setup retains its private key only until confirmation and requires saved-code acknowledgement', async () => {
  const w = mount(AuthAccount, { props: { accountId: id } });
  await loaded(w);
  w.getComponent(Select).vm.$emit('update:modelValue', 'totpBegin');
  await flushPromises();
  write = (path) =>
    json(
      path.endsWith('/totp-begin')
        ? { secret: 'ABCDEFGHIJKLMNOP' }
        : { recovery_codes: ['a'.repeat(32)] },
    );
  await w
    .get('[data-account-section=security] input[name="current_password"]')
    .setValue('a-long-test-password');
  await w.get('[data-account-section=security]').trigger('submit');
  await flushPromises();
  expect(w.get('[data-enrollment-secret]').text()).toBe('ABCDEFGHIJKLMNOP');
  expect(
    (
      w.get('[data-account-section=security] input[name="current_password"]')
        .element as HTMLInputElement
    ).value,
  ).toBe('');
  await w
    .get('[data-account-section=security] input[name="current_password"]')
    .setValue('a-long-test-password');
  await w.get('input[name="enrollment_code"]').setValue('123456');
  await w.get('[data-account-section=security]').trigger('submit');
  await flushPromises();
  expect(w.find('[data-enrollment-secret]').exists()).toBe(false);
  expect(w.get('[data-recovery-code]').text()).toBe('a'.repeat(32));
  expect(w.find('[data-account-section=security]').exists()).toBe(false);
  await w
    .findAll('button')
    .find((b) => b.text() === 'Kody zostały zapisane')!
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
  expect(w.getComponent(AuthAccountEditor).exists()).toBe(true);
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
  expect(writes.map((item) => item.path)).toEqual([
    '/api/runa/auth/profile',
    '/api/runa/auth/profile/email',
  ]);
  expect(writes[1]?.body).toMatchObject({
    version: 6,
    email: 'next@example.test',
  });
  expect(w.text() + document.body.textContent).toContain(
    'Część zmian zapisano',
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
  const remove = w
    .findAll('label')
    .find((label) => label.text().includes('Usuń obecne zdjęcie'))!;
  await remove.get('input').setValue(true);
  await w.get('input[name="name"]').setValue('Changed name');
  await w.get('input[name="password"]').setValue('unsaved-new-password');
  w.getComponent(FileInput).vm.$emit('update:modelValue', [
    await cropImageFile(),
  ]);
  const dialog = w.getComponent(Dialog);
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
  expect((remove.get('input').element as HTMLInputElement).checked).toBe(false);
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
