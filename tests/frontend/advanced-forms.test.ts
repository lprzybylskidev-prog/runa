import { afterEach, expect, it, vi } from 'vitest';
import { defineComponent, h, nextTick, ref } from 'vue';
import { enableAutoUnmount, mount } from '@vue/test-utils';
import {
  MaskedInput,
  Wizard,
  formatMask,
  parseMaskedInput,
  useDependentFields,
  useDirtyGuard,
  useWizard,
  usePageResolver,
  type SelectOption,
} from '@/runa/ui';
enableAutoUnmount(afterEach);
function setup<T>(factory: () => T) {
  let result!: T;
  const wrapper = mount(
    defineComponent({
      setup() {
        result = factory();
        return () => h('div');
      },
    }),
  );
  return { result, wrapper };
}
it('formats nullable partial values separately from domain validation and handles escaped and Unicode tokens', () => {
  expect(formatMask(null, '##-###')).toMatchObject({
    raw: null,
    formatted: '',
    complete: false,
  });
  expect(formatMask('12-345', '##-###')).toMatchObject({
    raw: '12345',
    formatted: '12-345',
    complete: true,
  });
  expect(formatMask('a1b23', '##-###')).toMatchObject({
    raw: '123',
    formatted: '12-3',
    complete: false,
  });
  expect(formatMask('Łą9', '\\AA A#')).toMatchObject({
    raw: 'Łą9',
    formatted: 'AŁ ą9',
    complete: true,
  });
  expect(parseMaskedInput('AŁ ą9', '\\AA A#').raw).toBe('Łą9');
  expect(() => formatMask('1', 'literal')).toThrow();
  expect(() => formatMask('1', '#\\')).toThrow();
});
it('preserves caret editing, paste, deletion over literals and composition without emitting intermediate IME text', async () => {
  const w = mount(MaskedInput, {
    attachTo: document.body,
    props: { modelValue: '12345', pattern: '##-###', label: 'Postal' },
  });
  const input = w.get('input').element;
  input.focus();
  input.setSelectionRange(3, 3);
  input.dispatchEvent(
    new InputEvent('beforeinput', {
      inputType: 'deleteContentBackward',
      bubbles: true,
      cancelable: true,
    }),
  );
  await nextTick();
  expect(w.emitted('update:modelValue')?.at(-1)).toEqual(['1345']);
  expect(input.value).toBe('13-45');
  expect(input.selectionStart).toBe(1);
  input.value = '98-765';
  input.setSelectionRange(6, 6);
  input.dispatchEvent(
    new InputEvent('input', { inputType: 'insertFromPaste', bubbles: true }),
  );
  await nextTick();
  expect(w.emitted('update:modelValue')?.at(-1)).toEqual(['98765']);
  const count = w.emitted('update:modelValue')!.length;
  input.dispatchEvent(
    new CompositionEvent('compositionstart', { bubbles: true }),
  );
  input.value = 'に';
  input.dispatchEvent(
    new InputEvent('input', { bubbles: true, isComposing: true }),
  );
  await nextTick();
  expect(w.emitted('update:modelValue')).toHaveLength(count);
  input.value = '12';
  input.dispatchEvent(
    new CompositionEvent('compositionend', { bubbles: true }),
  );
  await nextTick();
  expect(w.emitted('update:modelValue')?.at(-1)).toEqual(['12']);
  await w.setProps({ readonly: true });
  input.value = '99999';
  input.dispatchEvent(new InputEvent('input', { bubbles: true }));
  await nextTick();
  expect(w.emitted('update:modelValue')?.at(-1)).toEqual(['12']);
});
it('keeps selected-range deletion, forward deletion and external null/reset consistent', async () => {
  const w = mount(MaskedInput, {
    attachTo: document.body,
    props: { modelValue: '12345', pattern: '##-###', label: 'Postal' },
  });
  const input = w.get('input').element;
  input.focus();
  input.setSelectionRange(2, 2);
  input.dispatchEvent(
    new InputEvent('beforeinput', {
      inputType: 'deleteContentForward',
      bubbles: true,
      cancelable: true,
    }),
  );
  await nextTick();
  expect(w.emitted('update:modelValue')?.at(-1)).toEqual(['1245']);
  input.value = '15';
  input.setSelectionRange(1, 1);
  input.dispatchEvent(
    new InputEvent('input', {
      inputType: 'deleteContentBackward',
      bubbles: true,
    }),
  );
  await nextTick();
  expect(w.emitted('update:modelValue')?.at(-1)).toEqual(['15']);
  await w.setProps({ modelValue: null });
  expect(input.value).toBe('');
});
it('rejects unknown graph parents, duplicate keys and cycles', () => {
  const values = ref<Record<string, string>>({});
  for (const fields of [
    [{ key: 'a', dependsOn: ['b'] }],
    [{ key: 'a' }, { key: 'a' }],
    [
      { key: 'a', dependsOn: ['b'] },
      { key: 'b', dependsOn: ['a'] },
    ],
  ]) {
    expect(() => useDependentFields(fields, values)).toThrow();
  }
});
it('invalidates all descendants synchronously and ignores obsolete remote results', async () => {
  const values = ref<Record<string, string>>({
    country: 'pl',
    city: '',
    office: '',
  });
  const requests: {
    resolve: (options: readonly SelectOption[]) => void;
    signal: globalThis.AbortSignal;
    parent: string;
  }[] = [];
  const { result, wrapper } = setup(() =>
    useDependentFields(
      [
        {
          key: 'country',
          options: [
            { value: 'pl', label: 'Poland' },
            { value: 'de', label: 'Germany' },
          ],
        },
        {
          key: 'city',
          dependsOn: ['country'],
          load: ({ parents, signal }) =>
            new Promise((resolve) =>
              requests.push({ resolve, signal, parent: parents.country! }),
            ),
        },
        {
          key: 'office',
          dependsOn: ['city'],
          options: [{ value: 'one', label: 'One' }],
        },
      ],
      values,
    ),
  );
  await nextTick();
  values.value.country = 'de';
  await nextTick();
  expect(requests[0]!.signal.aborted).toBe(true);
  requests.at(-1)!.resolve([{ value: 'berlin', label: 'Berlin' }]);
  await nextTick();
  await nextTick();
  expect(result.state.city!.options[0]?.value).toBe('berlin');
  requests[0]!.resolve([{ value: 'warsaw', label: 'Warsaw' }]);
  await nextTick();
  await nextTick();
  expect(result.state.city!.options[0]?.value).toBe('berlin');
  values.value.city = 'berlin';
  await nextTick();
  await nextTick();
  values.value.office = 'one';
  values.value.country = 'pl';
  expect(values.value.city).toBe('');
  expect(values.value.office).toBe('');
  wrapper.unmount();
  expect(requests.at(-1)!.signal.aborted).toBe(true);
});
it('supports dependency failure, retry and explicit cancellation without resurrecting results', async () => {
  const values = ref<Record<string, string>>({ a: '' });
  let fail = true;
  const { result } = setup(() =>
    useDependentFields(
      [
        {
          key: 'a',
          load: async () => {
            if (fail) throw Error('Unavailable');
            return [{ value: 'ok', label: 'Okay' }];
          },
        },
      ],
      values,
    ),
  );
  await nextTick();
  expect(result.state.a!.error).toBe(true);
  fail = false;
  await result.retry('a');
  expect(result.state.a!.ready).toBe(true);
  result.cancel('a');
  expect(result.state.a!.ready).toBe(false);
  await result.retry('a');
  expect(result.state.a!.options[0]?.value).toBe('ok');
});
it('guards dirty navigation, deduplicates requests, supports refusal/retry and cleans unload listeners', async () => {
  const dirty = ref(true);
  let answer!: (value: boolean) => void;
  const action = vi.fn();
  const confirm = vi.fn(
    () =>
      new Promise<boolean>((resolve) => {
        answer = resolve;
      }),
  );
  const { result, wrapper } = setup(() =>
    useDirtyGuard({ dirty, confirmLeave: confirm }),
  );
  const unload = new Event('beforeunload', { cancelable: true });
  window.dispatchEvent(unload);
  expect(unload.defaultPrevented).toBe(true);
  const first = result.requestLeave(action);
  expect(await result.requestLeave(action)).toBe(false);
  answer(false);
  expect(await first).toBe(false);
  expect(action).not.toHaveBeenCalled();
  const second = result.requestLeave(action);
  answer(true);
  expect(await second).toBe(true);
  expect(action).toHaveBeenCalledTimes(1);
  dirty.value = false;
  await nextTick();
  const clean = new Event('beforeunload', { cancelable: true });
  window.dispatchEvent(clean);
  expect(clean.defaultPrevented).toBe(false);
  wrapper.unmount();
});
it('validates forward steps, permits backtracking, prevents duplicate work and revalidates all steps on finish', async () => {
  let valid = false;
  let saved = false;
  const { result } = setup(() =>
    useWizard(
      [
        { key: 'a', label: 'A' },
        { key: 'b', label: 'B' },
      ],
      {
        validate: async (key) => key !== 'a' || valid,
        submit: async () => {
          saved = true;
          return true;
        },
      },
    ),
  );
  expect(await result.next()).toBe(false);
  expect(result.index.value).toBe(0);
  valid = true;
  const move = result.next();
  expect(await result.next()).toBe(false);
  expect(await move).toBe(true);
  valid = false;
  expect(await result.finish()).toBe(false);
  expect(saved).toBe(false);
  expect(result.index.value).toBe(0);
  valid = true;
  await result.next();
  expect(await result.finish()).toBe(true);
  expect(result.complete.value).toBe(true);
  expect(await result.previous()).toBe(true);
});
it('keeps wizard submit failures retryable and focuses field errors', async () => {
  let success = false;
  const steps = [{ key: 'a', label: 'First' }];
  const { result } = setup(() =>
    useWizard(steps, { validate: () => true, submit: () => success }),
  );
  expect(await result.finish()).toBe(false);
  success = true;
  expect(await result.finish()).toBe(true);
  const w = mount(Wizard, {
    attachTo: document.body,
    props: {
      steps: [...steps, { key: 'b', label: 'Second' }],
      modelValue: 1,
      label: 'Wizard',
    },
    slots: { default: '<input aria-label="Invalid" aria-invalid="true" />' },
  });
  await w.setProps({ invalid: true, modelValue: 0 });
  await nextTick();
  expect(document.activeElement).toBe(w.get('input').element);
});
it('optionally guards resolver navigation and rejected browser-back transitions', async () => {
  const original = window.location.href;
  const originalState = window.history.state;
  let allow = true;
  const go = vi.spyOn(window.history, 'go').mockImplementation(() => {});
  const pages = [
    {
      routeKey: 'a',
      moduleKey: 'one',
      href: '/guard-a',
      title: 'A',
      serverRouteName: 'guard.a',
    },
    {
      routeKey: 'b',
      moduleKey: 'one',
      href: '/guard-b',
      title: 'B',
      serverRouteName: 'guard.b',
    },
  ];
  const { result, wrapper } = setup(() =>
    usePageResolver(pages, { beforeLeave: async () => allow }),
  );
  try {
    expect(await result.navigate('/guard-a')).toBe(true);
    allow = false;
    expect(await result.navigate('/guard-b')).toBe(false);
    expect(result.current.value?.routeKey).toBe('a');
    allow = true;
    expect(await result.navigate('/guard-b')).toBe(true);
    allow = false;
    window.dispatchEvent(
      new PopStateEvent('popstate', { state: { __runa_page_position: 1 } }),
    );
    await nextTick();
    await nextTick();
    expect(go).toHaveBeenCalledWith(1);
    expect(result.current.value?.routeKey).toBe('b');
  } finally {
    wrapper.unmount();
    go.mockRestore();
    window.history.replaceState(originalState, '', original);
  }
});

it('does not submit a wizard after its owner unmounts during validation', async () => {
  let resolve!: (valid: boolean) => void;
  const submit = vi.fn(() => true);
  const { result, wrapper } = setup(() =>
    useWizard([{ key: 'a', label: 'A' }], {
      validate: () =>
        new Promise<boolean>((done) => {
          resolve = done;
        }),
      submit,
    }),
  );
  const pending = result.finish();
  wrapper.unmount();
  resolve(true);
  expect(await pending).toBe(false);
  expect(submit).not.toHaveBeenCalled();
});
