import { Button } from '@/runa/ui';
import AuthRecoveryCodesDialog from '../../modules/auth/frontend/AuthRecoveryCodesDialog.vue';
import { afterEach, beforeEach, expect, it, vi } from 'vitest';
import {
  enableAutoUnmount,
  flushPromises,
  mount,
  type VueWrapper,
} from '@vue/test-utils';
import AuthEntry from '../../modules/auth/frontend/AuthEntry.vue';
import CustomerHost from './fixtures/customization/CustomerHost.vue';
import CustomerEntry from './fixtures/customization/CustomerEntry.vue';
import CustomerMinimalLayout from './fixtures/customization/CustomerMinimalLayout.vue';
import {
  decodeOptions,
  decodeSession,
  factorMethods,
  safeReturnPath,
  takeEntryToken,
} from '../../modules/auth/frontend/entry';
import { accountPreferences } from '../../resources/js/lib/preferenceStore';

enableAutoUnmount(afterEach);
async function settled(wrapper: VueWrapper) {
  await vi.waitFor(() => {
    expect(wrapper.find('form').exists(), wrapper.text()).toBe(true);
    expect(
      wrapper
        .get('section[aria-labelledby="auth-title"]')
        .attributes('aria-busy'),
    ).toBe('false');
  });
  await flushPromises();
}
const publicId = '01900000-0000-7000-8000-000000000001';
const options = {
  public_welcome_enabled: true,
  public_registration: true,
  identifier_mode: 'email',
  remember_enabled: true,
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
  required: 'totp',
  enrolled: ['totp'],
  totp_enabled: true,
  email_otp_enabled: true,
  recovery_codes_enabled: true,
  password_change_factor: false,
  trusted_devices_enabled: false,
};
const json = (value: unknown, status = 200) =>
  new Response(JSON.stringify(value), {
    status,
    headers: { 'Content-Type': 'application/json' },
  });
let restrictions: string[] | null;
let currentSecurity: Record<string, unknown>;
let post: (
  path: string,
  body: Record<string, unknown>,
) => Response | Promise<Response>;
let calls: { path: string; body: Record<string, unknown> }[];
beforeEach(() => {
  accountPreferences.disconnect();
  localStorage.clear();
  restrictions = null;
  calls = [];
  options.public_registration = true;
  currentSecurity = { ...security };
  post = () => json({ data: { accepted: true } });
  vi.spyOn(window, 'fetch').mockImplementation(async (input, init) => {
    const path = String(input).split('?')[0]!;
    if (path.endsWith('/preferences'))
      return json({
        data: {
          account: publicId,
          revision: 0,
          legacy_claimed: false,
          values: [],
        },
      });
    if (init?.method === 'POST') {
      const body = JSON.parse(String(init.body)) as Record<string, unknown>;
      calls.push({ path, body });
      return post(path, body);
    }
    if (path.endsWith('/csrf')) {
      document.cookie = 'XSRF-TOKEN=test-csrf; path=/';
      return json({ data: { ready: true } });
    }
    if (path.endsWith('/entry-options')) return json({ data: options });
    if (path.endsWith('/security')) return json({ data: currentSecurity });
    return restrictions === null
      ? json({ error: { code: 'authentication_required' } }, 401)
      : json({
          data: {
            public_id: publicId,
            display_name: 'Test User',
            restrictions,
          },
        });
  });
});
afterEach(() => {
  accountPreferences.disconnect();
  document.cookie = 'XSRF-TOKEN=; Max-Age=0; path=/';
});

it('rejects external, encoded, API and Auth return paths and consumes fragments without a GET write', () => {
  for (const value of [
    '//evil.test',
    'https://evil.test',
    '/%2fevil',
    '/auth/login',
    '/api/private',
    '/a\\evil',
    '/a?x=1',
    '/a/../b',
  ])
    expect(safeReturnPath(value)).toBe('/app');
  expect(safeReturnPath('/projects/item-1')).toBe('/projects/item-1');
  const replaceState = vi.fn();
  expect(
    takeEntryToken(
      {
        hash: '#token=' + 'a'.repeat(64),
        pathname: '/auth/invitation',
        search: '',
      },
      { replaceState },
    ),
  ).toBe('a'.repeat(64));
  expect(replaceState).toHaveBeenCalledWith(null, '', '/auth/invitation');
  expect(
    takeEntryToken(
      { hash: '#token=invalid', pathname: '/auth/invitation', search: '' },
      { replaceState },
    ),
  ).toBe('');
});
it('fails closed on unknown restrictions and filters methods by the server requirement', () => {
  expect(() =>
    decodeSession({
      data: {
        public_id: publicId,
        display_name: 'User',
        restrictions: ['future_restriction'],
      },
    }),
  ).toThrow();
  expect(() =>
    decodeOptions({ data: { ...options, remember_enabled: 'yes' } }),
  ).toThrow();
  expect(
    factorMethods({
      ...security,
      enrolled: ['totp', 'email'],
      required: 'totp',
    }),
  ).toEqual(['totp', 'recovery']);
});
it('follows fresh restrictions through verification, factor challenge and password change', async () => {
  const w = mount(AuthEntry, {
    props: { authenticated: () => {}, page: 'login' },
    attachTo: document.body,
  });
  await settled(w);
  expect(w.get('form').attributes('novalidate')).toBeDefined();
  await w.get('[name=identifier]').setValue('test@example.test');
  await w.get('[name=password]').setValue('a-long-password');
  post = () => {
    restrictions = [
      'email_verification_required',
      'two_factor_required',
      'password_change_required',
    ];
    return json({ data: { accepted: true } });
  };
  await w.get('form').trigger('submit');
  await settled(w);
  expect(w.get('h1').text()).toBe('Potwierdź adres e-mail');
  expect(w.find('[name=password]').exists()).toBe(false);
  restrictions = ['two_factor_required', 'password_change_required'];
  await w
    .findAll('button')
    .find((b) => b.text() === 'Sprawdź stan konta')!
    .trigger('click');
  await settled(w);
  expect(w.get('h1').text()).toBe('Potwierdź drugi składnik');
  expect(w.text()).not.toContain('Kod e-mail');
  post = () => {
    restrictions = ['password_change_required'];
    return json({ data: { confirmed: true } });
  };
  await w.get('[name=code]').setValue('123456');
  await w.get('form').trigger('submit');
  await settled(w);
  expect(w.get('h1').text()).toBe('Zmień hasło');
  await w.get('[name=current_password]').setValue('a-long-password');
  await w.get('[name=password]').setValue('another-long-password');
  await w.get('[name=password_confirmation]').setValue('another-long-password');
  post = () => {
    restrictions = [];
    return json({ data: { updated: true } });
  };
  await w.get('form').trigger('submit');
  await settled(w);
  expect(w.get('h1').text()).toBe('Logowanie zakończone.');
  expect(w.get('a[href="/"]').exists()).toBe(true);
  expect(calls.map((c) => c.path)).toEqual([
    '/api/runa/auth/login',
    '/api/runa/auth/security/challenge',
    '/api/runa/auth/password/change',
  ]);
});
it('keeps CSRF recovery deliberate, clears credentials and focuses safe server errors', async () => {
  const w = mount(AuthEntry, {
    props: { authenticated: () => {}, page: 'login' },
    attachTo: document.body,
  });
  await settled(w);
  await w.get('[name=identifier]').setValue('test@example.test');
  await w.get('[name=password]').setValue('secret-password');
  post = () => json({ error: { code: 'csrf_token_mismatch' } }, 419);
  await w.get('form').trigger('submit');
  await settled(w);
  expect(w.get('[name=password]').element).toHaveProperty('value', '');
  expect(w.text() + document.body.textContent).toContain(
    'Sesja formularza wygasła',
  );
  const retry = Array.from(
    document.querySelectorAll<HTMLButtonElement>('.app-toast button'),
  ).find(
    (button) =>
      button.textContent?.trim() === 'Odśwież zabezpieczenie formularza',
  )!;
  retry.click();
  await settled(w);
  expect(calls).toHaveLength(1);
  post = () =>
    json(
      {
        error: {
          code: 'validation_failed',
          fields: { identifier: ['DO NOT RENDER UNTRUSTED TEXT'] },
        },
      },
      422,
    );
  await w.get('[name=password]').setValue('secret-password');
  await w.get('form').trigger('submit');
  await settled(w);
  expect(w.get('[name=identifier]').attributes('aria-invalid')).toBe('true');
  expect(document.activeElement).toBe(w.get('[name=identifier]').element);
  expect(w.text()).not.toContain('DO NOT RENDER');
});
it('requires explicit token submission, checks password bytes and does not persist the proof', async () => {
  const original = window.location.pathname + window.location.search;
  window.history.replaceState(null, '', original + '#token=' + 'a'.repeat(64));
  const w = mount(AuthEntry, {
    props: { authenticated: () => {}, page: 'invitation' },
  });
  await settled(w);
  expect(window.location.hash).toBe('');
  expect(calls).toHaveLength(0);
  await w.get('[name=password]').setValue('ą'.repeat(40));
  await w.get('[name=password_confirmation]').setValue('ą'.repeat(40));
  await w.get('form').trigger('submit');
  await settled(w);
  expect(calls).toHaveLength(0);
  await w.get('[name=password]').setValue('a-valid-new-password');
  await w.get('[name=password_confirmation]').setValue('a-valid-new-password');
  await w.get('form').trigger('submit');
  await settled(w);
  expect(calls[0]?.body.token).toBe('a'.repeat(64));
  expect(w.text() + document.body.textContent).toContain(
    'Operacja została zakończona',
  );
  expect(JSON.stringify({ ...localStorage, ...sessionStorage })).not.toContain(
    'a'.repeat(64),
  );
});
it.each([409, 429, 503])(
  'presents failed writes without retrying for status %s',
  async (status) => {
    const w = mount(AuthEntry, {
      props: { authenticated: () => {}, page: 'forgot-password' },
    });
    await settled(w);
    await w.get('[name=identifier]').setValue('test@example.test');
    post = () => json({ error: { code: 'auth_unavailable' } }, status);
    await w.get('form').trigger('submit');
    await settled(w);
    expect(calls).toHaveLength(1);
    expect(
      document.querySelector('.app-toast [data-tone=danger]'),
    ).not.toBeNull();
    expect(w.get('[name=identifier]').element).toHaveProperty(
      'value',
      'test@example.test',
    );
  },
);

it('shows TOTP material and recovery codes only in the current flow, with deliberate acknowledgement', async () => {
  restrictions = ['two_factor_enrollment_required', 'password_change_required'];
  currentSecurity.enrolled = [];
  const w = mount(AuthEntry, {
    props: { authenticated: () => {}, page: 'security' },
  });
  await settled(w);
  await w.get('[name=current_password]').setValue('a-long-password');
  post = () =>
    json({
      data: {
        secret: 'A'.repeat(32),
        uri: 'otpauth://totp/test',
        qr_code:
          'data:image/svg+xml;base64,' +
          btoa(
            '<svg xmlns="http://www.w3.org/2000/svg" width="256" height="256"><rect width="256" height="256" fill="white"/></svg>',
          ),
      },
    });
  await w.get('form').trigger('submit');
  await settled(w);
  expect(w.get('[data-enrollment-secret]').text()).toBe('A'.repeat(32));
  expect(w.get('[data-enrollment-qr]').attributes('src')).toMatch(
    /^data:image\/svg\+xml;base64,/,
  );
  expect(w.get('[name=current_password]').element).toHaveProperty('value', '');
  await w.get('[name=current_password]').setValue('a-long-password');
  await w.get('[name=enrollment_code]').setValue('123456');
  post = () =>
    json({ data: { updated: true, recovery_codes: ['b'.repeat(32)] } });
  await w.get('form').trigger('submit');
  await settled(w);
  expect(w.get('h1').text()).toBe('Zapisz kody odzyskiwania');
  expect(w.text() + document.body.textContent).toContain('b'.repeat(32));
  expect(w.find('[data-enrollment-secret]').exists()).toBe(false);
  expect(w.find('[data-enrollment-qr]').exists()).toBe(false);
  restrictions = ['password_change_required'];
  currentSecurity.enrolled = ['totp'];
  await w
    .getComponent(AuthRecoveryCodesDialog)
    .getComponent(Button)
    .trigger('click');
  await settled(w);
  expect(w.get('h1').text()).toBe('Zmień hasło');
  expect(w.text()).not.toContain('b'.repeat(32));
  await w.setProps({ page: 'forgot-password' });
  await settled(w);
  expect(w.get('h1').text()).toBe('Odzyskaj dostęp');
});

it('hides disabled registration and never renders a writable registration form', async () => {
  options.public_registration = false;
  const w = mount(AuthEntry, {
    props: { authenticated: () => {}, page: 'register' },
  });
  await settled(w);
  expect(w.text() + document.body.textContent).toContain(
    'Rejestracja jest wyłączona',
  );
  expect(w.find('[name=password]').exists()).toBe(false);
  expect(w.find('button[type=submit]').exists()).toBe(false);
});

it('verifies a primary address before required email enrollment, even with verification policy otherwise disabled', async () => {
  restrictions = ['two_factor_enrollment_required'];
  currentSecurity = {
    ...security,
    enrolled: [],
    required: 'email',
    email_verified: false,
  };
  const w = mount(AuthEntry, {
    props: { authenticated: () => {}, page: 'security' },
  });
  await settled(w);
  expect(w.get('h1').text()).toBe('Potwierdź adres e-mail');
  currentSecurity.email_verified = true;
  await w
    .findAll('button')
    .find((b) => b.text() === 'Sprawdź stan konta')!
    .trigger('click');
  await settled(w);
  expect(w.get('h1').text()).toBe('Skonfiguruj drugi składnik');
  expect(w.text()).not.toContain('Aplikacja uwierzytelniająca');
  await w.get('[name=current_password]').setValue('a-long-password');
  post = () => {
    restrictions = ['two_factor_required'];
    currentSecurity.enrolled = ['email'];
    return json({ data: { updated: true, recovery_codes: [] } });
  };
  await w.get('form').trigger('submit');
  await settled(w);
  expect(w.get('h1').text()).toBe('Potwierdź drugi składnik');
  expect(w.text() + document.body.textContent).toContain('Wyślij kod e-mail');
});

it('cancels old flow ownership when a pending token write settles after page navigation', async () => {
  window.history.replaceState(
    null,
    '',
    window.location.pathname +
      window.location.search +
      '#token=' +
      'a'.repeat(64),
  );
  const w = mount(AuthEntry, {
    props: { authenticated: () => {}, page: 'verify-email' },
  });
  await settled(w);
  let resolve!: (value: Response) => void;
  post = () =>
    new Promise((done) => {
      resolve = done;
    });
  await w.get('form').trigger('submit');
  await vi.waitFor(() => expect(calls).toHaveLength(1));
  await w.setProps({ page: 'forgot-password' });
  await settled(w);
  resolve(json({ data: { accepted: true } }));
  await settled(w);
  expect(w.get('h1').text()).toBe('Odzyskaj dostęp');
  expect(w.text()).not.toContain('Potwierdzenie zostało przyjęte');
});

it('customization: layout replacement retains the default entry validation and subtree defaults', async () => {
  const custom = mount(CustomerHost, {
    props: {
      mode: 'entry',
      page: 'login',
      components: { minimalLayout: CustomerMinimalLayout },
    },
  });
  await settled(custom);
  expect(custom.find('[data-customer-brand]').exists()).toBe(true);
  await custom.get('form').trigger('submit');
  expect(calls).toEqual([]);
  expect(custom.findAll('[aria-invalid="true"]').length).toBeGreaterThan(0);
  const defaults = mount(AuthEntry, { props: { page: 'login' } });
  await settled(defaults);
  expect(defaults.find('[data-customer-brand]').exists()).toBe(false);
});

it('customization: public headless entry preserves CSRF refusal, explicit retry and required verification', async () => {
  const accepted = vi.fn();
  const w = mount(CustomerHost, {
    props: {
      mode: 'entry',
      page: 'login',
      authenticated: accepted,
      components: { entry: CustomerEntry },
    },
  });
  await vi.waitFor(() => expect(w.find('form').exists()).toBe(true));
  await w.get('input[name="identifier"]').setValue('test@example.test');
  await w.get('input[name="password"]').setValue('correct horse battery');
  post = () => json({ error: { code: 'csrf_mismatch' } }, 419);
  await w.get('form').trigger('submit');
  await vi.waitFor(() => expect(w.get('[role="alert"]').text()).toBe('csrf'));
  expect(accepted).not.toHaveBeenCalled();
  const count = calls.length;
  await w
    .findAll('button')
    .find((b) => b.text() === 'Refresh CSRF')!
    .trigger('click');
  await flushPromises();
  expect(calls.length).toBe(count);
  post = () => {
    restrictions = ['email_verification_required'];
    return json({ data: { accepted: true } });
  };
  await w.get('input[name="password"]').setValue('correct horse battery');
  await w.get('form').trigger('submit');
  await vi.waitFor(() => expect(w.get('[data-screen]').text()).toBe('verify'));
  expect(accepted).not.toHaveBeenCalled();
});
