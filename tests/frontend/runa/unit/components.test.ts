import { afterEach, expect, it } from '@runa/testing/vitest';
import { enableAutoUnmount, mount } from '@runa/testing/vue';
import AppForm from '../../../../src/runa/resources/js/components/AppForm.vue';
import AppSelect from '../../../../src/runa/resources/js/components/AppSelect.vue';

enableAutoUnmount(afterEach);

it('emits a controlled submit once and blocks resubmission while busy', async () => {
  const wrapper = mount(AppForm);
  expect(wrapper.attributes('novalidate')).toBeDefined();
  wrapper.element.dispatchEvent(
    new SubmitEvent('submit', { bubbles: true, cancelable: true }),
  );
  expect(wrapper.emitted('submit')).toHaveLength(1);
  await wrapper.setProps({ busy: true });
  wrapper.element.dispatchEvent(
    new SubmitEvent('submit', { bubbles: true, cancelable: true }),
  );
  expect(wrapper.emitted('submit')).toHaveLength(1);
});

it('labels a select and emits its selected value', async () => {
  const wrapper = mount(AppSelect, {
    props: {
      label: 'Language',
      modelValue: 'pl',
      options: [
        { value: 'pl', label: 'Polski' },
        { value: 'en', label: 'English' },
      ],
    },
  });
  expect(wrapper.get('label').text()).toContain('Language');
  await wrapper.get('button').trigger('click');
  document
    .querySelectorAll<globalThis.HTMLElement>('[role=option]')[1]!
    .click();
  await wrapper.vm.$nextTick();
  expect(wrapper.emitted('update:modelValue')).toEqual([['en']]);
});
