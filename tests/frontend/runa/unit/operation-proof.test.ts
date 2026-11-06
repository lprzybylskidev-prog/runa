/* eslint-disable vue/one-component-per-file -- Independent proof admission and invalidation fixtures. */
import { afterEach, expect, it, vi } from '@runa/testing/vitest';
import { enableAutoUnmount, mount } from '@runa/testing/vue';
import { defineComponent, h, ref } from 'vue';
import {
  AuthOperationProofPage,
  useOperationProof,
  providePasswordConfirmation,
} from '@/runa/auth';
import {
  Dialog,
  Button,
  TextInput,
  providePresentationVisibility,
} from '@/runa/ui';
import type { OperationProofController } from '@/runa/auth';
enableAutoUnmount(afterEach);
const reply = (data: unknown) =>
  new Response(JSON.stringify({ data }), {
    headers: { 'Content-Type': 'application/json' },
  });
it('Admin operation proof uses only native password confirmation and clears credentials without replaying a business write', async () => {
  const identity = ref<string | undefined>('account-session');
  const calls: string[] = [];
  vi.spyOn(window, 'fetch').mockImplementation(async (input) => {
    calls.push(String(input));
    return reply({ confirmed: true });
  });
  let proof!: OperationProofController;
  mount(
    defineComponent({
      setup() {
        proof = useOperationProof({
          identity: () => identity.value,
          action: () => 'runa.auth.profile.manage',
          requirement: () => 'password',
        });
        return () => h(AuthOperationProofPage, { controller: proof });
      },
    }),
  );
  await proof.begin();
  proof.password.value = 'transient-proof-password';
  await proof.confirm();
  expect(proof.confirmed.value).toBe(true);
  expect(proof.password.value).toBe('');
  expect(calls.filter((path) => path.endsWith('/step-up'))).toEqual([
    '/api/runa/auth/step-up',
  ]);
  expect(calls.some((path) => path.endsWith('/security'))).toBe(false);
  identity.value = undefined;
  expect(proof.confirmed.value).toBe(false);
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

it('proof refusals explain invalid credentials and throttling while clearing secrets', async () => {
  let reason = 'security_proof_invalid';
  let status = 422;
  vi.spyOn(window, 'fetch').mockImplementation(
    async () =>
      new Response(JSON.stringify({ error: { code: reason } }), {
        status,
        headers: { 'Content-Type': 'application/json' },
      }),
  );
  let proof!: OperationProofController;
  mount(
    defineComponent({
      setup() {
        proof = useOperationProof({
          identity: () => 'session',
          action: () => 'test',
          requirement: () => 'password',
          locale: () => 'pl',
        });
        return () => h(AuthOperationProofPage, { controller: proof });
      },
    }),
  );
  await proof.begin();
  proof.password.value = 'transient';
  await proof.confirm();
  expect(proof.error.value).toContain('Sprawdź hasło');
  expect(proof.password.value).toBe('');
  expect(proof.confirmed.value).toBe(false);
  reason = 'throttled';
  status = 429;
  proof.password.value = 'transient';
  await proof.confirm();
  expect(proof.error.value).toContain('Zbyt wiele prób');
  expect(proof.password.value).toBe('');
});

it('expired proof security renews CSRF as a read and requires another explicit confirmation', async () => {
  const calls: string[] = [];
  vi.spyOn(window, 'fetch').mockImplementation(async (input) => {
    const path = String(input);
    calls.push(path);
    return path.endsWith('/csrf')
      ? reply({ ready: true })
      : new Response(
          JSON.stringify({ error: { code: 'csrf_token_mismatch' } }),
          { status: 419, headers: { 'Content-Type': 'application/json' } },
        );
  });
  let proof!: OperationProofController;
  mount(
    defineComponent({
      setup() {
        proof = useOperationProof({
          identity: () => 'session',
          action: () => 'test',
          requirement: () => 'password',
          locale: () => 'pl',
        });
        return () => h(AuthOperationProofPage, { controller: proof });
      },
    }),
  );
  await proof.begin();
  proof.password.value = 'transient';
  await proof.confirm();
  expect(calls).toEqual(['/api/runa/auth/step-up', '/api/runa/auth/csrf']);
  expect(proof.error.value).toContain('Zabezpieczenie formularza odnowione');
  expect(proof.password.value).toBe('');
  expect(proof.confirmed.value).toBe(false);
});

it('shared password page suspends modal portals, retains drafts and never executes on cancel', async () => {
  const writes = vi.fn();
  const draft = ref('Retained draft');
  let proof!: OperationProofController;
  vi.spyOn(window, 'fetch').mockImplementation(async (input, init) => {
    if (String(input) !== '/api/runa/auth/step-up') return reply({});
    expect(JSON.parse(String(init?.body))).toEqual({
      action: 'app.fixture.save',
      current_password: 'private-password',
    });
    return reply({ confirmed: true });
  });
  const Child = defineComponent({
    setup() {
      proof = useOperationProof({
        identity: () => 'session',
        action: () => 'app.fixture.save',
        requirement: () => 'password',
      });
      return () =>
        h(
          Dialog,
          { modelValue: true, title: 'Editor' },
          {
            default: () => [
              h(TextInput, {
                modelValue: draft.value,
                label: 'Draft',
                'onUpdate:modelValue': (value: string | null) =>
                  (draft.value = value ?? ''),
              }),
              h(
                Button,
                {
                  onClick: async () => {
                    if (await proof.request()) writes();
                  },
                },
                () => 'Save fixture',
              ),
            ],
          },
        );
    },
  });
  const wrapper = mount(
    defineComponent({
      setup() {
        const host = providePasswordConfirmation();
        providePresentationVisibility(() => !host.active.value);
        return () =>
          h('div', [
            h(Child),
            host.active.value
              ? h(AuthOperationProofPage, {
                  controller: host.active.value,
                  locale: 'en',
                  onConfirmed: () => host.complete(),
                  onCancel: () => host.cancel(),
                })
              : null,
          ]);
      },
    }),
    { attachTo: document.body },
  );
  const clickSave = () =>
    [...document.querySelectorAll<HTMLButtonElement>('button')]
      .find((button) => button.textContent?.trim() === 'Save fixture')!
      .click();
  clickSave();
  await vi.waitFor(() =>
    expect(wrapper.find('input[name=current_password]').exists()).toBe(true),
  );
  expect(
    document.querySelector<HTMLElement>('.dialog-backdrop')!.style.display,
  ).toBe('none');
  expect(wrapper.find('input[name=verification_code]').exists()).toBe(false);
  await wrapper
    .get('input[name=current_password]')
    .setValue('private-password');
  await wrapper.get('form').trigger('submit');
  await vi.waitFor(() => expect(writes).toHaveBeenCalledOnce());
  expect(draft.value).toBe('Retained draft');
  expect(
    document.querySelector<HTMLElement>('.dialog-backdrop')!.style.display,
  ).not.toBe('none');
  clickSave();
  await vi.waitFor(() =>
    expect(wrapper.find('input[name=current_password]').exists()).toBe(true),
  );
  await wrapper
    .findAll('button')
    .find((button) => button.text() === 'Cancel')!
    .trigger('click');
  await vi.waitFor(() =>
    expect(wrapper.find('input[name=current_password]').exists()).toBe(false),
  );
  expect(writes).toHaveBeenCalledOnce();
  expect(draft.value).toBe('Retained draft');
});
