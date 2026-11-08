/* eslint-disable vue/one-component-per-file -- Independent controlled-field and failure-isolation fixtures. */
import { afterEach, beforeEach, expect, it, vi } from '@runa/testing/vitest';
import { savePreference } from '../../../../src/runa/resources/js/lib/preferences';
import { enableAutoUnmount, mount } from '@runa/testing/vue';
import { defineComponent, h, nextTick, shallowRef, shallowReactive } from 'vue';
import type { PropType } from 'vue';
import {
  AdminField,
  AdminPresentationProvider,
  validateAdminField,
} from '@/runa/admin';
import type {
  AdminFieldData,
  AdminFieldProps,
  AdminFieldValue,
} from '@/runa/admin';
import { decodeRead } from '../../../../src/runa/modules/admin/frontend/decode';
import type { AdminSectionData } from '@/runa/admin';
import type { SelectionProvider } from '@/runa/ui';
import CustomerAdminField from './fixtures/customization/CustomerAdminField.vue';
enableAutoUnmount(afterEach);
afterEach(() => savePreference('runa.locale', 'en'));
beforeEach(() => {
  localStorage.setItem(
    'runa.preferences:runa:display:global:runa.locale',
    JSON.stringify({ version: 1, value: 'en' }),
  );
});
function field(change: Partial<AdminFieldData> = {}): AdminFieldData {
  return {
    key: 'name',
    type: 'text',
    label: 'Name',
    readonly: false,
    sensitive: false,
    filterable: false,
    sortable: false,
    lookup: false,
    required: false,
    max_length: null,
    choices: [],
    multiple: false,
    component: null,
    ...change,
  };
}
it.each(['pl', 'en'])(
  'read-only dictionary values %s localize declared choices without changing identifiers',
  (locale) => {
    savePreference('runa.locale', locale);
    const wrapper = mount(AdminField, {
      props: {
        field: field({
          type: 'dictionary',
          choices: ['human', 'success'],
          multiple: true,
          readonly: true,
        }),
        modelValue: ['human', 'success', 'MiXeD'],
        errors: [],
        readonly: true,
        busy: false,
      },
    });
    expect(wrapper.get('p').text()).toBe(
      locale === 'pl' ? 'człowiek, sukces, MiXeD' : 'human, success, MiXeD',
    );
  },
);
function controlled(
  descriptor: AdminFieldData,
  initial: AdminFieldValue = null,
) {
  const value = shallowRef<AdminFieldValue>(initial);
  const wrapper = mount(
    defineComponent({
      setup: () => () =>
        h(AdminField, {
          field: descriptor,
          modelValue: value.value,
          errors: [],
          readonly: false,
          busy: false,
          'onUpdate:modelValue': (next: AdminFieldValue) => {
            value.value = next;
          },
        }),
    }),
  );
  return { value, wrapper };
}

it('Admin fields retain invalid numeric drafts and refuse precision loss before a request boundary', async () => {
  const { value, wrapper } = controlled(
    field({ type: 'integer', label: 'Count', required: true }),
    12,
  );
  await wrapper.get('input').setValue('-');
  expect(value.value).toBe('-');
  expect(wrapper.get('input').attributes('aria-invalid')).toBe('true');
  await wrapper.get('input').setValue('9007199254740993');
  expect(value.value).toBe('9007199254740993');
  expect(
    validateAdminField(field({ type: 'integer' }), value.value),
  ).not.toEqual([]);
  await wrapper.get('input').setValue('27');
  expect(value.value).toBe(27);
  expect(wrapper.get('input').attributes('aria-invalid')).toBeUndefined();
  await wrapper.get('input').setValue('');
  expect(value.value).toBeNull();
  expect(wrapper.text()).toContain('This field is required.');
});

it('Admin fields associate controlled hints and server errors without truncating Unicode or IME drafts', async () => {
  const { value, wrapper } = controlled(
    field({ max_length: 2, required: true }),
    '',
  );
  expect(wrapper.get('input').attributes('aria-invalid')).toBeUndefined();
  await wrapper.get('input').setValue('😀é');
  expect(validateAdminField(field({ max_length: 2 }), value.value)).toEqual([]);
  await wrapper.get('input').setValue('😀éx');
  expect(value.value).toBe('😀éx');
  expect(wrapper.text()).toContain('Use at most 2 characters.');
  expect(wrapper.get('label').attributes('for')).toBe(
    wrapper.get('input').attributes('id'),
  );
  const input = wrapper.get('input').element;
  input.value = 'composing';
  input.dispatchEvent(
    new InputEvent('input', { bubbles: true, isComposing: true }),
  );
  expect(value.value).toBe('😀éx');
  const server = mount(AdminField, {
    props: {
      field: field(),
      modelValue: 'Draft',
      errors: ['Owner refused this value.'],
      readonly: false,
      busy: false,
    },
  });
  expect(server.text()).toContain('Owner refused this value.');
  expect(server.get('input').attributes('aria-describedby')).toBeTruthy();
});

it('Admin fields keep secret set-state out of password inputs and erase presentation on readonly changes', async () => {
  const wrapper = mount(AdminField, {
    props: {
      field: field({ sensitive: true }),
      modelValue: { set: true },
      errors: [],
      readonly: false,
      busy: false,
    },
  });
  expect(wrapper.get('input').element.value).toBe('');
  expect(wrapper.get('input').attributes('type')).toBe('password');
  expect(wrapper.text()).toContain('A value is set');
  await wrapper.get('input').setValue('transient-replacement');
  expect(wrapper.emitted('update:modelValue')?.at(-1)).toEqual([
    'transient-replacement',
  ]);
  await wrapper.setProps({ modelValue: { set: true }, readonly: true });
  expect(wrapper.find('input').exists()).toBe(false);
  expect(wrapper.text()).toContain('Set');
  expect(wrapper.html()).not.toContain('transient-replacement');
  expect(wrapper.html()).not.toContain('[object Object]');
});

it('Admin fields render nullable booleans and application timezone edits while preserving UTC values', async () => {
  const { value, wrapper } = controlled(
    field({ type: 'boolean', label: 'Enabled' }),
  );
  expect(wrapper.get('input').attributes('aria-checked')).toBe('mixed');
  await wrapper.get('input').setValue(true);
  expect(value.value).toBe(true);
  const datetime = controlled(field({ type: 'datetime', label: 'Deadline' }));
  await datetime.wrapper.get('input').setValue('2026-10-30T12:00:00+02:00');
  expect(datetime.wrapper.get('input').attributes('aria-invalid')).toBe('true');
  await datetime.wrapper.get('input').setValue('2026-10-30 11:00:00');
  expect(datetime.value.value).toBe('2026-10-30T10:00:00.000Z');
  expect(
    datetime.wrapper.get('input').attributes('aria-invalid'),
  ).toBeUndefined();
  await datetime.wrapper.get('input').setValue('2026-03-29 02:30:00');
  expect(datetime.wrapper.get('input').attributes('aria-invalid')).toBe('true');
  expect(
    validateAdminField(field({ type: 'datetime' }), datetime.value.value),
  ).not.toEqual([]);
  await datetime.wrapper.get('input').setValue('2026-02-30 10:00:00');
  expect(datetime.wrapper.get('input').attributes('aria-invalid')).toBe('true');
});

it('Admin fields use English bounded selection controls and require an explicit lookup provider', async () => {
  const { value, wrapper } = controlled(
    field({ type: 'dictionary', choices: ['enabled', 'disabled'] }),
  );
  await wrapper.get('button[aria-haspopup="dialog"]').trigger('click');
  const option = document.querySelector('[role="option"]');
  // Teleported selection is owned by the shared Select, with no native select popup.
  expect(option).not.toBeNull();
  (option as HTMLElement).click();
  await vi.waitFor(() => expect(value.value).toBe('enabled'));
  expect(wrapper.find('select').exists()).toBe(false);
  const missing = mount(AdminField, {
    props: {
      field: field({ type: 'uuid', lookup: true }),
      modelValue: null,
      errors: [],
      readonly: false,
      busy: false,
    },
  });
  expect(missing.text()).toContain('This lookup is currently unavailable.');
  expect(missing.find('input').exists()).toBe(false);
});

it('Admin fields preserve public customer replacement contracts and block stale or readonly emissions', async () => {
  const props = shallowReactive<AdminFieldProps>({
    field: field(),
    modelValue: 'Current',
    errors: [],
    readonly: false,
    busy: false,
  });
  const wrapper = mount(AdminPresentationProvider, {
    props: { components: { field: CustomerAdminField } },
    slots: { default: () => h(AdminField, props) },
  });
  expect(wrapper.text()).toContain('Customer Name');
  const control = wrapper.getComponent(AdminField);
  const replacement = wrapper.getComponent(CustomerAdminField);
  replacement.vm.$emit('update:modelValue', 'Changed');
  expect(control.emitted('update:modelValue')).toEqual([['Changed']]);
  props.readonly = true;
  await nextTick();
  replacement.vm.$emit('update:modelValue', 'Forbidden');
  expect(control.emitted('update:modelValue')).toEqual([['Changed']]);
  props.readonly = false;
  props.busy = true;
  await nextTick();
  replacement.vm.$emit('update:modelValue', 'Concurrent');
  expect(control.emitted('update:modelValue')).toEqual([['Changed']]);
  props.field = field({
    component: {
      key: 'app.missing',
      owner: 'app',
      manifest_hash: null,
      build_id: null,
    },
  });
  props.busy = false;
  await nextTick();
  expect(wrapper.findComponent(CustomerAdminField).exists()).toBe(false);
  expect(wrapper.find('input').exists()).toBe(false);
});

it('Admin fields keep lookup UUIDs and off-page labels in the owning bounded selection lane', async () => {
  const identity = '01900000-0000-7000-8000-000000000001';
  const second = '01900000-0000-7000-8000-000000000002';
  const provider = vi.fn<SelectionProvider>(async () => ({
    options: [{ value: second, label: 'Another owner' }],
    nextCursor: null,
  }));
  const wrapper = mount(AdminField, {
    props: {
      field: field({ type: 'uuid', lookup: true, multiple: true }),
      modelValue: [identity],
      errors: [],
      readonly: false,
      busy: false,
      lookup: {
        provider,
        selectedOptions: [{ value: identity, label: 'Current owner' }],
      },
    },
  });
  expect(wrapper.text()).toContain('Current owner');
  expect(provider).not.toHaveBeenCalled();
  await wrapper.get('button[aria-haspopup="dialog"]').trigger('click');
  await vi.waitFor(() =>
    expect(document.querySelector('[role="option"]')?.textContent).toContain(
      'Another owner',
    ),
  );
  expect(provider).toHaveBeenCalledTimes(1);
  expect(provider.mock.calls[0]![0]).toMatchObject({
    cursor: null,
    limit: 40,
    query: '',
  });
  (document.querySelector('[role="option"]') as HTMLElement).click();
  await vi.waitFor(() =>
    expect(wrapper.emitted('update:modelValue')?.at(-1)).toEqual([
      [identity, second],
    ]),
  );
  await wrapper.setProps({ busy: true });
  expect(document.querySelector('[role="option"]')).toBeNull();
  expect(provider.mock.calls[0]![0].signal.aborted).toBe(true);
});

it('Admin fields isolate a broken customer field while preserving readable siblings', async () => {
  const replacement = defineComponent({
    props: {
      field: { type: Object as PropType<AdminFieldData>, required: true },
    },
    setup(props) {
      if (props.field.key === 'broken')
        throw new Error('Fixture presentation failure');
      return () => h('p', 'Healthy sibling');
    },
  });
  const wrapper = mount(AdminPresentationProvider, {
    props: { components: { field: replacement } },
    slots: {
      default: () =>
        ['broken', 'healthy'].map((key) =>
          h(AdminField, {
            field: field({ key }),
            modelValue: 'Readable',
            errors: [],
            readonly: true,
            busy: false,
          }),
        ),
    },
  });
  await vi.waitFor(() => expect(wrapper.text()).toContain('Healthy sibling'));
  expect(wrapper.text()).toContain('unavailable');
});

it.each(['pl', 'en'] as const)(
  'setting choice labels %s preserve submitted API values',
  async (locale) => {
    savePreference('runa.locale', locale);
    const wrapper = mount(AdminField, {
      props: {
        field: field({
          type: 'dictionary',
          key: 'password_session_policy',
          choices: ['keep_all', 'revoke_others'],
        }),
        modelValue: 'keep_all',
        errors: [],
        readonly: false,
        busy: false,
      },
      attachTo: document.body,
    });
    const trigger = wrapper.get('button');
    await trigger.trigger('click');
    const option = Array.from(
      document.querySelectorAll<globalThis.HTMLElement>('[role="option"]'),
    ).find((item) =>
      item.textContent?.includes(
        locale === 'pl'
          ? 'Unieważnij pozostałe sesje'
          : 'Revoke other sessions',
      ),
    );
    expect(option).toBeDefined();
    option!.click();
    await nextTick();
    expect(wrapper.emitted('update:modelValue')?.at(-1)).toEqual([
      'revoke_others',
    ]);
  },
);

it('readonly account booleans share localized semantic badges and retain null facts', async () => {
  savePreference('runa.locale', 'en');
  const wrapper = mount(AdminField, {
    props: {
      field: field({ key: 'security_locked', type: 'boolean', readonly: true }),
      modelValue: true,
      errors: [],
      readonly: true,
      busy: false,
    },
  });
  expect(wrapper.get('[data-tone=danger]').text()).toBe('Yes');
  savePreference('runa.locale', 'pl');
  await vi.waitFor(() =>
    expect(wrapper.get('[data-tone=danger]').text()).toBe('Tak'),
  );
  await wrapper.setProps({ modelValue: false });
  expect(wrapper.get('[data-tone=neutral]').text()).toBe('Nie');
  await wrapper.setProps({ modelValue: null });
  expect(wrapper.text()).toContain('—');
  expect(wrapper.find('[data-tone]').exists()).toBe(false);
});

it.each(['boolean', 'dictionary', 'integer'])(
  'compact settings %s controls retain external label targets',
  (type) => {
    const wrapper = mount(
      defineComponent({
        setup() {
          return () =>
            h('div', [
              h('label', { for: 'compact-control' }, 'External setting label'),
              h(AdminField, {
                id: 'compact-control',
                hideLabel: true,
                field: field({
                  type,
                  readonly: false,
                  choices: type === 'dictionary' ? ['human'] : [],
                }),
                modelValue:
                  type === 'boolean'
                    ? false
                    : type === 'integer'
                      ? 12
                      : 'human',
                errors: [],
                readonly: false,
                busy: false,
              }),
            ]);
        },
      }),
    );
    const control = wrapper.get('#compact-control');
    expect(['INPUT', 'BUTTON']).toContain(control.element.tagName);
    expect(wrapper.get('label[for="compact-control"]').attributes('for')).toBe(
      control.attributes('id'),
    );
    expect(wrapper.find('.sr-only').exists()).toBe(true);
  },
);

it('structured fields decode native data, refuse encoded or scalar alternatives and preserve text validation', () => {
  const descriptor: AdminSectionData = {
    key: 'settings',
    title: 'Settings',
    view: 'record',
    readonly: false,
    required: false,
    component: null,
    commands: [],
    record: null,
    diagnostic: null,
    fields: [field({ key: 'values', type: 'json' })],
  };
  const read = (value: AdminFieldValue) =>
    decodeRead(
      {
        data: {
          public_id: null,
          revision: '1',
          values: { values: value },
        },
      },
      { kind: 'record' },
      descriptor,
    );
  for (const value of [
    [],
    { label: '<script>unsafe</script>', nested: [true, null] },
    null,
  ]) {
    expect(read(value)).toEqual({
      public_id: null,
      revision: '1',
      values: { values: value },
    });
    expect(validateAdminField(descriptor.fields[0]!, value)).toEqual([]);
  }
  for (const value of ['[]', '{}', '', 1, false]) {
    expect(() => read(value)).toThrow('Invalid Admin field value');
    expect(validateAdminField(descriptor.fields[0]!, value)).not.toEqual([]);
  }
  descriptor.fields[0] = field({ key: 'values', type: 'text' });
  expect(() => read({ label: 'text' })).toThrow('Invalid Admin field value');
});

it('structured readonly fields escape their data and generic writable fields refuse text editing', () => {
  const readonly = mount(AdminField, {
    props: {
      field: field({ type: 'json', readonly: true }),
      modelValue: { label: '<script>unsafe</script>' },
      errors: [],
      readonly: true,
      busy: false,
    },
  });
  expect(readonly.text()).toContain('<script>unsafe</script>');
  expect(readonly.find('script').exists()).toBe(false);
  const writable = controlled(field({ type: 'json' }), { enabled: true });
  expect(writable.wrapper.text()).toContain(
    'This field is currently unavailable.',
  );
  expect(writable.wrapper.find('input').exists()).toBe(false);
});
