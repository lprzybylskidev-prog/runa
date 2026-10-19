import { afterEach, expect, it } from 'vitest';
import { enableAutoUnmount, mount } from '@vue/test-utils';
import { nextTick } from 'vue';
import {
  Form,
  FormField,
  FormSection,
  TextInput,
  NumberInput,
  useForm,
  laravelFieldErrors,
  parseDecimal,
  serializeDecimal,
  formatDecimal,
} from '../../resources/js/runa/ui';
import { ApiError } from '../../resources/js/lib/http';
enableAutoUnmount(afterEach);
it('associates generated field labels, helper/warning/errors and native semantic types', async () => {
  const w = mount(TextInput, {
    props: {
      label: 'Email',
      modelValue: '',
      type: 'email',
      required: true,
      helper: 'Work address',
      warning: 'Check the domain',
      error: ['Invalid address', 'Another issue'],
    },
    attrs: {
      autocomplete: 'email',
      name: 'email',
      'aria-describedby': 'external',
    },
  });
  const input = w.get('input');
  expect(w.get('label').attributes('for')).toBe(input.attributes('id'));
  expect(input.attributes('type')).toBe('email');
  expect(input.attributes('required')).toBeDefined();
  expect(input.attributes('autocomplete')).toBe('email');
  expect(input.attributes('aria-invalid')).toBe('true');
  for (const id of input
    .attributes('aria-describedby')!
    .split(' ')
    .filter((id) => id !== 'external'))
    expect(w.findAll('[id]').some((node) => node.attributes('id') === id)).toBe(
      true,
    );
  await input.setValue('person@example.test');
  expect(w.emitted('update:modelValue')?.at(-1)).toEqual([
    'person@example.test',
  ]);
  await w.setProps({ error: [], warning: '', helper: '' });
  expect(input.attributes('aria-invalid')).toBeUndefined();
  expect(input.attributes('aria-describedby')).toBe('external');
});
it('supports textarea, nullable empty values, readonly and IME composition', async () => {
  const w = mount(TextInput, {
    props: {
      label: 'Notes',
      modelValue: 'Draft',
      type: 'textarea',
      nullable: true,
    },
  });
  await w.get('textarea').setValue('');
  expect(w.emitted('update:modelValue')?.at(-1)).toEqual([null]);
  const textarea = w.get('textarea').element;
  textarea.value = 'composition';
  textarea.dispatchEvent(
    new InputEvent('input', { bubbles: true, isComposing: true }),
  );
  expect(w.emitted('update:modelValue')).toHaveLength(1);
  textarea.dispatchEvent(
    new CompositionEvent('compositionend', { bubbles: true }),
  );
  expect(w.emitted('update:modelValue')?.at(-1)).toEqual(['composition']);
  await w.setProps({ readonly: true });
  await w.get('textarea').setValue('blocked');
  expect(w.emitted('update:modelValue')).toHaveLength(2);
});
it('guards busy and known-invalid submission and disables contained controls', async () => {
  const w = mount(Form, {
    props: { valid: false },
    slots: {
      default: '<input name="name">',
      actions: '<button type="submit">Save</button>',
    },
  });
  expect(w.attributes('novalidate')).toBeDefined();
  w.element.dispatchEvent(
    new SubmitEvent('submit', { bubbles: true, cancelable: true }),
  );
  await nextTick();
  expect(w.emitted('submit')).toBeUndefined();
  expect(w.emitted('invalid')).toHaveLength(1);
  await w.setProps({ valid: true, busy: true });
  expect(w.get('input').element.matches(':disabled')).toBe(true);
  w.element.dispatchEvent(
    new SubmitEvent('submit', { bubbles: true, cancelable: true }),
  );
  expect(w.emitted('submit')).toBeUndefined();
  await w.setProps({ busy: false });
  w.element.dispatchEvent(
    new SubmitEvent('submit', { bubbles: true, cancelable: true }),
  );
  expect(w.emitted('submit')).toHaveLength(1);
});
it('preserves values when a controlled form section is collapsed', async () => {
  const w = mount(FormSection, {
    props: { title: 'More', collapsible: true },
    slots: { default: '<input value="draft">' },
  });
  await w.get('button').trigger('click');
  expect(w.emitted('update:collapsed')).toEqual([[true]]);
  await w.setProps({ collapsed: true });
  expect(w.get('input').element.closest('[hidden]')).not.toBeNull();
  await w.setProps({ collapsed: false });
  expect(w.get('input').element.value).toBe('draft');
  const field = mount(FormField, {
    props: { label: 'Custom', id: 'custom' },
    slots: { default: '<input id="custom">' },
  });
  expect(field.get('label').attributes('for')).toBe('custom');
});
it('normalizes decimal strings exactly and rejects unsafe rounding, grouping and exponents', () => {
  expect(serializeDecimal('90071992547409931234567890.1234567890')).toBe(
    '90071992547409931234567890.1234567890',
  );
  expect(
    serializeDecimal('+00012,50', { locale: 'pl', kind: 'currency', scale: 2 }),
  ).toBe('12.50');
  expect(JSON.stringify({ amount: serializeDecimal('0.10') })).toBe(
    '{"amount":"0.10"}',
  );
  expect(formatDecimal('12.5000', 'pl')).toBe('12,5000');
  expect(serializeDecimal('12.5', { kind: 'percentage' })).toBe('12.5');
  expect(serializeDecimal('', { kind: 'integer' })).toBeNull();
  expect(serializeDecimal('.5')).toBe('0.5');
  for (const value of [
    '1e3',
    'NaN',
    'Infinity',
    '1 000',
    '1,000.00',
    '1.',
    '-',
    '0x10',
  ])
    expect(() => serializeDecimal(value)).toThrow(TypeError);
  expect(parseDecimal('1.000', { scale: 2 }).valid).toBe(false);
  expect(parseDecimal('1.5', { kind: 'integer' }).valid).toBe(false);
  expect(parseDecimal('1', { scale: -1 }).valid).toBe(false);
});
it('keeps incomplete numeric drafts visible and emits them instead of a stale valid number', async () => {
  const w = mount(NumberInput, {
    props: {
      label: 'Amount',
      modelValue: '12.50',
      locale: 'pl',
      scale: 2,
      invalidMessage: 'Invalid amount',
      unit: 'PLN',
    },
  });
  expect(w.get('input').element.value).toBe('12,50');
  await w.get('input').setValue('-');
  expect(w.emitted('update:modelValue')?.at(-1)).toEqual(['-']);
  expect(w.emitted('validity')?.at(-1)).toEqual([false]);
  expect(w.get('input').attributes('aria-invalid')).toBe('true');
  await w.setProps({ modelValue: '-' });
  expect(w.get('input').element.value).toBe('-');
  await w.get('input').setValue('0,10');
  expect(w.emitted('update:modelValue')?.at(-1)).toEqual(['0.10']);
  await w.setProps({ modelValue: '0.10' });
  expect(w.get('input').element.value).toBe('0,10');
  await w.get('input').trigger('blur');
  expect(w.get('input').element.value).toBe('0,10');
  expect(w.get('input').attributes('aria-invalid')).toBeUndefined();
  expect(w.get('input').attributes('aria-describedby')).toContain(
    w.get('.app-input-suffix [id]').attributes('id'),
  );
  await w.get('input').setValue('');
  expect(w.emitted('update:modelValue')?.at(-1)).toEqual([null]);
});
it('tracks nested dirty values, protects baseline ownership and resets to last confirmed values', () => {
  const initial = {
    name: 'Initial',
    nested: { email: 'a@example.test' },
    rows: ['a'],
  };
  const form = useForm(initial);
  initial.name = 'mutated externally';
  expect(form.values.value.name).toBe('Initial');
  form.values.value.nested.email = 'b@example.test';
  expect(form.dirty.value).toBe(true);
  form.markClean();
  form.values.value.rows.push('b');
  expect(form.dirty.value).toBe(true);
  form.reset();
  expect(form.dirty.value).toBe(false);
  expect(form.values.value.nested.email).toBe('b@example.test');
  expect(form.values.value.rows).toEqual(['a']);
  form.reset({
    name: 'Server',
    nested: { email: 'server@example.test' },
    rows: [],
  });
  expect(form.values.value.name).toBe('Server');
  expect(form.dirty.value).toBe(false);
});
it('prevents invalid/duplicate requests and marks only the submitted snapshot clean', async () => {
  const form = useForm({ name: '' });
  let calls = 0;
  expect(
    await form.submit(
      async () => {
        calls++;
      },
      () => ({ name: ['Required'] }),
    ),
  ).toBe(false);
  expect(calls).toBe(0);
  form.clearErrors();
  form.values.value.name = 'Saved';
  let resolve!: () => void;
  const pending = new Promise<void>((done) => {
    resolve = done;
  });
  const first = form.submit(async (snapshot) => {
    calls++;
    expect(snapshot.name).toBe('Saved');
    await pending;
  });
  expect(form.busy.value).toBe(true);
  expect(
    await form.submit(async () => {
      calls++;
    }),
  ).toBe(false);
  expect(calls).toBe(1);
  form.values.value.name = 'Edited while saving';
  resolve();
  expect(await first).toBe(true);
  expect(form.busy.value).toBe(false);
  expect(form.dirty.value).toBe(true);
  form.reset();
  expect(form.values.value.name).toBe('Saved');
});
it('maps validated Laravel nested keys and preserves input after 422, 409 and server errors', async () => {
  const form = useForm({ name: 'Draft' });
  expect(
    laravelFieldErrors({
      errors: {
        'contacts.0.email': ['Invalid'],
        ignored: 'not an array',
        wrong: [1],
      },
    }),
  ).toEqual({ 'contacts.0.email': ['Invalid'] });
  expect(
    await form.submit(async () => {
      throw new ApiError(422, { errors: { 'contacts.0.email': ['Invalid'] } });
    }),
  ).toBe(false);
  expect(form.errors.value['contacts.0.email']).toEqual(['Invalid']);
  expect(form.failure.value).toBe('validation');
  expect(form.values.value.name).toBe('Draft');
  form.clearErrors();
  expect(
    await form.submit(async () => {
      throw new ApiError(409, null);
    }),
  ).toBe(false);
  expect(form.failure.value).toBe('conflict');
  expect(form.values.value.name).toBe('Draft');
  expect(
    await form.submit(async () => {
      throw new ApiError(500, null);
    }),
  ).toBe(false);
  expect(form.failure.value).toBe('unexpected');
  expect(form.values.value.name).toBe('Draft');
});

it('rejects nonfinite values instead of silently serializing them as null', () => {
  expect(() => useForm({ amount: Number.NaN })).toThrow(TypeError);
  expect(() => useForm({ amount: Number.POSITIVE_INFINITY })).toThrow(
    TypeError,
  );
});
