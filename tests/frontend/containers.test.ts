import { afterEach, expect, it } from 'vitest';
import { enableAutoUnmount, mount } from '@vue/test-utils';
import {
  Accordion,
  Tabs,
  Pagination,
  Breadcrumbs,
  Dropdown,
  Card,
} from '../../resources/js/runa/ui';
enableAutoUnmount(afterEach);
it('keeps accordion expansion controlled and relates its trigger to preserved content', async () => {
  const w = mount(Accordion, {
    props: { label: 'Details', modelValue: false },
    slots: { default: '<input value="draft">' },
  });
  await w.get('button').trigger('click');
  expect(w.emitted('update:modelValue')).toEqual([[true]]);
  expect(w.find('[hidden]').exists()).toBe(true);
  await w.setProps({ modelValue: true });
  expect(w.find('[hidden]').exists()).toBe(false);
  expect(w.get('input').element.value).toBe('draft');
  expect(w.get('button').attributes('aria-controls')).toBe(
    w.get('input').element.parentElement?.id,
  );
});
it('navigates tabs with arrows/Home/End, skips disabled tabs and retains inactive content', async () => {
  const w = mount(Tabs, {
    attachTo: document.body,
    props: {
      label: 'Sections',
      modelValue: 'a',
      items: [
        { id: 'a', label: 'A' },
        { id: 'off', label: 'Off', disabled: true },
        { id: 'b', label: 'B' },
      ],
    },
    slots: { a: '<input value="draft">', b: 'Other' },
  });
  const first = w.findAll('[role="tab"]')[0]!;
  await first.trigger('keydown', { key: 'ArrowRight' });
  expect(w.emitted('update:modelValue')).toEqual([['b']]);
  expect(document.activeElement?.textContent).toBe('B');
  await w.setProps({ modelValue: 'b' });
  expect(w.get('input').element.closest('[hidden]')).not.toBeNull();
  await w.findAll('[role="tab"]')[2]!.trigger('keydown', { key: 'Home' });
  expect(w.emitted('update:modelValue')?.at(-1)).toEqual(['a']);
  await first.trigger('keydown', { key: 'End' });
  expect(w.emitted('update:modelValue')?.at(-1)).toEqual(['b']);
  await w.setProps({
    items: [
      { id: 'b', label: 'B', disabled: true },
      { id: 'c', label: 'C' },
    ],
  });
  expect(w.get('[aria-selected="true"]').text()).toBe('C');
});
it('bounds pagination rendering and honors endpoints and external updates', async () => {
  const w = mount(Pagination, {
    props: {
      modelValue: 1,
      pages: 1000000,
      label: 'Pages',
      previousLabel: 'Previous',
      nextLabel: 'Next',
    },
  });
  expect(w.findAll('button').length).toBeLessThanOrEqual(7);
  expect(w.get('[aria-label="Previous"]').attributes('disabled')).toBeDefined();
  await w.get('[aria-label="Next"]').trigger('click');
  expect(w.emitted('update:modelValue')).toEqual([[2]]);
  await w.setProps({ modelValue: 1000000 });
  expect(w.get('[aria-label="Next"]').attributes('disabled')).toBeDefined();
  expect(w.get('[aria-current="page"]').text()).toBe('1000000');
  await w.setProps({ pages: 0 });
  expect(w.get('[aria-current="page"]').text()).toBe('1');
});
it('renders safe breadcrumbs with a current page and navigation interception', async () => {
  const w = mount(Breadcrumbs, {
    props: {
      label: 'Path',
      onNavigate: (_href: string, event: MouseEvent) => event.preventDefault(),
      items: [
        { label: 'Unsafe', href: 'javascript:alert(1)' },
        { label: 'Home', href: '/' },
        { label: 'Current', href: '/current' },
      ],
    },
  });
  expect(w.findAll('a')).toHaveLength(1);
  expect(w.get('[aria-current="page"]').text()).toBe('Current');
  await w.get('a').trigger('click');
  expect(w.emitted('navigate')?.[0]?.[0]).toBe('/');
});
it('supports controlled dropdown opening and ordinary card slots without a shell', async () => {
  const w = mount(Dropdown, {
    attachTo: document.body,
    props: { modelValue: false, label: 'Options' },
    slots: { default: '<button>Apply</button>' },
  });
  await w.get('button').trigger('click');
  expect(w.emitted('update:modelValue')).toEqual([[true]]);
  expect(document.querySelector('[role="dialog"]')).toBeNull();
  await w.setProps({ modelValue: true });
  expect(document.querySelector('[role="dialog"]')?.textContent).toContain(
    'Apply',
  );
  document.dispatchEvent(
    new KeyboardEvent('keydown', { key: 'Escape', bubbles: true }),
  );
  expect(w.emitted('update:modelValue')?.at(-1)).toEqual([false]);
  expect(
    mount(Card, {
      slots: { header: 'Title', default: 'Body', footer: 'Footer' },
    }).text(),
  ).toBe('TitleBodyFooter');
});
