import { afterEach, expect, it } from '@runa/testing/vitest';
import { mount, enableAutoUnmount } from '@runa/testing/vue';
import { ApplicationLayout, createPageResolver } from '@/runa/ui';
import type { NavigationEntry, PageDescriptor } from '@/runa/ui';
enableAutoUnmount(afterEach);
const pages: PageDescriptor[] = [
  {
    routeKey: 'a',
    moduleKey: 'one',
    areaKey: 'list',
    href: '/one',
    title: 'One',
    serverRouteName: 'one.index',
  },
  {
    routeKey: 'b',
    moduleKey: 'two',
    href: '/two',
    title: 'Two',
    serverRouteName: 'two.index',
  },
];
const navigation: NavigationEntry[] = pages.map((page) => ({
  ...page,
  key: page.routeKey,
  label: page.title,
  availability: 'available',
}));
it('resolves exact same-origin paths and rejects unknown, external and duplicate paths', () => {
  const resolver = createPageResolver(pages, 'https://example.test');
  expect(resolver.resolve('/one?sort=name#item')?.moduleKey).toBe('one');
  expect(resolver.resolve('/two')?.moduleKey).toBe('two');
  expect(resolver.resolve('/one/missing')).toBeUndefined();
  expect(resolver.resolve('https://other.test/one')).toBeUndefined();
  expect(() =>
    createPageResolver([...pages, pages[0]!], 'https://example.test'),
  ).toThrow();
  expect(() =>
    createPageResolver(
      [{ ...pages[0]!, href: '//other.test/one' }],
      'https://example.test',
    ),
  ).toThrow();
});
it('orders optional workspace regions and permits both navigations and footer to be hidden', async () => {
  const layout = mount(ApplicationLayout, {
    props: { navigation, current: pages[0], title: 'Workspace' },
    slots: {
      actions: 'Action',
      breadcrumbs: 'Breadcrumbs',
      statistics: 'Statistics',
      toolbar: 'Toolbar',
      default: 'Unused fallback',
      content: 'Content',
      supplementary: 'Supplementary',
    },
  });
  const main = layout.get('main').element;
  expect(Array.from(main.children).map((child) => child.className)).toEqual([
    'shell-heading',
    'shell-breadcrumbs',
    'shell-statistics',
    'shell-toolbar',
    'shell-content-grid has-supplementary',
  ]);
  expect(layout.get('a[aria-current="page"]').text()).toBe('One');
  await layout.setProps({
    sidebar: false,
    topbar: false,
    footer: { visible: false },
  });
  expect(layout.find('.shell-sidebar').exists()).toBe(false);
  expect(layout.find('.shell-top-nav').exists()).toBe(false);
  expect(layout.find('footer').exists()).toBe(false);
  expect(layout.get('.shell-content').text()).toBe('Content');
});

it('renders hidden, disabled and invalid navigation entries without unsafe links', () => {
  const layout = mount(ApplicationLayout, {
    props: {
      title: 'Workspace',
      navigation: [
        {
          ...navigation[0]!,
          key: 'hidden',
          label: 'Hidden',
          availability: 'hidden',
        },
        {
          ...navigation[0]!,
          key: 'disabled',
          label: 'Disabled',
          availability: 'disabled',
        },
        {
          ...navigation[0]!,
          key: 'invalid',
          label: 'Invalid',
          href: 'javascript:alert(1)',
        },
      ],
    },
  });
  expect(layout.find('a[href^="javascript:"]').exists()).toBe(false);
  expect(layout.find('.shell-nav-list').text()).not.toContain('Hidden');
  expect(layout.findAll('[aria-disabled="true"]')).toHaveLength(2);
});
