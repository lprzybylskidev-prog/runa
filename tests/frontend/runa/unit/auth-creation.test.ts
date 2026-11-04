import { savePreference } from '../../../../src/runa/resources/js/lib/preferences';
import { afterEach, beforeEach, expect, it, vi } from '@runa/testing/vitest';
import { enableAutoUnmount, mount } from '@runa/testing/vue';
import { computed, defineComponent, h, ref, shallowRef } from 'vue';
import AuthAdminCreation from '../../../../src/runa/modules/auth/frontend/AuthAdminCreation.vue';
import { useAdminForm } from '@/runa/admin';
import { FormNotifications } from '@/runa/ui';
import type {
  AdminFormController,
  AdminJson,
  AdminPageController,
  AdminSectionData,
} from '@/runa/admin';

enableAutoUnmount(afterEach);
beforeEach(() => savePreference('runa.locale', 'en'));
afterEach(() => {
  localStorage.removeItem('runa.locale');
  vi.restoreAllMocks();
});

function defaults(): Record<string, AdminJson> {
  return {
    email: null,
    name: null,
    first_name: null,
    surname: null,
    login: null,
    active: false,
    credential_mode: 'none',
    password: { set: false },
    password_confirmation: { set: false },
    email_verified: false,
    activate_at: null,
    deactivate_at: null,
    security_locked: false,
    attempt_locked_until: null,
    password_change_required: false,
    identifier_mode: 'email',
    password_policy: JSON.stringify({
      min_length: 12,
      max_bytes: 72,
      mixed_case: false,
      numbers: false,
      symbols: false,
    }),
    invitation_allowed: true,
    email_verification_allowed: true,
  };
}
function fixture() {
  const initial = defaults();
  const metadata = [
    'identifier_mode',
    'password_policy',
    'invitation_allowed',
    'email_verification_allowed',
  ];
  const section: AdminSectionData = {
    key: 'creation',
    title: 'Creation',
    view: 'record',
    readonly: false,
    required: false,
    component: null,
    record: null,
    diagnostic: null,
    fields: Object.entries(initial).map(([key, value]) => ({
      key,
      label: key,
      type:
        key === 'credential_mode' || key === 'identifier_mode'
          ? 'dictionary'
          : typeof value === 'boolean'
            ? 'boolean'
            : key.endsWith('_at') || key === 'attempt_locked_until'
              ? 'datetime'
              : 'text',
      readonly: metadata.includes(key),
      sensitive: ['password', 'password_confirmation'].includes(key),
      filterable: false,
      sortable: false,
      lookup: false,
      required: false,
      max_length: null,
      choices:
        key === 'credential_mode' ? ['none', 'invitation', 'password'] : [],
      multiple: false,
      component: null,
    })),
    commands: [
      {
        key: 'create',
        title: 'Create account',
        mode: 'independent',
        destructive: false,
        requirement: 'password',
        preview_required: false,
        inputs: Object.keys(initial).filter((key) => !metadata.includes(key)),
        bulk: false,
        action: 'runa.auth.users.create',
        available: true,
        intent: 'create',
      },
    ],
  };
  const page: AdminPageController = {
    state: computed(() => 'ready'),
    error: ref(),
    refresh: vi.fn(async () => {}),
    page: shallowRef({
      schema_version: 1,
      owner: 'runa.auth',
      key: 'users',
      title: 'Users',
      generation: 1,
      revision: 'page-1',
      component: null,
      sections: [section],
    }),
  };
  let form!: AdminFormController;
  const wrapper = mount(
    defineComponent({
      setup() {
        form = useAdminForm(page, { autoRead: false });
        return () => h(AuthAdminCreation, { page, section, controller: form });
      },
    }),
  );
  return {
    wrapper,
    get form() {
      return form;
    },
  };
}
const json = (data: unknown, status = 200) =>
  new Response(JSON.stringify(status === 200 ? { data } : data), {
    status,
    headers: { 'Content-Type': 'application/json' },
  });
function read(patch: Record<string, AdminJson> = {}) {
  return json({
    public_id: null,
    revision: '0',
    values: { ...defaults(), ...patch },
  });
}

it('Auth creation hides unavailable verification and clears a withdrawn option without discarding other edits', async () => {
  let allowed = true;
  vi.spyOn(globalThis, 'fetch').mockImplementation(async () =>
    read({ email_verification_allowed: allowed }),
  );
  const { wrapper, form } = fixture();
  await vi.waitFor(() =>
    expect(wrapper.find('[data-auth-creation]').exists()).toBe(true),
  );
  const verification = wrapper
    .findAll('input[type="checkbox"]')
    .find((input) =>
      input.element
        .closest('label')
        ?.textContent?.includes('I verify this email'),
    );
  expect(verification).toBeDefined();
  await verification!.setValue(true);
  await wrapper.get('input[name="email"]').setValue('draft@example.test');
  allowed = false;
  await form.refresh();
  await vi.waitFor(() =>
    expect(wrapper.text()).not.toContain('Email verification'),
  );
  expect(form.values.value[form.fieldPath('creation', 'email_verified')]).toBe(
    false,
  );
  expect(form.values.value[form.fieldPath('creation', 'email')]).toBe(
    'draft@example.test',
  );
});

it('Auth creation switches naming credentials and lifecycle choices without retaining hidden secrets', async () => {
  vi.spyOn(globalThis, 'fetch').mockImplementation(async () => read());
  const { wrapper, form } = fixture();
  await vi.waitFor(() =>
    expect(wrapper.find('[data-auth-creation]').exists()).toBe(true),
  );
  await wrapper.get('input[name="first_name"]').setValue('Ada');
  await wrapper.get('input[name="surname"]').setValue('Nowak');
  await wrapper.get('input[value="single"]').setValue();
  expect(wrapper.find('input[name="name"]').exists()).toBe(true);
  expect(wrapper.find('input[name="first_name"]').exists()).toBe(false);
  await wrapper.get('input[name="name"]').setValue('Working name');
  await wrapper.get('input[value="full"]').setValue();
  expect(
    wrapper.get<HTMLInputElement>('input[name="first_name"]').element.value,
  ).toBe('Ada');
  expect(
    wrapper.get<HTMLInputElement>('input[name="surname"]').element.value,
  ).toBe('Nowak');
  await wrapper.get('input[value="single"]').setValue();
  expect(
    wrapper.get<HTMLInputElement>('input[name="name"]').element.value,
  ).toBe('Working name');
  await wrapper.get('input[value="password"]').setValue();
  await wrapper.get('input[name="password"]').setValue('long-initial-password');
  await wrapper.get('input[value="invitation"]').setValue();
  expect(wrapper.find('input[name="password"]').exists()).toBe(false);
  expect(form.values.value[form.fieldPath('creation', 'password')]).toEqual({
    set: false,
  });
  expect(wrapper.get('[data-creation-summary]').text()).toContain(
    'verify their email',
  );
  await wrapper.get('input[value="scheduled"]').setValue();
  expect(wrapper.text()).toContain('Activation date');
  await wrapper.get('input[value="active"]').setValue();
  expect(
    form.values.value[form.fieldPath('creation', 'activate_at')],
  ).toBeNull();
  expect(wrapper.get('[data-creation-summary]').text()).toContain(
    'active immediately',
  );
});

it('Auth creation handles Enter submission and blocks known invalid fields before any request', async () => {
  const fetch = vi
    .spyOn(globalThis, 'fetch')
    .mockImplementation(async () => read());
  const { wrapper } = fixture();
  await vi.waitFor(() =>
    expect(wrapper.find('input[name="email"]').exists()).toBe(true),
  );
  await wrapper.get('form').trigger('submit');
  await vi.waitFor(() =>
    expect(wrapper.findAll('[aria-invalid="true"]').length).toBeGreaterThan(0),
  );
  await wrapper.get('input[name="first_name"]').setValue('First');
  await wrapper.get('input[name="surname"]').setValue('Last');
  await wrapper.get('input[name="email"]').setValue('person@example.test');
  await wrapper.get('input[value="password"]').setValue();
  await wrapper.get('input[name="password"]').setValue('short');
  await wrapper.get('input[name="password_confirmation"]').setValue('mismatch');
  await wrapper.get('form').trigger('submit');
  expect(wrapper.text()).toContain('Passwords must match.');
  expect(
    fetch.mock.calls.every(
      ([, init]) => !init?.method || init.method === 'GET',
    ),
  ).toBe(true);
});

it('Auth creation settles a failed initial read and retries explicitly', async () => {
  let failed = true;
  vi.spyOn(globalThis, 'fetch').mockImplementation(async () =>
    failed ? json({ error: { code: 'unavailable' } }, 503) : read(),
  );
  const { wrapper } = fixture();
  await vi.waitFor(() =>
    expect(
      wrapper
        .findAllComponents(FormNotifications)
        .some(
          (feedback) =>
            feedback.props('error') === 'Unable to load account options.',
        ),
    ).toBe(true),
  );
  expect(wrapper.find('[data-auth-creation]').exists()).toBe(false);
  failed = false;
  await wrapper.get('button').trigger('click');
  await vi.waitFor(() =>
    expect(wrapper.find('[data-auth-creation]').exists()).toBe(true),
  );
});

it.each([true, false])(
  'Auth creation requires explicit proof and submission, clears secrets and never retries writes (success=%s)',
  async (success) => {
    let writes = 0;
    vi.spyOn(globalThis, 'fetch').mockImplementation(async (input, init) => {
      const url = String(input);
      if (url.endsWith('/preview'))
        return json({
          fingerprint: 'proof-selection',
          changes: {},
          requirement: 'password',
          every_action: true,
        });
      if (url.endsWith('/step-up')) return json({ confirmed: true });
      if (url.endsWith('/commands')) {
        writes++;
        expect(JSON.parse(String(init?.body)).values.password).toBe(
          'long-initial-password',
        );
        return success
          ? json({
              status: 'committed',
              operation_id: null,
              record: { public_id: null, revision: '0', values: defaults() },
            })
          : json({ error: { code: 'identifier_conflict' } }, 409);
      }
      return read();
    });
    const { wrapper, form } = fixture();
    await vi.waitFor(() =>
      expect(wrapper.find('input[name="email"]').exists()).toBe(true),
    );
    await wrapper.get('input[name="first_name"]').setValue('First');
    await wrapper.get('input[name="surname"]').setValue('Last');
    await wrapper.get('input[name="email"]').setValue('person@example.test');
    await wrapper.get('input[value="password"]').setValue();
    for (const key of ['password', 'password_confirmation'])
      await wrapper
        .get(`input[name="${key}"]`)
        .setValue('long-initial-password');
    await wrapper.get('form').trigger('submit');
    const proof = form.proof('creation', 'create');
    await vi.waitFor(() => expect(proof.open.value).toBe(true));
    expect(writes).toBe(0);
    proof.password.value = 'admin-password';
    await proof.confirm();
    expect(proof.confirmed.value).toBe(true);
    expect(writes).toBe(0);
    await wrapper.get('form').trigger('submit');
    await vi.waitFor(() => expect(writes).toBe(1));
    await vi.waitFor(() =>
      expect(form.values.value[form.fieldPath('creation', 'password')]).toEqual(
        { set: false },
      ),
    );
    expect(form.values.value[form.fieldPath('creation', 'email')]).toBe(
      success ? null : 'person@example.test',
    );
    expect(proof.confirmed.value).toBe(false);
  },
);
