import { afterEach, expect, it } from 'vitest';
import { enableAutoUnmount, mount } from '@vue/test-utils';
import { nextTick } from 'vue';
import {
  Select,
  type SelectionPage,
  type SelectionProvider,
} from '../../resources/js/runa/ui';
enableAutoUnmount(afterEach);
const options = [
  { value: 'a', label: 'Alpha' },
  { value: 'b', label: 'Beta', disabled: true },
  { value: 'c', label: 'Gamma' },
];
function panel() {
  return document.querySelector<globalThis.HTMLElement>('.selection-panel')!;
}
async function key(key: string) {
  panel().dispatchEvent(new KeyboardEvent('keydown', { key, bubbles: true }));
  await nextTick();
}
it('searches, skips disabled options, selects with the keyboard and restores focus', async () => {
  const w = mount(Select, {
    attachTo: document.body,
    props: { label: 'City', modelValue: '', options, searchable: true },
  });
  await w.get('button').trigger('click');
  await key('ArrowDown');
  await key('ArrowDown');
  await key('Enter');
  expect(w.emitted('update:modelValue')?.at(-1)).toEqual(['c']);
  expect(document.activeElement).toBe(w.get('button').element);
  await w.get('button').trigger('click');
  const search = panel().querySelector('input')!;
  search.value = 'zzz';
  search.dispatchEvent(new Event('input', { bubbles: true }));
  await nextTick();
  expect(panel().querySelectorAll('[role=option]')).toHaveLength(0);
  expect(panel().textContent).toContain('Brak wyników');
  await key('Escape');
  expect(w.get('button').attributes('aria-expanded')).toBe('false');
});
it('toggles multiple values, blocks readonly and disabled state, and clears explicitly', async () => {
  const w = mount(Select, {
    attachTo: document.body,
    props: {
      label: 'Cities',
      modelValue: ['a'],
      multiple: true,
      options,
      clearable: true,
    },
  });
  await w.get('button').trigger('click');
  panel().querySelectorAll<globalThis.HTMLElement>('[role=option]')[2]!.click();
  await nextTick();
  expect(w.emitted('update:modelValue')?.at(-1)).toEqual([['a', 'c']]);
  await w.setProps({ modelValue: ['a', 'c'] });
  panel().querySelectorAll<globalThis.HTMLElement>('[role=option]')[0]!.click();
  await nextTick();
  expect(w.emitted('update:modelValue')?.at(-1)).toEqual([['c']]);
  await w.setProps({ readonly: true });
  await w.get('button').trigger('click');
  expect(w.get('button').attributes('aria-expanded')).toBe('false');
  await w.setProps({ readonly: false });
  await w.get('[aria-label="Wyczyść wybór"]').trigger('click');
  expect(w.emitted('update:modelValue')?.at(-1)).toEqual([[]]);
});
it('aborts obsolete queries and ignores responses even when providers ignore cancellation', async () => {
  const requests: {
    resolve: (page: SelectionPage) => void;
    signal: globalThis.AbortSignal;
    query: string;
  }[] = [];
  const provider: SelectionProvider = ({ signal, query }) =>
    new Promise((resolve) => requests.push({ resolve, signal, query }));
  const w = mount(Select, {
    attachTo: document.body,
    props: { label: 'Remote', modelValue: '', provider, debounce: 0 },
  });
  await w.get('button').trigger('click');
  const search = panel().querySelector('input')!;
  search.value = 'new';
  search.dispatchEvent(new Event('input', { bubbles: true }));
  await new Promise((resolve) => globalThis.setTimeout(resolve, 20));
  expect(requests[0]!.signal.aborted).toBe(true);
  requests[1]!.resolve({ options: [{ value: 'new', label: 'New result' }] });
  await nextTick();
  await nextTick();
  requests[0]!.resolve({ options: [{ value: 'old', label: 'Old result' }] });
  await nextTick();
  await nextTick();
  expect(panel().textContent).toContain('New result');
  expect(panel().textContent).not.toContain('Old result');
  await key('Escape');
  expect(requests[1]!.signal.aborted).toBe(true);
});
it('bounds local rendering and pages remote results without losing selected labels', async () => {
  const w = mount(Select, {
    attachTo: document.body,
    props: {
      label: 'Large',
      modelValue: '',
      searchable: true,
      options: Array.from({ length: 10000 }, (_, i) => ({
        value: String(i),
        label: `Item ${i}`,
      })),
      pageSize: 20,
    },
  });
  await w.get('button').trigger('click');
  expect(panel().querySelectorAll('[role=option]')).toHaveLength(20);
  [...panel().querySelectorAll('button')]
    .find((b) => b.textContent?.includes('Kolejne'))!
    .click();
  await nextTick();
  expect(panel().querySelector('[role=option]')?.textContent).toContain(
    'Item 20',
  );
  await key('Escape');
  const calls: (string | null)[] = [];
  const provider: SelectionProvider = async ({ cursor }) => {
    calls.push(cursor);
    return {
      options: [
        { value: cursor ?? 'first', label: cursor ? 'Second' : 'First' },
      ],
      nextCursor: cursor ? null : 'second',
    };
  };
  await w.setProps({ provider, multiple: true, modelValue: [] });
  await w.get('button').trigger('click');
  await nextTick();
  await nextTick();
  panel().querySelector<globalThis.HTMLElement>('[role=option]')!.click();
  await nextTick();
  await w.setProps({ modelValue: ['first'] });
  [...panel().querySelectorAll('button')]
    .find((b) => b.textContent?.includes('Kolejne'))!
    .click();
  await nextTick();
  await nextTick();
  expect(calls).toEqual([null, 'second']);
  expect(w.get('button').text()).toContain('First');
  expect(panel().textContent).toContain('Second');
});

it('exposes provider failure, retries and keeps empty and loading states distinct', async () => {
  let fail = true;
  const provider: SelectionProvider = async () => {
    if (fail) throw new Error('Unavailable');
    return { options: [] };
  };
  const w = mount(Select, {
    attachTo: document.body,
    props: { label: 'Remote', modelValue: '', provider },
  });
  await w.get('button').trigger('click');
  await nextTick();
  await nextTick();
  expect(panel().textContent).toContain('Nie udało się');
  fail = false;
  [...panel().querySelectorAll('button')]
    .find((b) => b.textContent?.includes('Ponów'))!
    .click();
  await nextTick();
  await nextTick();
  expect(panel().textContent).toContain('Brak wyników');
  expect(w.emitted('update:modelValue')).toBeUndefined();
});

it('uses the themed panel for simple selections and never opens readonly fields', async () => {
  const w = mount(Select, {
    attachTo: document.body,
    props: { label: 'City', modelValue: 'a', options, readonly: true },
  });
  expect(w.find('select').exists()).toBe(false);
  await w.get('button').trigger('click');
  await w.get('button').trigger('keydown', { key: 'ArrowDown' });
  expect(w.get('button').attributes('aria-expanded')).toBe('false');
  await w.setProps({ readonly: false });
  await w.get('button').trigger('click');
  expect(panel().querySelector('input')).toBeNull();
  await key('ArrowDown');
  await key('ArrowDown');
  await key('Enter');
  expect(w.emitted('update:modelValue')?.at(-1)).toEqual(['c']);
});
it('matches small select trigger, options and action sizes while keeping md default', async () => {
  const w = mount(Select, {
    attachTo: document.body,
    props: {
      size: 'sm',
      label: 'Compact',
      modelValue: 'a',
      options,
      clearable: true,
    },
  });
  const trigger = w.get('.selection-trigger');
  expect(trigger.attributes('data-size')).toBe('sm');
  await trigger.trigger('click');
  expect(panel().dataset.size).toBe('sm');
  expect(panel().querySelector('button')?.dataset.size).toBe('sm');
  await key('Escape');
  await w.setProps({ size: 'md' });
  expect(trigger.attributes('data-size')).toBe('md');
});
