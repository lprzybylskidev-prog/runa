/* eslint-disable vue/one-component-per-file -- Independent mounted harnesses exercise module entry boundaries. */
import loaders from 'virtual:runa-module-components';
import CustomerModules from './fixtures/customization/CustomerModules.vue';
import { afterEach, expect, it, vi } from '@runa/testing/vitest';
import { mount, enableAutoUnmount, flushPromises } from '@runa/testing/vue';
import { defineComponent, h, effectScope } from 'vue';
import {
  ModulePage,
  ModuleSlot,
  createModuleComponents,
  decodeModuleFrontend,
  moduleNavigation,
  useModuleFrontend,
  loadModuleFrontend,
} from '@/runa/modules';
import type { ModuleFrontendView } from '@/runa/modules';
import { ApplicationLayout, createPageResolver } from '@/runa/ui';
enableAutoUnmount(afterEach);
function view(): ModuleFrontendView {
  return decodeModuleFrontend({
    schemaVersion: 1,
    generation: 7,
    pages: [
      {
        routeKey: 'app.probe.index',
        moduleKey: 'app.probe',
        areaKey: 'index',
        href: '/modules/app/probe/index',
        serverRouteName: 'app.probe.pages.index',
        names: { pl: 'Moduł', en: 'Module' },
        icon: 'layers-intersect',
        component: 'app.probe.index',
        manifestHash: 'a'.repeat(64),
        availability: 'available',
        reason: null,
      },
    ],
    slots: {
      'app.probe.panel': {
        contract: {
          id: 'app.probe.panel',
          contract: 'panel',
          version: 1,
          props: { label: 'string' },
          schemaVersion: 1,
        },
        contributions: ['b', 'a'].map((key) => ({
          id: `app.probe.${key}`,
          component: `app.probe.${key}`,
          manifestHash: 'a'.repeat(64),
          target: {
            id: 'app.probe.panel',
            contract: 'panel',
            version: 1,
            props: { label: 'string' },
            schemaVersion: 1,
          },
        })),
      },
    },
    diagnostics: {},
  });
}
it('renders deep links after refresh and suppresses unavailable page setup and imports', async () => {
  const setup = vi.fn();
  const loader = vi.fn(async () => ({
    default: defineComponent({
      setup() {
        setup();
        return () => h('p', 'Page content');
      },
    }),
  }));
  const components = createModuleComponents({
    'app.probe.index': { manifestHash: 'a'.repeat(64), load: loader },
  });
  const data = view();
  const disabled = {
    ...data,
    pages: data.pages.map((page) => ({
      ...page,
      availability: 'disabled' as const,
    })),
  };
  const page = mount(ModulePage, {
    props: {
      view: disabled,
      components,
      href: '/modules/app/probe/index?sort=name#row',
    },
  });
  await flushPromises();
  expect(loader).not.toHaveBeenCalled();
  expect(setup).not.toHaveBeenCalled();
  expect(page.get('[role="status"]').text()).toBe('Moduł jest niedostępny.');
  await page.setProps({ view: data });
  await flushPromises();
  await vi.waitFor(() => expect(page.text()).toBe('Page content'));
  expect(setup).toHaveBeenCalledOnce();
  await page.setProps({ view: disabled });
  expect(page.text()).not.toContain('Page content');
  await page.setProps({ view: data, href: '/modules/app/probe/unknown' });
  expect(page.text()).not.toContain('Page content');
  expect(
    createPageResolver([
      {
        routeKey: 'app.probe.index',
        moduleKey: 'app.probe',
        href: '/modules/app/probe/index',
        serverRouteName: 'app.probe.pages.index',
        title: 'Module',
      },
    ]).resolve(window.location.origin + '/modules/app/probe/index')?.moduleKey,
  ).toBe('app.probe');
});
it('projects disabled hidden versus unhealthy diagnostic navigation through the real shell', () => {
  const data = view();
  const pages = [
    data.pages[0]!,
    {
      ...data.pages[0]!,
      routeKey: 'app.probe.hidden',
      href: '/modules/app/probe/hidden',
      availability: 'hidden' as const,
      names: { pl: 'Ukryty', en: 'Hidden' },
    },
    {
      ...data.pages[0]!,
      routeKey: 'app.probe.unhealthy',
      href: '/modules/app/probe/unhealthy',
      availability: 'disabled' as const,
      names: { pl: 'Niedostępny', en: 'Unavailable' },
    },
  ];
  const layout = mount(ApplicationLayout, {
    props: {
      title: 'Modules',
      navigation: moduleNavigation({ ...data, pages }, 'pl'),
    },
  });
  expect(layout.get('.shell-nav-list').text()).not.toContain('Ukryty');
  expect(layout.get('.shell-nav-list').text()).toContain('Niedostępny');
  expect(layout.find('[aria-disabled="true"]').exists()).toBe(true);
});
it('renders stable slot identities in server order and validates exact props before mounting', async () => {
  const setup = vi.fn();
  const components = createModuleComponents(
    Object.fromEntries(
      ['a', 'b'].map((key) => [
        `app.probe.${key}`,
        {
          manifestHash: 'a'.repeat(64),
          load: async () => ({
            default: defineComponent({
              props: { label: { type: String, required: true } },
              setup(props) {
                setup(key);
                return () => h('p', key + props.label);
              },
            }),
          }),
        },
      ]),
    ),
  );
  const slot = mount(ModuleSlot, {
    props: {
      view: view(),
      components,
      name: 'app.probe.panel',
      values: { label: 'Label' },
    },
  });
  await flushPromises();
  await vi.waitFor(() => expect(slot.findAll('p')).toHaveLength(2));
  expect(slot.findAll('p').map((entry) => entry.text())).toEqual([
    'bLabel',
    'aLabel',
  ]);
  await slot.setProps({ values: { label: 3 } });
  expect(slot.findAll('p')).toHaveLength(0);
  await slot.setProps({ values: { label: 'Label', extra: true } });
  expect(slot.findAll('p')).toHaveLength(0);
  await slot.setProps({ view: null });
  expect(slot.findAll('p')).toHaveLength(0);
  expect(setup).toHaveBeenCalledTimes(2);
});
it('rejects unsafe build keys malformed contracts duplicate paths and unsupported metadata', () => {
  expect(() =>
    createModuleComponents({
      'https://bad.test': {
        manifestHash: 'a'.repeat(64),
        load: async () => ({ default: {} }),
      },
    }),
  ).toThrow();
  expect(() => decodeModuleFrontend({ ...view(), schemaVersion: 2 })).toThrow();
  expect(() =>
    decodeModuleFrontend({
      ...view(),
      pages: [...view().pages, view().pages[0]],
    }),
  ).toThrow();
  const data = view();
  const bad = {
    ...data,
    slots: {
      'app.probe.panel': {
        ...data.slots['app.probe.panel']!,
        contract: {
          ...data.slots['app.probe.panel']!.contract,
          props: { onClick: 'string' },
        },
      },
    },
  };
  expect(() => decodeModuleFrontend(bad)).toThrow();
  expect(() =>
    decodeModuleFrontend({
      ...data,
      pages: [{ ...data.pages[0], href: '//bad.test' }],
    }),
  ).toThrow();
});
it('clears stale components during refresh cancels obsolete loads and ignores late responses', async () => {
  const scope = effectScope();
  const pending: ((value: ModuleFrontendView) => void)[] = [];
  const signals: AbortSignal[] = [];
  const frontend = scope.run(() =>
    useModuleFrontend((signal) => {
      signals.push(signal);
      return new Promise((resolve) => pending.push(resolve));
    }),
  )!;
  const first = frontend.refresh();
  const second = frontend.refresh();
  expect(signals[0]!.aborted).toBe(true);
  pending[1]!(view());
  await second;
  expect(frontend.view.value?.generation).toBe(7);
  pending[0]!({ ...view(), generation: 1 });
  await first;
  expect(frontend.view.value?.generation).toBe(7);
  const third = frontend.refresh();
  expect(frontend.view.value).toBeNull();
  scope.stop();
  expect(signals[2]!.aborted).toBe(true);
  pending[2]!(view());
  await third;
  expect(frontend.view.value).toBeNull();
});
it('permits a standalone customer frontend without layout or automatic metadata requests', () => {
  const fetch = vi.spyOn(window, 'fetch');
  const content = mount(
    defineComponent({ setup: () => () => h('main', 'Customer frontend') }),
  );
  expect(content.text()).toBe('Customer frontend');
  expect(fetch).not.toHaveBeenCalled();
});

it('blocks stale build hashes for pages and slot contributions before importing code', async () => {
  const loader = vi.fn(async () => ({
    default: defineComponent({ setup: () => () => h('p', 'Stale code') }),
  }));
  const components = createModuleComponents(
    Object.fromEntries(
      ['index', 'a', 'b'].map((key) => [
        `app.probe.${key}`,
        { manifestHash: 'b'.repeat(64), load: loader },
      ]),
    ),
  );
  mount(ModulePage, {
    props: { view: view(), href: '/modules/app/probe/index', components },
  });
  mount(ModuleSlot, {
    props: {
      view: view(),
      name: 'app.probe.panel',
      values: { label: 'Label' },
      components,
    },
  });
  await flushPromises();
  expect(loader).not.toHaveBeenCalled();
});

it('keeps the retired probe out of the development registry while retaining the reference', () => {
  expect(Object.keys(loaders)).not.toContain('app.module_probe.panel');
  expect(Object.keys(loaders)).toContain('app.reference.panel');
});

it('loads the unwrapped public resource through the shared HTTP client', async () => {
  const data = view();
  const fetch = vi.spyOn(window, 'fetch').mockResolvedValue(
    new Response(JSON.stringify(data), {
      headers: { 'Content-Type': 'application/json' },
    }),
  );
  expect(await loadModuleFrontend(new AbortController().signal)).toEqual(data);
  expect(fetch.mock.calls[0]![0]).toBe('/api/modules/frontend');
});

it('renders the retained reference and reads its public endpoint with the shared client', async () => {
  const fetch = vi.spyOn(window, 'fetch').mockResolvedValue(
    new Response(JSON.stringify({ message: 'reference' }), {
      headers: { 'Content-Type': 'application/json' },
    }),
  );
  const loaded = await loaders['app.reference.panel']!.load();
  const panel = mount(loaded.default);
  expect(panel.text()).toContain('Moduł referencyjny');
  await panel.get('button').trigger('click');
  await flushPromises();
  expect(panel.text()).toContain('reference');
  expect(fetch.mock.calls[0]![0]).toBe('/api/app/reference/ping');
  panel.unmount();
});

it('shows English retry feedback after a reference request fails', async () => {
  vi.spyOn(window, 'fetch').mockRejectedValue(new Error('offline'));
  const loaded = await loaders['app.reference.panel']!.load();
  const panel = mount(loaded.default, { props: { locale: 'en' } });
  expect(panel.text()).toContain('Reference module');
  await panel.get('button').trigger('click');
  await flushPromises();
  expect(panel.get('[role="status"]').text()).toContain('Try again');
  expect(panel.get('button').attributes('disabled')).toBeUndefined();
  panel.unmount();
});

it('customization: public module page and slot composition react to props and unavailable metadata', async () => {
  const data = view();
  const components = createModuleComponents(
    Object.fromEntries(
      ['index', 'a', 'b'].map((key) => [
        `app.probe.${key}`,
        {
          manifestHash: 'a'.repeat(64),
          load: async () => ({
            default: defineComponent({
              props: { label: { type: String, default: '' } },
              setup: (props) => () =>
                h(
                  'p',
                  { 'data-module-content': key },
                  key + (props.label ?? ''),
                ),
            }),
          }),
        },
      ]),
    ),
  );
  const w = mount(CustomerModules, {
    props: {
      page: { view: data, components, href: '/modules/app/probe/index' },
      contribution: {
        view: data,
        components,
        name: 'app.probe.panel',
        values: { label: 'First' },
      },
    },
  });
  await vi.waitFor(() => expect(w.text()).toContain('bFirst'));
  expect(w.text()).toContain('index');
  await w.setProps({
    contribution: {
      view: data,
      components,
      name: 'app.probe.panel',
      values: { label: 'Next' },
    },
  });
  await vi.waitFor(() => expect(w.text()).toContain('bNext'));
  expect(w.text()).not.toContain('First');
  await w.setProps({
    page: { view: null, components, href: '/modules/app/probe/index' },
    contribution: { view: null, components, name: 'app.probe.panel' },
  });
  expect(w.get('[data-page-unavailable]').text()).toBe('page_unavailable');
  expect(w.get('[data-slot-empty]').text()).toBe('No contributions');
  expect(w.find('[data-module-content]').exists()).toBe(false);
});
