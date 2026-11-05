import {
  authActionIntent,
  authActionGroupIntent,
  authBulkActionIntent,
} from '../../../../src/runa/modules/auth/frontend/bulkGroups';
import { afterEach, expect, it } from '@runa/testing/vitest';
import { enableAutoUnmount, mount } from '@runa/testing/vue';
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
  await wrapper.setProps({ busy: false });
  (wrapper.element as HTMLButtonElement).click();
  expect(count).toBe(1);
});
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
