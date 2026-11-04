/* eslint-disable vue/one-component-per-file -- Independent proof admission and invalidation fixtures. */
import { afterEach, expect, it, vi } from '@runa/testing/vitest';
import { enableAutoUnmount, mount } from '@runa/testing/vue';
import { defineComponent, h, ref } from 'vue';
import { AuthOperationProof, useOperationProof } from '@/runa/auth';
import type { OperationProofController } from '@/runa/auth';
enableAutoUnmount(afterEach);
const reply = (data: unknown) =>
  new Response(JSON.stringify({ data }), {
    headers: { 'Content-Type': 'application/json' },
  });
const security = {
  email_verified: true,
  required: 'optional',
  enrolled: ['totp'],
  totp_enabled: true,
  email_otp_enabled: true,
  recovery_codes_enabled: true,
  password_change_factor: false,
  trusted_devices_enabled: false,
};
it('Admin operation proof uses native factor admission and clears credentials without replaying a business write', async () => {
  const identity = ref<string | undefined>('account-session');
  const calls: string[] = [];
  vi.spyOn(window, 'fetch').mockImplementation(async (input) => {
    calls.push(String(input));
    return reply(
      String(input).endsWith('/security') ? security : { confirmed: true },
    );
  });
  let proof!: OperationProofController;
  mount(
    defineComponent({
      setup() {
        proof = useOperationProof({
          identity: () => identity.value,
          action: () => 'runa.auth.profile.manage',
          requirement: () => 'both',
        });
        return () => h(AuthOperationProof, { controller: proof });
      },
    }),
  );
  await proof.begin();
  expect(proof.methods.value).toEqual(['totp', 'recovery']);
  proof.password.value = 'transient-proof-password';
  proof.code.value = '123456';
  await proof.confirm();
  expect(proof.confirmed.value).toBe(true);
  expect(proof.password.value).toBe('');
  expect(proof.code.value).toBe('');
  expect(calls).toEqual(['/api/runa/auth/security', '/api/runa/auth/step-up']);
  identity.value = undefined;
  expect(proof.confirmed.value).toBe(false);
  expect(proof.methods.value).toEqual([]);
});
it('Admin operation proof fences late confirmations after account invalidation', async () => {
  const identity = ref<string | undefined>('account-session');
  let finish!: (response: Response) => void;
  vi.spyOn(window, 'fetch').mockImplementation(
    async () =>
      new Promise((resolve) => {
        finish = resolve;
      }),
  );
  let proof!: OperationProofController;
  mount(
    defineComponent({
      setup() {
        proof = useOperationProof({
          identity: () => identity.value,
          action: () => 'app.profile.save',
          requirement: () => 'password',
        });
        return () => h('div');
      },
    }),
  );
  await proof.begin();
  proof.password.value = 'transient-proof-password';
  const pending = proof.confirm();
  await vi.waitFor(() => expect(finish).toBeDefined());
  identity.value = undefined;
  finish(reply({ confirmed: true }));
  await pending;
  expect(proof.confirmed.value).toBe(false);
  expect(proof.password.value).toBe('');
});

it('factor discovery shows local loading until native methods settle', async () => {
  let finish!: (response: Response) => void;
  vi.spyOn(window, 'fetch').mockImplementation(
    async () =>
      new Promise((resolve) => {
        finish = resolve;
      }),
  );
  let proof!: OperationProofController;
  const w = mount(
    defineComponent({
      setup() {
        proof = useOperationProof({
          identity: () => 'session',
          action: () => 'runa.permissions.access.manage',
          requirement: () => 'both',
        });
        return () => h(AuthOperationProof, { controller: proof });
      },
    }),
  );
  const pending = proof.begin();
  await vi.waitFor(() =>
    expect(w.find('[data-content-loading=form]').exists()).toBe(true),
  );
  expect(w.find('input[name=current_password]').exists()).toBe(false);
  finish(reply(security));
  await pending;
  await vi.waitFor(() =>
    expect(w.find('[data-content-loading]').exists()).toBe(false),
  );
  expect(w.find('input[name=current_password]').exists()).toBe(true);
  expect(proof.methods.value).toEqual(['totp', 'recovery']);
});
