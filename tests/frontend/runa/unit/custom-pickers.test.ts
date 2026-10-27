import { afterEach, expect, it } from '@runa/testing/vitest';
import { enableAutoUnmount, mount } from '@runa/testing/vue';
import { nextTick } from 'vue';
import {
  DateInput,
  DateTimeInput,
  TimeInput,
  ColorInput,
} from '../../../../src/runa/resources/js/runa/ui';
import Calendar from '../../../../src/runa/resources/js/components/AppCalendarPicker.vue';
import TimePicker from '../../../../src/runa/resources/js/components/AppTimePicker.vue';
import Slider from '../../../../src/runa/resources/js/components/AppPickerSlider.vue';
import {
  hexToHsv,
  hsvToHex,
} from '../../../../src/runa/resources/js/lib/color';
enableAutoUnmount(afterEach);
const popup = { stubs: { teleport: true } };
it('renders an application calendar, moves across leap months and selects only explicitly', async () => {
  const w = mount(Calendar, {
    attachTo: document.body,
    props: { modelValue: '2024-02-29', locale: 'pl', today: '2024-03-02' },
  });
  expect(w.findAll('[role=columnheader]')[0]!.text()).toContain('pon');
  const day = w.get('[data-date="2024-02-29"]');
  await day.trigger('keydown', { key: 'ArrowRight' });
  expect((document.activeElement as HTMLElement)?.dataset.date).toBe(
    '2024-03-01',
  );
  expect(w.emitted('update:modelValue')).toBeUndefined();
  await w.get('[data-date="2024-03-01"]').trigger('click');
  expect(w.emitted('update:modelValue')?.at(-1)).toEqual(['2024-03-01']);
  await w.get('[data-date="2024-03-01"]').trigger('keydown', { key: 'PageUp' });
  expect((document.activeElement as HTMLElement)?.dataset.date).toBe(
    '2024-02-01',
  );
  await w.get('button[aria-label="Wybierz rok"]').trigger('click');
  await w
    .findAll('button')
    .find((b) => b.text() === '2025')!
    .trigger('click');
  await w
    .findAll('button')
    .find((b) => b.text() === 'styczeń')!
    .trigger('click');
  expect(w.get('[role=grid]').attributes('aria-label')).toBe('styczeń 2025');
  expect(w.findAll('[role=gridcell][tabindex="0"]').length).toBe(1);
  await w
    .findAll('button')
    .find((b) => b.text() === 'Dzisiaj')!
    .trigger('click');
  expect(w.emitted('update:modelValue')?.at(-1)).toEqual(['2024-03-02']);
});
it('uses localized text dates, retains invalid/IME drafts and resets without browser validation', async () => {
  const w = mount(DateInput, {
    props: { modelValue: '2026-10-19', label: 'Data' },
  });
  expect(w.get('input').attributes('type')).toBe('text');
  expect(w.get('input').element.value).toBe('19.10.2026');
  await w.get('input').setValue('31.02.2026');
  expect(w.get('input').element.value).toBe('31.02.2026');
  expect(w.emitted('validity')?.at(-1)).toEqual([false]);
  expect(w.emitted('update:modelValue')).toBeUndefined();
  await w.get('input').setValue('20.10.2026');
  expect(w.emitted('update:modelValue')?.at(-1)).toEqual(['2026-10-20']);
  await w.get('input').setValue('2026-10-20T12:00Tgarbage');
  expect(w.emitted('validity')?.at(-1)).toEqual([false]);
  w.vm.reset();
  await nextTick();
  expect(w.get('input').element.value).toBe('19.10.2026');
  await w.setProps({ locale: 'en' });
  expect(w.get('input').element.value).toBe('2026-10-19');
});
it('opens its calendar with Alt+Down, selects a day and clears to null', async () => {
  const w = mount(DateInput, {
    attachTo: document.body,
    global: popup,
    props: { modelValue: '2026-10-19', label: 'Date', locale: 'en' },
  });
  await w.get('input').trigger('keydown', { key: 'ArrowDown', altKey: true });
  await nextTick();
  expect(w.find('[role=dialog]').exists()).toBe(true);
  await w.get('[data-date="2026-10-20"]').trigger('click');
  expect(w.emitted('update:modelValue')?.at(-1)).toEqual(['2026-10-20']);
  expect(w.find('[role=dialog]').exists()).toBe(false);
  await w.get('button').trigger('click');
  await w
    .findAll('button')
    .find((b) => b.text() === 'Clear')!
    .trigger('click');
  expect(w.emitted('update:modelValue')?.at(-1)).toEqual([null]);
});
it('chooses 24-hour time with minute precision while preserving explicit seconds', async () => {
  const w = mount(TimePicker, {
    attachTo: document.body,
    props: { modelValue: '23:59:12.345', locale: 'en' },
  });
  await w.get('[data-time="0-23"]').trigger('keydown', { key: 'ArrowDown' });
  expect(w.emitted('update:modelValue')?.at(-1)).toEqual(['00:59:12.345']);
  await w.get('[data-time="1-59"]').trigger('keydown', { key: 'Home' });
  expect(w.emitted('update:modelValue')?.at(-1)).toEqual(['23:00:12.345']);
  const input = mount(TimeInput, {
    global: popup,
    props: { modelValue: null, label: 'Time', locale: 'en' },
  });
  await input.get('button').trigger('click');
  await input.get('[data-time="0-9"]').trigger('click');
  await input.get('[data-time="1-37"]').trigger('click');
  expect(input.emitted('update:modelValue')?.at(-1)).toEqual(['09:37']);
});
it('keeps custom datetime picker choices behind existing DST and readonly rules', async () => {
  const w = mount(DateTimeInput, {
    global: popup,
    props: {
      modelValue: '2026-03-29T00:30:00.000Z',
      timeZone: 'Europe/Warsaw',
      label: 'Moment',
      locale: 'en',
    },
  });
  await w.get('button').trigger('click');
  await w.get('[data-time="0-2"]').trigger('click');
  expect(w.emitted('validity')?.at(-1)).toEqual([false]);
  expect(w.emitted('update:modelValue')).toBeUndefined();
  expect(w.get('input').element.value).toBe('2026-03-29 02:30:00');
  await w.setProps({ readonly: true });
  expect(w.find('[role=dialog]').exists()).toBe(false);
  expect(w.get('button').attributes('disabled')).toBeDefined();
});
it('roundtrips all color channels and supports grayscale and hue boundaries', () => {
  for (const value of [
    '#000000',
    '#ffffff',
    '#b784df',
    '#123456',
    '#ff0000',
    '#00ff00',
    '#0000ff',
  ])
    expect(hsvToHex(hexToHsv(value))).toBe(value);
  expect(hsvToHex({ h: 360, s: 100, v: 100 })).toBe('#ff0000');
  expect(() => hexToHsv('#bad')).toThrow();
});
it('uses custom color sliders/palette and supports keyboard values and null clearing', async () => {
  const slider = mount(Slider, {
    props: { modelValue: 50, max: 100, label: 'Saturation', gradient: 'none' },
  });
  await slider.get('[role=slider]').trigger('keydown', { key: 'End' });
  expect(slider.emitted('update:modelValue')?.at(-1)).toEqual([100]);
  await slider.get('[role=slider]').trigger('keydown', { key: 'PageDown' });
  expect(slider.emitted('update:modelValue')?.at(-1)).toEqual([40]);
  const w = mount(ColorInput, {
    global: popup,
    props: { modelValue: '#ff0000', label: 'Color', locale: 'en' },
  });
  await w.get('button').trigger('click');
  expect(w.findAll('[role=slider]')).toHaveLength(3);
  await w
    .get('[role=slider][aria-label="Hue"]')
    .trigger('keydown', { key: 'End' });
  expect(w.emitted('update:modelValue')?.at(-1)).toEqual(['#ff0004']);
  await w.get('button[aria-label="#000000"]').trigger('click');
  expect(w.emitted('update:modelValue')?.at(-1)).toEqual(['#000000']);
  await w
    .findAll('button')
    .find((b) => b.text() === 'Clear')!
    .trigger('click');
  expect(w.emitted('update:modelValue')?.at(-1)).toEqual([null]);
  expect(w.find('input[type=color]').exists()).toBe(false);
});
