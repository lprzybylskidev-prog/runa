import { afterEach, expect, it } from 'vitest';
import { enableAutoUnmount, mount } from '@vue/test-utils';
import { Button, ButtonGroup, Icon } from '../../resources/js/runa/ui';
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
