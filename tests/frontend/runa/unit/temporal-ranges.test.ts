import { afterEach, expect, it } from '@runa/testing/vitest';
import { enableAutoUnmount, mount } from '@runa/testing/vue';
import { nextTick } from 'vue';
import { DateTimeRangeInput, DateTimeInput } from '@/runa/ui';
enableAutoUnmount(afterEach);
const button = (text: string) =>
  Array.from(
    document.querySelectorAll<HTMLButtonElement>('[role=dialog] button'),
  ).find((b) => b.textContent?.trim() === text)!;
it('datetime range commits both UTC endpoints once, permits open bounds and clears', async () => {
  const w = mount(DateTimeRangeInput, {
    attachTo: document.body,
    props: {
      label: 'Activity',
      locale: 'en',
      timeZone: 'Europe/Warsaw',
      modelValue: { start: null, end: null },
    },
  });
  expect(w.findAll('input')).toHaveLength(0);
  await w.get('button').trigger('click');
  await nextTick();
  const endpoints = w.findAllComponents(DateTimeInput);
  expect(endpoints).toHaveLength(2);
  await endpoints[0]!.get('input').setValue('2026-11-02 13:00');
  await endpoints[1]!.get('input').setValue('2026-11-03 14:00');
  expect(w.emitted('update:modelValue')).toBeUndefined();
  button('Apply').click();
  await nextTick();
  const value = {
    start: '2026-11-02T12:00:00.000Z',
    end: '2026-11-03T13:00:00.000Z',
  };
  expect(w.emitted('update:modelValue')).toEqual([[value]]);
  expect(document.activeElement).toBe(w.get('button').element);
  await w.setProps({ modelValue: value });
  await w.get('button').trigger('click');
  await nextTick();
  await w.findAllComponents(DateTimeInput)[1]!.get('input').setValue('');
  button('Apply').click();
  await nextTick();
  expect(w.emitted('update:modelValue')?.at(-1)).toEqual([
    { start: value.start, end: null },
  ]);
  await w.get('button').trigger('click');
  await nextTick();
  button('Clear').click();
  await nextTick();
  expect(w.emitted('update:modelValue')?.at(-1)).toEqual([
    { start: null, end: null },
  ]);
});
it('datetime range refuses reversed and DST-invalid drafts and cancels without publishing', async () => {
  const w = mount(DateTimeRangeInput, {
    attachTo: document.body,
    props: {
      label: 'Activity',
      locale: 'en',
      timeZone: 'Europe/Warsaw',
      modelValue: {
        start: '2026-11-02T12:00:00Z',
        end: '2026-11-03T12:00:00Z',
      },
    },
  });
  await w.get('button').trigger('click');
  await nextTick();
  await w
    .findAllComponents(DateTimeInput)[0]!
    .get('input')
    .setValue('2026-11-04 13:00');
  expect(button('Apply').disabled).toBe(true);
  await w
    .findAllComponents(DateTimeInput)[0]!
    .get('input')
    .setValue('2026-10-25 02:30');
  expect(button('Apply').disabled).toBe(true);
  button('Cancel').click();
  await nextTick();
  expect(w.emitted('update:modelValue')).toBeUndefined();
  await w.get('button').trigger('click');
  await nextTick();
  expect(
    w.findAllComponents(DateTimeInput)[0]!.get('input').element.value,
  ).toContain('2026-11-02');
  w.vm.reset();
  await nextTick();
  expect(document.querySelector('[role=dialog]')).toBeNull();
});
