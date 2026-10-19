import { expect, it } from 'vitest';
import { mount } from '@vue/test-utils';
import { ApplicationLayout } from '@/runa/ui';

it('preserves mandatory content across optional actions, breadcrumbs, footer and heading overrides', () => {
  for (let mask = 0; mask < 16; mask++) {
    const layout = mount(ApplicationLayout, {
      props: {
        navigation: [],
        title: 'Default title',
        description: mask & 8 ? 'Optional description' : '',
        sidebar: false,
        topbar: false,
        footer: { visible: !!(mask & 4) },
      },
      slots: {
        content: '<p>Mandatory content</p>',
        ...(mask & 1 ? { actions: '<button>Action</button>' } : {}),
        ...(mask & 2 ? { breadcrumbs: 'Breadcrumbs' } : {}),
        ...(mask & 8 ? { heading: '<h1>Custom title</h1>' } : {}),
      },
    });
    expect(layout.get('main').text()).toContain('Mandatory content');
    expect(layout.find('.shell-actions').exists()).toBe(!!(mask & 1));
    expect(layout.find('.shell-breadcrumbs').exists()).toBe(!!(mask & 2));
    expect(layout.find('footer').exists()).toBe(!!(mask & 4));
    expect(layout.get('h1').text()).toBe(
      mask & 8 ? 'Custom title' : 'Default title',
    );
    layout.unmount();
  }
});
