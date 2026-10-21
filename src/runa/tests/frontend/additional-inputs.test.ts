import { afterEach, expect, it } from 'vitest';
import { enableAutoUnmount, mount } from '@vue/test-utils';
import { defineComponent, h, nextTick } from 'vue';
import {
  DateInput,
  TimeInput,
  DateTimeInput,
  DateRangeInput,
  Checkbox,
  Switch,
  RadioGroup,
  FileInput,
  ColorInput,
  HiddenInput,
  TagsInput,
  isDateOnly,
  isTimeOnly,
  isInstant,
  validDateRange,
  formatDateOnly,
  resolveLocalDateTime,
  toLocalDateTime,
  validateFiles,
} from '../../resources/js/runa/ui';
enableAutoUnmount(afterEach);
it('validates date-only/time boundaries without shifting calendar dates', () => {
  expect(isDateOnly('2024-02-29')).toBe(true);
  expect(isDateOnly('2026-02-29')).toBe(false);
  expect(isDateOnly('0099-01-01')).toBe(true);
  expect(isDateOnly('0000-01-01')).toBe(false);
  expect(isDateOnly('2026-2-01')).toBe(false);
  expect(formatDateOnly('2026-10-19', 'pl')).toBe('19.10.2026');
  expect(formatDateOnly(null, 'en')).toBe('');
  expect(isTimeOnly('23:59:59.123')).toBe(true);
  expect(isTimeOnly('24:00')).toBe(false);
  expect(isTimeOnly('12:60')).toBe(false);
  expect(validDateRange({ start: null, end: '2026-10-19' })).toBe(true);
  expect(validDateRange({ start: '2026-10-20', end: '2026-10-19' })).toBe(
    false,
  );
  expect(validDateRange({ start: '', end: null })).toBe(false);
});
it('converts explicit-zone wall times to UTC and rejects DST gaps/overlaps by default', () => {
  expect(resolveLocalDateTime('2026-10-19T09:30', 'Europe/Warsaw')).toEqual({
    valid: true,
    value: '2026-10-19T07:30:00.000Z',
  });
  expect(
    resolveLocalDateTime('2026-03-29T02:30', 'Europe/Warsaw'),
  ).toMatchObject({ valid: false, reason: 'gap' });
  expect(resolveLocalDateTime('2026-10-25T02:30', 'Europe/Warsaw')).toEqual({
    valid: false,
    reason: 'ambiguous',
    candidates: ['2026-10-25T00:30:00.000Z', '2026-10-25T01:30:00.000Z'],
  });
  expect(
    resolveLocalDateTime('2026-10-25T02:30', 'Europe/Warsaw', 'earlier'),
  ).toEqual({ valid: true, value: '2026-10-25T00:30:00.000Z' });
  expect(
    resolveLocalDateTime('2026-10-25T02:30', 'Europe/Warsaw', 'later'),
  ).toEqual({ valid: true, value: '2026-10-25T01:30:00.000Z' });
  expect(resolveLocalDateTime(null, 'UTC')).toEqual({
    valid: true,
    value: null,
  });
  expect(resolveLocalDateTime(null, 'Invalid/Zone')).toMatchObject({
    valid: false,
    reason: 'timezone',
  });
  expect(
    toLocalDateTime('2024-12-31T23:59:59.123Z', 'Pacific/Kiritimati'),
  ).toBe('2025-01-01T13:59:59.123');
  expect(
    resolveLocalDateTime('2025-01-01T13:59:59.123', 'Pacific/Kiritimati'),
  ).toEqual({ valid: true, value: '2024-12-31T23:59:59.123Z' });
  expect(isInstant('2026-10-19T09:30:00+02:00')).toBe(true);
  expect(isInstant('2026-10-19T09:30')).toBe(false);
  expect(isInstant('2026-02-31T09:30Z')).toBe(false);
});
it('preserves nullable text temporal values and exposes reset/focus for incomplete drafts', async () => {
  const w = mount(DateInput, {
    attachTo: document.body,
    props: { modelValue: '2026-10-19', label: 'Date', locale: 'en' },
  });
  expect(w.get('input').element.value).toBe('2026-10-19');
  expect(w.get('input').attributes('type')).toBe('text');
  await w.get('input').setValue('2026-10-20');
  expect(w.emitted('update:modelValue')?.at(-1)).toEqual(['2026-10-20']);
  await w.get('input').setValue('');
  expect(w.emitted('update:modelValue')?.at(-1)).toEqual([null]);
  w.vm.reset();
  expect(w.get('input').element.value).toBe('2026-10-19');
  w.vm.focus();
  expect(document.activeElement).toBe(w.get('input').element);
  const time = mount(TimeInput, {
    props: { modelValue: '09:30', label: 'Time' },
  });
  expect(time.get('input').element.value).toBe('09:30');
  const bad = mount(DateInput, {
    props: { modelValue: '2026-02-31', label: 'Invalid date' },
  });
  await nextTick();
  expect(bad.get('input').attributes('aria-invalid')).toBe('true');
});
it('blocks nonexistent/ambiguous local datetime commits and resolves them explicitly', async () => {
  const w = mount(DateTimeInput, {
    props: { modelValue: null, label: 'Moment', timeZone: 'Europe/Warsaw' },
  });
  await w.get('input').setValue('2026-03-29T02:30');
  expect(w.emitted('validity')?.at(-1)).toEqual([false]);
  expect(w.emitted('update:modelValue')).toBeUndefined();
  await w.get('input').setValue('2026-10-25T02:30');
  expect(w.get('input').attributes('data-time-error')).toBe('ambiguous');
  await w.setProps({ disambiguation: 'later' });
  expect(w.emitted('update:modelValue')?.at(-1)).toEqual([
    '2026-10-25T01:30:00.000Z',
  ]);
  await w.setProps({ modelValue: '2026-10-25T01:30:00.000Z' });
  expect(w.get('input').element.value).toBe('2026-10-25T02:30');
  w.vm.reset();
  expect(w.get('input').element.value).toBe('25.10.2026 02:30:00');
});
it('keeps date range endpoints controlled and reports reversed order', async () => {
  const w = mount(DateRangeInput, {
    props: {
      modelValue: { start: '2026-10-19', end: '2026-10-20' },
      label: 'Range',
      startLabel: 'From',
      endLabel: 'To',
    },
  });
  await w.findAll('input')[0]!.setValue('2026-10-21');
  expect(w.emitted('update:modelValue')?.at(-1)).toEqual([
    { start: '2026-10-21', end: '2026-10-20' },
  ]);
  await w.setProps({ modelValue: { start: '2026-10-21', end: '2026-10-20' } });
  expect(w.get('[role="status"]').text()).toBe('Check the date range.');
  expect(w.emitted('validity')?.at(-1)).toEqual([false]);
});
it('uses native boolean semantics for indeterminate, switch, disabled and readonly states', async () => {
  const w = mount(Checkbox, {
    attachTo: document.body,
    props: { label: 'Include', modelValue: null },
  });
  expect(w.get('input').element.indeterminate).toBe(true);
  expect(w.get('input').attributes('aria-checked')).toBe('mixed');
  w.get('input').element.click();
  await nextTick();
  expect(w.emitted('update:modelValue')?.at(-1)).toEqual([true]);
  const readonly = mount(Checkbox, {
    props: { label: 'Read only', modelValue: false, readonly: true },
  });
  readonly.get('input').element.click();
  await nextTick();
  expect(readonly.emitted('update:modelValue')).toBeUndefined();
  const toggle = mount(Switch, {
    props: { label: 'Enabled', modelValue: true, disabled: true },
  });
  expect(toggle.get('input').attributes('role')).toBe('switch');
  toggle.get('input').element.click();
  expect(toggle.emitted('update:modelValue')).toBeUndefined();
});
it('keeps radio names isolated between groups and preserves exact option IDs', async () => {
  const props = {
    modelValue: null,
    label: 'Mode',
    options: [
      { value: 'a', label: 'A' },
      { value: 'b', label: 'B', disabled: true },
    ],
  };
  const harness = mount(
    defineComponent({
      setup: () => () => h('div', [h(RadioGroup, props), h(RadioGroup, props)]),
    }),
  );
  const a = harness.findAllComponents(RadioGroup)[0]!,
    b = harness.findAllComponents(RadioGroup)[1]!;
  expect(a.get('input').attributes('name')).not.toBe(
    b.get('input').attributes('name'),
  );
  await a.get('input').setValue(true);
  expect(a.emitted('update:modelValue')).toEqual([['a']]);
  expect(a.findAll('input')[1]!.attributes('disabled')).toBeDefined();
});
it('checks file UX limits without treating accept or MIME as authorization', () => {
  const pdf = new globalThis.File(['abc'], 'report.pdf', {
    type: 'application/pdf',
  });
  const image = new globalThis.File(['abcd'], 'image.png', {
    type: 'image/png',
  });
  expect(validateFiles([pdf], { accept: '.pdf', maxBytes: 3 })).toEqual([]);
  expect(validateFiles([image], { accept: 'image/*' })).toEqual([]);
  expect(validateFiles([pdf, image], { accept: '.pdf', maxBytes: 2 })).toEqual([
    { code: 'count' },
    { code: 'size', fileName: 'report.pdf' },
    { code: 'size', fileName: 'image.png' },
    { code: 'type', fileName: 'image.png' },
  ]);
});
it('exposes selected files, rejects no data silently and lets the caller remove them', async () => {
  const w = mount(FileInput, {
    props: {
      modelValue: [],
      label: 'Attachments',
      multiple: true,
      maxFiles: 1,
    },
  });
  const file = new globalThis.File(['hello'], 'hello.pdf', {
    type: 'application/pdf',
  });
  const transfer = new globalThis.DataTransfer();
  transfer.items.add(file);
  const input = w.get('input');
  input.element.files = transfer.files;
  await input.trigger('change');
  expect(w.emitted('update:modelValue')?.at(-1)).toEqual([[file]]);
  expect(input.element.value).toBe('');
  await w.setProps({ modelValue: [file, file] });
  expect(w.text()).toContain('Too many files.');
  expect(w.findAll('li').length).toBeGreaterThanOrEqual(2);
  await w.findAll('button')[1]!.trigger('click');
  expect(w.emitted('update:modelValue')?.at(-1)).toEqual([[file]]);
});
it('preserves color drafts/null and updates the model through the swatch', async () => {
  const w = mount(ColorInput, {
    props: { modelValue: null, label: 'Color' },
    global: { stubs: { teleport: true } },
  });
  await w.get('input[type="text"]').setValue('#BAD');
  expect(w.emitted('update:modelValue')?.at(-1)).toEqual(['#BAD']);
  expect(w.get('input[type="text"]').attributes('aria-invalid')).toBe('true');
  expect(w.find('input[type="color"]').exists()).toBe(false);
  await w.get('button').trigger('click');
  await w.get('button[aria-label="#b784df"]').trigger('click');
  expect(w.emitted('update:modelValue')?.at(-1)).toEqual(['#b784df']);
  expect(w.emitted('validity')?.at(-1)).toEqual([true]);
  await w.get('input[type="text"]').setValue('');
  expect(w.emitted('update:modelValue')?.at(-1)).toEqual([null]);
});
it('commits tags with Enter, respects composition/limits/duplicates and removes by index', async () => {
  const w = mount(TagsInput, {
    props: { modelValue: ['Runa'], label: 'Tags', maxTags: 2 },
  });
  await w.get('input').setValue('Vue');
  expect(w.emitted('validity')?.at(-1)).toEqual([false]);
  await w.get('input').trigger('keydown', { key: 'Enter', isComposing: true });
  expect(w.emitted('update:modelValue')).toBeUndefined();
  await w.get('input').trigger('keydown', { key: 'Enter' });
  expect(w.emitted('update:modelValue')?.at(-1)).toEqual([['Runa', 'Vue']]);
  await w.setProps({ modelValue: ['Runa', 'Vue'] });
  await w.get('input').setValue('Runa');
  await w.get('input').trigger('keydown', { key: 'Enter' });
  expect(w.text()).toContain('This tag already exists.');
  await w.get('input').setValue('Third');
  await w.get('input').trigger('keydown', { key: 'Enter' });
  expect(w.text()).toContain('Tag limit reached.');
  await w.findAll('button')[0]!.trigger('click');
  expect(w.emitted('update:modelValue')?.at(-1)).toEqual([['Vue']]);
  const hidden = mount(HiddenInput, {
    props: { modelValue: 'public-id', name: 'public_id' },
  });
  expect(hidden.attributes('type')).toBe('hidden');
  expect(hidden.element.value).toBe('public-id');
});
