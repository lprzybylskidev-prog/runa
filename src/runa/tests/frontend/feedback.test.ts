/* eslint-disable vue/one-component-per-file -- Independent mounted harnesses exercise feedback integration. */
import { afterEach, expect, it, vi } from 'vitest';
import { enableAutoUnmount, mount } from '@vue/test-utils';
import { defineComponent, h, nextTick } from 'vue';
import {
  Alert,
  Button,
  Progress,
  Statistic,
  AsyncContent,
  Badge,
  Status,
  Tooltip,
  Popover,
  ToastViewport,
  FormNotifications,
  Dialog,
  useToasts,
} from '../../resources/js/runa/ui';
import AppToast from '../../resources/js/components/AppToast.vue';
enableAutoUnmount(afterEach);
afterEach(() => vi.useRealTimers());
const settle = async () => {
  await nextTick();
  await nextTick();
  await nextTick();
};
type Trigger = { toggle: () => void; open: boolean; panelId: string };
function trigger({ toggle, open, panelId }: Trigger) {
  return h(
    'button',
    { onClick: toggle, 'aria-expanded': open, 'aria-controls': panelId },
    'Details',
  );
}
it('uses alert/status semantics without making static badges live regions', async () => {
  const alert = mount(Alert, {
    props: { tone: 'danger', title: 'Unable to save', dismissible: true },
    slots: { default: 'Retry without losing data' },
  });
  expect(alert.attributes('role')).toBe('alert');
  expect(alert.text()).toContain('Retry without losing data');
  await alert.get('button').trigger('click');
  expect(alert.emitted('close')).toHaveLength(1);
  await alert.setProps({ tone: 'success' });
  expect(alert.attributes('role')).toBe('status');
  await alert.setProps({ announce: false });
  expect(alert.attributes('role')).toBeUndefined();
  expect(mount(Badge).attributes('role')).toBeUndefined();
  expect(mount(Status, { props: { announce: true } }).attributes('role')).toBe(
    'status',
  );
});
it('dismisses rapid tooltip hover, permits pointer entry into content and honors Escape', async () => {
  vi.useFakeTimers();
  const wrapper = mount(Tooltip, {
    attachTo: document.body,
    props: { text: 'Description' },
    slots: { default: '<button>Action</button>' },
  });
  await wrapper.get('.app-tooltip').trigger('pointerenter');
  await settle();
  expect(document.querySelector('[role="tooltip"]')).not.toBeNull();
  await wrapper.get('.app-tooltip').trigger('pointerleave');
  document
    .querySelector('[role="tooltip"]')!
    .dispatchEvent(new PointerEvent('pointerenter'));
  await vi.advanceTimersByTimeAsync(100);
  await settle();
  expect(document.querySelector('[role="tooltip"]')).not.toBeNull();
  document.dispatchEvent(
    new KeyboardEvent('keydown', { key: 'Escape', bubbles: true }),
  );
  await settle();
  expect(document.querySelector('[role="tooltip"]')).toBeNull();
  for (let n = 0; n < 4; n++) {
    await wrapper.get('.app-tooltip').trigger('pointerenter');
    await wrapper.get('.app-tooltip').trigger('pointerleave');
  }
  await vi.advanceTimersByTimeAsync(100);
  await settle();
  expect(document.querySelector('[role="tooltip"]')).toBeNull();
});
it('keeps Escape dismissal through duplicate pointer entry until the hover session ends', async () => {
  const wrapper = mount(Tooltip, {
    attachTo: document.body,
    props: { text: 'Theme' },
    slots: { default: '<button>Theme</button>' },
  });
  const anchor = wrapper.get('.app-tooltip');
  await anchor.trigger('pointerenter');
  await settle();
  expect(document.querySelector('[role="tooltip"]')).not.toBeNull();
  document.dispatchEvent(
    new KeyboardEvent('keydown', { key: 'Escape', bubbles: true }),
  );
  await settle();
  await anchor.trigger('pointerenter');
  await settle();
  expect(document.querySelector('[role="tooltip"]')).toBeNull();
  await anchor.trigger('pointerleave');
  await anchor.trigger('pointerenter');
  await settle();
  expect(document.querySelector('[role="tooltip"]')).not.toBeNull();
});
it('keeps Escape dismissal when the first pointer entry arrives during existing keyboard focus', async () => {
  vi.useFakeTimers();
  const wrapper = mount(Tooltip, {
    attachTo: document.body,
    props: { text: 'Theme' },
    slots: { default: '<button>Theme</button>' },
  });
  const button = wrapper.get('button').element as HTMLButtonElement;
  button.focus();
  await settle();
  expect(document.querySelector('[role="tooltip"]')).not.toBeNull();
  document.dispatchEvent(
    new KeyboardEvent('keydown', { key: 'Escape', bubbles: true }),
  );
  await settle();
  await wrapper.get('.app-tooltip').trigger('pointerenter');
  await settle();
  expect(document.querySelector('[role="tooltip"]')).toBeNull();
  await wrapper.get('.app-tooltip').trigger('pointerleave');
  button.blur();
  await vi.advanceTimersByTimeAsync(100);
  button.focus();
  await settle();
  expect(document.querySelector('[role="tooltip"]')).not.toBeNull();
});
it('hides clicked preference tooltips even when the trigger retains pointer focus', async () => {
  const wrapper = mount(Tooltip, {
    attachTo: document.body,
    props: { text: 'Theme' },
    slots: { default: '<button>Theme</button>' },
  });
  await wrapper.get('.app-tooltip').trigger('pointerenter');
  await settle();
  await wrapper.get('button').trigger('click');
  await settle();
  expect(document.querySelector('[role="tooltip"]')).toBeNull();
});
it('opens a named popover, focuses its action and restores trigger focus on Escape', async () => {
  const wrapper = mount(Popover, {
    attachTo: document.body,
    props: { label: 'Information' },
    slots: { trigger, default: () => h('button', 'Inside') },
  });
  await wrapper.get('button').trigger('click');
  await settle();
  const panel = document.querySelector<HTMLElement>('[role="dialog"]')!;
  expect(panel.getAttribute('aria-label')).toBe('Information');
  expect(document.activeElement?.textContent).toBe('Inside');
  document.dispatchEvent(
    new KeyboardEvent('keydown', { key: 'Escape', bubbles: true }),
  );
  await settle();
  expect(document.querySelector('[role="dialog"]')).toBeNull();
  expect(document.activeElement).toBe(wrapper.get('button').element);
  await wrapper.get('button').trigger('click');
  await settle();
  document.body.dispatchEvent(
    new PointerEvent('pointerdown', { bubbles: true }),
  );
  await settle();
  expect(document.querySelector('[role="dialog"]')).toBeNull();
});
it('closes only the top nested popover on Escape', async () => {
  const wrapper = mount(Popover, {
    attachTo: document.body,
    props: { label: 'Outer' },
    slots: {
      trigger,
      default: () =>
        h(
          Popover,
          { label: 'Inner' },
          { trigger, default: () => h('button', 'Inner action') },
        ),
    },
  });
  await wrapper.get('button').trigger('click');
  await settle();
  (
    document.querySelector('[aria-label="Outer"] button') as HTMLButtonElement
  ).click();
  await settle();
  expect(document.querySelectorAll('[role="dialog"]')).toHaveLength(2);
  document.dispatchEvent(
    new KeyboardEvent('keydown', { key: 'Escape', bubbles: true }),
  );
  await settle();
  expect(document.querySelectorAll('[role="dialog"]')).toHaveLength(1);
  expect(document.querySelector('[aria-label="Outer"]')).not.toBeNull();
});
it('pauses toast time on hover and focus, then resumes only the remaining time', async () => {
  vi.useFakeTimers();
  const wrapper = mount(AppToast, {
    attachTo: document.body,
    attrs: { style: 'pointer-events: none' },
    props: {
      message: { id: 'one', title: 'Saved', tone: 'success', duration: 1000 },
      closeLabel: 'Close',
    },
  });
  await vi.advanceTimersByTimeAsync(400);
  await wrapper.trigger('pointerenter');
  await vi.advanceTimersByTimeAsync(2000);
  expect(wrapper.emitted('close')).toBeUndefined();
  await wrapper.get('button').trigger('focusin');
  await wrapper.trigger('pointerleave');
  await vi.advanceTimersByTimeAsync(2000);
  expect(wrapper.emitted('close')).toBeUndefined();
  await wrapper
    .get('button')
    .trigger('focusout', { relatedTarget: document.body });
  await vi.advanceTimersByTimeAsync(599);
  expect(wrapper.emitted('close')).toBeUndefined();
  await vi.advanceTimersByTimeAsync(2);
  expect(wrapper.emitted('close')).toEqual([['one']]);
});
it('keeps errors persistent and restores the invoking control when closing a focused toast', async () => {
  vi.useFakeTimers();
  const Harness = defineComponent({
    setup() {
      const { messages, show, dismiss } = useToasts();
      return () =>
        h('div', [
          h(
            'button',
            { onClick: () => show({ title: 'Failure', tone: 'danger' }) },
            'Notify',
          ),
          h(ToastViewport, { messages: messages.value, onDismiss: dismiss }),
        ]);
    },
  });
  const wrapper = mount(Harness, { attachTo: document.body });
  const triggerButton = wrapper.get('button');
  (triggerButton.element as HTMLButtonElement).focus();
  await triggerButton.trigger('click');
  await settle();
  expect(
    document.querySelector('.toast-viewport')?.getAttribute('aria-live'),
  ).toBe('polite');
  await vi.advanceTimersByTimeAsync(10000);
  await settle();
  const close = document.querySelector<HTMLButtonElement>('.app-toast button')!;
  expect(close).not.toBeNull();
  close.focus();
  close.click();
  await settle();
  expect(document.querySelector('.app-toast')).toBeNull();
  expect(document.activeElement).toBe(triggerButton.element);
});
it('keeps a keyboard-focused tooltip until focus leaves, then releases it', async () => {
  vi.useFakeTimers();
  const wrapper = mount(Tooltip, {
    attachTo: document.body,
    attrs: {
      style: 'position: fixed; right: 8px; bottom: 8px; pointer-events: none',
    },
    props: { text: 'Keyboard help' },
    slots: { default: () => h('button', 'Help') },
  });
  const button = wrapper.get('button').element as HTMLButtonElement;
  button.focus();
  await settle();
  expect(document.querySelector('[role="tooltip"]')).not.toBeNull();
  await wrapper.get('.app-tooltip').trigger('pointerenter');
  await wrapper.get('.app-tooltip').trigger('pointerleave');
  await vi.advanceTimersByTimeAsync(100);
  await settle();
  expect(document.querySelector('[role="tooltip"]')).not.toBeNull();
  button.blur();
  await vi.advanceTimersByTimeAsync(100);
  await settle();
  expect(document.querySelector('[role="tooltip"]')).toBeNull();
});
it('returns through normal trigger order on Tab and Shift+Tab instead of trapping focus', async () => {
  const Harness = defineComponent({
    setup: () => () =>
      h('div', [
        h(
          Popover,
          { label: 'Tab test' },
          { trigger, default: () => h('button', 'Inside') },
        ),
        h('button', { id: 'after-popover' }, 'After'),
      ]),
  });
  const wrapper = mount(Harness, { attachTo: document.body });
  await wrapper.get('button').trigger('click');
  await settle();
  document.activeElement!.dispatchEvent(
    new KeyboardEvent('keydown', {
      key: 'Tab',
      bubbles: true,
      cancelable: true,
    }),
  );
  await settle();
  expect(document.querySelector('[role="dialog"]')).toBeNull();
  expect(document.activeElement).toBe(wrapper.get('#after-popover').element);
  await wrapper.get('button').trigger('click');
  await settle();
  document.activeElement!.dispatchEvent(
    new KeyboardEvent('keydown', {
      key: 'Tab',
      shiftKey: true,
      bubbles: true,
      cancelable: true,
    }),
  );
  await settle();
  expect(document.activeElement).toBe(wrapper.get('button').element);
});

it('inherits semantic tones through nested slots and updates them reactively', async () => {
  const wrapper = mount(Alert, {
    props: { tone: 'danger' },
    slots: {
      default: () => [
        h(Badge, {}, () => 'Badge'),
        h(Status, {}, () => 'Status'),
        h(Progress, { value: 50, label: 'Progress' }),
        h(Statistic, { label: 'Count', value: 5 }),
      ],
      actions: () => h(Button, {}, () => 'Retry'),
    },
  });
  for (const selector of [
    '.app-badge',
    '.app-status',
    '.app-progress',
    '.app-statistic',
    'button',
  ])
    expect(wrapper.get(selector).attributes('data-tone')).toBe('danger');
  expect(wrapper.get('button').attributes('data-variant')).toBe('outline');
  await wrapper.setProps({ tone: 'success' });
  expect(wrapper.get('button').attributes('data-tone')).toBe('success');
  await wrapper.setProps({ tone: 'neutral' });
  expect(wrapper.get('button').attributes('data-variant')).toBe('neutral');
});
it('respects nearest explicit tones, explicit button variants and standalone defaults', () => {
  const wrapper = mount(Alert, {
    props: { tone: 'danger' },
    slots: {
      default: () =>
        h(
          Alert,
          { tone: 'warning' },
          {
            actions: () => [
              h(Button, {}, () => 'Inherited'),
              h(Button, { tone: 'info', variant: 'outline' }, () => 'Explicit'),
            ],
          },
        ),
    },
  });
  const buttons = wrapper.findAll('button');
  expect(buttons[0]!.attributes('data-tone')).toBe('warning');
  expect(buttons[1]!.attributes('data-tone')).toBe('info');
  expect(buttons[1]!.attributes('data-variant')).toBe('outline');
  const button = mount(Button);
  expect(button.attributes('data-tone')).toBe('primary');
  expect(button.attributes('data-variant')).toBe('neutral');
  expect(mount(Badge).attributes('data-tone')).toBe('neutral');
});
it('renders a matching retry action in every AsyncContent error', () => {
  const wrapper = mount(AsyncContent, {
    props: { state: 'error', retryLabel: 'Retry' },
  });
  expect(wrapper.get('button').attributes('data-tone')).toBe('danger');
  expect(wrapper.get('button').attributes('data-variant')).toBe('outline');
});

it('shows one top-right form result, keeps failures persistent with recovery actions and leaves no inline duplicate', async () => {
  const retry = vi.fn();
  const wrapper = mount(FormNotifications, {
    props: { success: 'Zapisano dane.' },
    slots: { actions: () => h(Button, { onClick: retry }, () => 'Ponów') },
  });
  await settle();
  expect(wrapper.text()).toBe('');
  expect(document.querySelectorAll('.app-toast')).toHaveLength(1);
  expect(document.querySelector('.app-toast')?.textContent).toContain(
    'Zapisano dane.',
  );
  await wrapper.setProps({ success: '', error: 'Nie zapisano danych.' });
  await settle();
  await vi.waitFor(() =>
    expect(document.querySelectorAll('.app-toast')).toHaveLength(1),
  );
  const toast = wrapper.getComponent(AppToast);
  expect(toast.props('message').duration).toBe(0);
  const retryButton = Array.from(
    document.querySelectorAll<HTMLButtonElement>('.app-toast button'),
  ).find((button) => button.textContent?.trim() === 'Ponów')!;
  retryButton.click();
  expect(retry).toHaveBeenCalledOnce();
  await wrapper.setProps({ error: '', invalid: true });
  await settle();
  await vi.waitFor(() =>
    expect(document.querySelectorAll('.app-toast')).toHaveLength(1),
  );
  expect(document.querySelector('.app-toast')?.textContent).toContain(
    'Sprawdź zaznaczone pola',
  );
  wrapper
    .getComponent(AppToast)
    .vm.$emit('close', wrapper.getComponent(AppToast).props('message').id);
  await vi.waitFor(() =>
    expect(document.querySelectorAll('.app-toast')).toHaveLength(0),
  );
  await wrapper.setProps({ attempt: 1 });
  await settle();
  expect(document.querySelectorAll('.app-toast')).toHaveLength(1);
});
it('keeps modal form notifications and their recovery buttons inside the active modal focus boundary', async () => {
  const wrapper = mount(Dialog, {
    attachTo: document.body,
    props: { modelValue: true, title: 'Test' },
    slots: {
      default: () =>
        h(
          FormNotifications,
          { error: 'Błąd kadrowania' },
          { actions: () => h(Button, {}, () => 'Ponów') },
        ),
    },
  });
  await settle();
  await vi.waitFor(() =>
    expect(
      document.querySelector('.dialog-backdrop .app-toast'),
    ).not.toBeNull(),
  );
  const button = Array.from(
    document.querySelectorAll<HTMLButtonElement>('.app-toast button'),
  ).find((value) => value.textContent?.trim() === 'Ponów')!;
  button.focus();
  expect(document.activeElement).toBe(button);
  expect(button.closest('[inert]')).toBeNull();
  await wrapper.setProps({ modelValue: false });
});
