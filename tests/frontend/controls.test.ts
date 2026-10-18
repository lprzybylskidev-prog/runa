import { afterEach, expect, it } from 'vitest';
import { enableAutoUnmount, mount } from '@vue/test-utils';
import { nextTick } from 'vue';
import {
  Avatar,
  Button,
  ButtonGroup,
  DropdownButton,
  Icon,
} from '../../resources/js/runa/ui';
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
it('falls back after failed images, retries a changed URL and rejects unsafe sources', async () => {
  const avatar = mount(Avatar, {
    props: { name: 'Anna Nowak', src: '/missing.png' },
  });
  await avatar.get('img').trigger('error');
  expect(avatar.text()).toBe('AN');
  expect(avatar.attributes('aria-label')).toBe('Anna Nowak');
  await avatar.setProps({ src: '/new.png' });
  expect(avatar.find('img').exists()).toBe(true);
  await avatar.setProps({ src: 'javascript:alert(1)', name: '' });
  expect(avatar.find('img').exists()).toBe(false);
  expect(avatar.text()).toBe('?');
});
it('supports dropdown keyboard navigation, disabled skipping, selection and focus return', async () => {
  const dropdown = mount(DropdownButton, {
    attachTo: document.body,
    props: {
      label: 'Actions',
      actions: [
        { id: 'save', label: 'Save' },
        { id: 'delete', label: 'Delete', disabled: true },
        { id: 'copy', label: 'Copy' },
      ],
    },
  });
  const trigger = dropdown.get('button');
  await trigger.trigger('keydown', { key: 'ArrowDown' });
  await nextTick();
  expect(document.activeElement?.textContent).toBe('Save');
  await dropdown
    .get('.dropdown-actions')
    .trigger('keydown', { key: 'ArrowDown' });
  expect(document.activeElement?.textContent).toBe('Copy');
  await dropdown.get('.dropdown-actions').trigger('keydown', { key: 'Escape' });
  expect(dropdown.find('.dropdown-actions').exists()).toBe(false);
  expect(document.activeElement).toBe(trigger.element);
  await trigger.trigger('click');
  await dropdown.get('.dropdown-actions button').trigger('click');
  expect(dropdown.emitted('select')).toEqual([['save']]);
  expect(document.activeElement).toBe(trigger.element);
  await trigger.trigger('click');
  await dropdown.setProps({ disabled: true });
  expect(dropdown.find('.dropdown-actions').exists()).toBe(false);
});
it('separates split primary activation from dropdown action selection and dismisses outside', async () => {
  const dropdown = mount(DropdownButton, {
    attachTo: document.body,
    props: {
      label: 'Save',
      split: true,
      actions: [{ id: 'copy', label: 'Copy' }],
    },
  });
  await dropdown.get('button').trigger('click');
  expect(dropdown.emitted('click')).toHaveLength(1);
  const toggle = dropdown.get('[aria-controls]');
  await toggle.trigger('click');
  document.body.dispatchEvent(
    new PointerEvent('pointerdown', { bubbles: true }),
  );
  await nextTick();
  expect(dropdown.find('.dropdown-actions').exists()).toBe(false);
});
it('keeps an action list inside the viewport near its right edge and closes on resize', async () => {
  const dropdown = mount(DropdownButton, {
    attachTo: document.body,
    props: { label: 'Actions', actions: [{ id: 'save', label: 'Save' }] },
    attrs: { style: 'position: fixed; right: 4px; top: 4px' },
  });
  await dropdown.get('button').trigger('click');
  await nextTick();
  expect(
    dropdown.get('.dropdown-actions').element.getBoundingClientRect().right,
  ).toBeLessThanOrEqual(window.innerWidth - 7);
  window.dispatchEvent(new Event('resize'));
  await nextTick();
  expect(dropdown.find('.dropdown-actions').exists()).toBe(false);
});
