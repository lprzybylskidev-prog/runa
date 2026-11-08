import {
  authActionIntent,
  authActionGroupIntent,
  authBulkActionIntent,
} from '../../../../src/runa/modules/auth/frontend/bulkGroups';
import { afterEach, expect, it } from '@runa/testing/vitest';
import { enableAutoUnmount, mount } from '@runa/testing/vue';
import PreferenceControls from '../../../../src/runa/resources/js/components/AppPreferenceControls.vue';
import { messages } from '../../../../src/runa/resources/js/locales/messages';
import {
  Button,
  ButtonGroup,
  Icon,
} from '../../../../src/runa/resources/js/runa/ui';
enableAutoUnmount(afterEach);
it('blocks native clicks while busy and preserves submit, labels and pressed state', async () => {
  let count = 0;
  const wrapper = mount(Button, {
    props: { busy: true, type: 'submit', iconOnly: true },
    slots: { default: 'Save icon' },
    attrs: {
      'aria-label': 'Save',
      'aria-pressed': 'true',
      onClick: () => count++,
    },
  });
  (wrapper.element as HTMLButtonElement).click();
  expect(count).toBe(0);
  expect(wrapper.attributes('aria-busy')).toBe('true');
  expect(wrapper.attributes('type')).toBe('submit');
  expect(wrapper.attributes('aria-label')).toBe('Save');
  expect(wrapper.attributes('aria-pressed')).toBe('true');
  expect(wrapper.text()).not.toContain('Save icon');
  await wrapper.setProps({ busy: false });
  expect(wrapper.text()).toContain('Save icon');
  (wrapper.element as HTMLButtonElement).click();
  expect(count).toBe(1);
});
it.each([
  ['sm', 36],
  ['md', 44],
  ['lg', 52],
] as const)(
  'keeps %s icon-only geometry and restores focusable activation after loading',
  async (size, pixels) => {
    const wrapper = mount(Button, {
      attachTo: document.body,
      props: { iconOnly: true, size, intent: 'inspect' },
      attrs: { 'aria-label': 'Refresh' },
      slots: { default: '↻' },
    });
    const button = wrapper.element as HTMLButtonElement;
    const dimensions = () => [
      button.getBoundingClientRect().width,
      button.getBoundingClientRect().height,
    ];
    expect(dimensions()).toEqual([pixels, pixels]);
    button.focus();
    expect(document.activeElement).toBe(button);
    await wrapper.setProps({ busy: true });
    expect(dimensions()).toEqual([pixels, pixels]);
    expect(wrapper.text()).not.toContain('↻');
    expect(wrapper.attributes('aria-label')).toBe('Refresh');
    expect(wrapper.attributes('data-tone')).toBe('info');
    await wrapper.setProps({ busy: false });
    button.focus();
    expect(document.activeElement).toBe(button);
    expect(wrapper.text()).toContain('↻');
  },
);
it.each([false, true])(
  'keeps square named preference controls with navigation=%s',
  async (navigation) => {
    const wrapper = mount(PreferenceControls, {
      attachTo: document.body,
      props: {
        navigation,
        size: 'sm',
        locale: 'pl',
        theme: 'system',
        languageLabel: 'Zmień język',
        themeLabel: 'Zmień motyw',
        t: (key) => messages.pl[key],
      },
    });
    const buttons = wrapper.findAll('button');
    expect(buttons).toHaveLength(2);
    expect(buttons.map((button) => button.attributes('aria-label'))).toEqual([
      'Zmień język',
      'Zmień motyw',
    ]);
    for (const button of buttons) {
      const bounds = button.element.getBoundingClientRect();
      expect([bounds.width, bounds.height]).toEqual([36, 36]);
    }
    if (navigation) {
      expect(
        buttons.map((button) => [
          button.attributes('type'),
          button.attributes('name'),
          button.attributes('value'),
        ]),
      ).toEqual([
        ['submit', 'lang', 'en'],
        ['submit', 'theme', 'light'],
      ]);
      expect(
        wrapper.findAll('form').map((form) => form.attributes('method')),
      ).toEqual(['get', 'get']);
    } else {
      await buttons[0]!.trigger('click');
      await buttons[1]!.trigger('click');
      expect(
        wrapper.emitted('change')?.map(([preference]) => preference),
      ).toEqual(['language', 'theme']);
    }
  },
);
it('names groups and keeps icons decorative with configurable size/color', () => {
  expect(
    mount(ButtonGroup, { props: { label: 'Period' } }).attributes('aria-label'),
  ).toBe('Period');
  const icon = mount(Icon, { props: { name: 'sun', size: 16, color: 'red' } });
  expect(icon.attributes('width')).toBe('16');
  expect(icon.attributes('stroke')).toBe('red');
  expect(icon.attributes('aria-hidden')).toBe('true');
});

it('keeps action meaning independent from feedback, labels, hierarchy and busy state', async () => {
  const wrapper = mount(Button, {
    props: { intent: 'commit', tone: 'danger' },
    slots: { default: 'Zapisz' },
  });
  expect(wrapper.attributes('data-tone')).toBe('success');
  expect(wrapper.attributes('data-variant')).toBe('outline');
  await wrapper.setProps({ variant: 'outline', busy: true });
  expect(wrapper.attributes('data-tone')).toBe('success');
  expect(wrapper.attributes('data-variant')).toBe('outline');
  expect(wrapper.attributes('disabled')).toBeDefined();
  await wrapper.setProps({ intent: 'cancel', busy: false });
  expect(wrapper.attributes('data-tone')).toBe('neutral');
  await wrapper.setProps({ intent: 'discard' });
  expect(wrapper.attributes('data-tone')).toBe('warning');
  await wrapper.setProps({ intent: 'inspect' });
  expect(wrapper.attributes('data-tone')).toBe('info');
  await wrapper.setProps({ intent: 'danger' });
  expect(wrapper.attributes('data-tone')).toBe('danger');
});

it('colors selected Auth effects rather than password requirements or mixed tool openers', () => {
  expect(authActionIntent('activate')).toBe('commit');
  expect(authActionIntent('deactivate')).toBe('caution');
  expect(authActionIntent('delete')).toBe('danger');
  expect(authActionIntent('set_security_lock', true)).toBe('caution');
  expect(authActionIntent('set_security_lock', false)).toBe('commit');
  expect(authActionIntent('set_attempt_lock', null, true)).toBe('commit');
  expect(authActionIntent('reset_preferences')).toBe('caution');
  expect(authActionIntent('revoke')).toBe('danger');
});

it('distinguishes mixed Auth tools from concrete operation effects', () => {
  expect(authBulkActionIntent(['activate', 'deactivate'])).toBe('mixed');
  expect(authBulkActionIntent(['delete', 'restore'])).toBe('mixed');
  expect(authBulkActionIntent(['set_attempt_lock'])).toBe('mixed');
  expect(authBulkActionIntent(['set_security_lock'])).toBe('mixed');
  expect(authBulkActionIntent(['factor_reset'])).toBe('danger');
  expect(authBulkActionIntent(['activate'])).toBe('commit');
  expect(authActionGroupIntent(['commit', 'commit'])).toBe('commit');
  expect(authActionGroupIntent(['commit', 'caution'])).toBe('mixed');
  expect(authActionGroupIntent(['danger'])).toBe('danger');
  expect(
    mount(Button, { props: { intent: 'mixed' } }).attributes('data-tone'),
  ).toBe('primary');
});

it.each(['sm', 'md', 'lg'] as const)(
  'semantic icons preserve %s labelled action geometry while busy',
  async (size) => {
    const wrapper = mount(Button, {
      attachTo: document.body,
      props: { size, icon: 'device-floppy', type: 'submit', intent: 'commit' },
      slots: { default: 'Zapisz zmiany' },
    });
    const button = wrapper.element as HTMLButtonElement;
    const bounds = () => [
      button.getBoundingClientRect().width,
      button.getBoundingClientRect().height,
    ];
    const original = bounds();
    const glyphSize = wrapper.get('svg').attributes('width');
    expect(wrapper.get('svg').attributes('aria-hidden')).toBe('true');
    expect(wrapper.text()).toBe('Zapisz zmiany');
    await wrapper.setProps({ busy: true });
    expect(wrapper.find('svg').exists()).toBe(false);
    expect(wrapper.get('.button-spinner').attributes('aria-hidden')).toBe(
      'true',
    );
    expect(
      wrapper.get('.app-spinner-ring').element.getBoundingClientRect().width,
    ).toBe(Number(glyphSize));
    expect(bounds()).toEqual(original);
    expect(wrapper.text()).toBe('Zapisz zmiany');
    expect(wrapper.attributes('type')).toBe('submit');
    await wrapper.setProps({ busy: false, icon: 'send' });
    expect(wrapper.getComponent(Icon).props('name')).toBe('send');
    expect(bounds()).toEqual(original);
  },
);

it('semantic icons retain native link semantics and render every catalogued glyph', async () => {
  const { LinkButton, actionIcons } = await import('@/runa/ui');
  const { iconNames } =
    await import('../../../../src/runa/resources/js/runa/ui/icons');
  const link = mount(LinkButton, {
    props: { href: '/account', icon: actionIcons.inspect },
    slots: { default: 'Konto' },
  });
  expect(link.element.tagName).toBe('A');
  expect(link.attributes('href')).toBe('/account');
  expect(link.text()).toBe('Konto');
  for (const name of iconNames) {
    const icon = mount(Icon, { props: { name } });
    expect(
      icon
        .find('path, rect, circle, line, polyline, polygon, ellipse')
        .exists(),
      name,
    ).toBe(true);
    expect(icon.attributes('aria-hidden')).toBe('true');
    icon.unmount();
  }
});

it('semantic icons retain explicit final-action selection and confirmation opt-out', async () => {
  const { ConfirmDialog } = await import('@/runa/ui');
  const wrapper = mount(ConfirmDialog, {
    props: {
      modelValue: true,
      title: 'Usuń przykład',
      confirmLabel: 'Usuń',
      confirmIntent: 'danger',
      confirmIcon: 'trash',
    },
  });
  const finalAction = () =>
    wrapper
      .findAllComponents(Button)
      .find((button) => button.text() === 'Usuń')!;
  expect(finalAction().props('icon')).toBe('trash');
  expect(finalAction().attributes('data-tone')).toBe('danger');
  await wrapper.setProps({ confirmIcon: false });
  expect(finalAction().find('svg').exists()).toBe(false);
  expect(finalAction().text()).toBe('Usuń');
});
