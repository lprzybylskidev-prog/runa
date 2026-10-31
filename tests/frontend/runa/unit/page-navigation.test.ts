/* eslint-disable vue/one-component-per-file -- Independent navigation composition fixtures. */
import { afterEach, expect, it, vi } from '@runa/testing/vitest';
import { enableAutoUnmount, flushPromises, mount } from '@runa/testing/vue';
import { defineComponent, h, ref } from 'vue';
import {
  ApplicationLayout,
  PageNavigation,
  createPreferenceStore,
} from '@/runa/ui';
import type { NavigationEntry } from '@/runa/ui';
enableAutoUnmount(afterEach);
const entry = (key: string): NavigationEntry => ({
  key,
  routeKey: key,
  moduleKey: 'app',
  label: key,
  href: '/app?section=' + key,
  availability: 'available',
});
it('page navigation is rendered in the header and preserves the layout when its entries change', async () => {
  const entries = ref([entry('first')]);
  const navigate = vi.fn((_: string, event: MouseEvent) =>
    event.preventDefault(),
  );
  const w = mount(
    defineComponent({
      setup: () => () =>
        h(
          ApplicationLayout,
          {
            title: 'Page',
            navigation: [],
            preferenceStore: createPreferenceStore(),
          },
          {
            default: () =>
              h(PageNavigation, {
                entries: entries.value,
                current: { ...entry(entries.value[0]!.key) },
                label: 'Sections',
                onNavigate: navigate,
              }),
          },
        ),
    }),
  );
  await flushPromises();
  const frame = w.get('.application-page').element;
  expect(w.find('main nav').exists()).toBe(false);
  const link = w.get('header .shell-top-nav a');
  expect(link.text()).toBe('first');
  expect(link.attributes('aria-current')).toBe('page');
  await link.trigger('click');
  expect(navigate).toHaveBeenCalledOnce();
  entries.value = [entry('second')];
  await flushPromises();
  expect(w.get('header .shell-top-nav a').text()).toBe('second');
  expect(w.get('.application-page').element).toBe(frame);
});
it('disposing an older page publisher cannot clear the newer top navigation', async () => {
  const first = ref(true),
    second = ref(true);
  const w = mount(
    defineComponent({
      setup: () => () =>
        h(
          ApplicationLayout,
          {
            title: 'Page',
            navigation: [],
            preferenceStore: createPreferenceStore(),
          },
          {
            default: () => [
              first.value
                ? h(PageNavigation, { key: 'first', entries: [entry('first')] })
                : null,
              second.value
                ? h(PageNavigation, {
                    key: 'second',
                    entries: [entry('second')],
                  })
                : null,
            ],
          },
        ),
    }),
  );
  await flushPromises();
  first.value = false;
  await flushPromises();
  expect(w.get('header .shell-top-nav a').text()).toBe('second');
  second.value = false;
  await flushPromises();
  expect(w.find('header .shell-top-nav a').exists()).toBe(false);
});
it('standalone page navigation retains a controlled labeled fallback', async () => {
  const w = mount(PageNavigation, {
    props: {
      entries: [entry('first')],
      label: 'Sections',
      current: entry('first'),
    },
  });
  expect(w.get('nav').attributes('aria-label')).toBe('Sections');
  await w.get('button').trigger('click');
  expect(w.emitted('navigate')?.[0]?.[0]).toBe('/app?section=first');
  await w.setProps({ disabled: true });
  expect(w.get('button').attributes('disabled')).toBeDefined();
});
