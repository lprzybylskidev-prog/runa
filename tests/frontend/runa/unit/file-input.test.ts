import { afterEach, expect, it } from '@runa/testing/vitest';
import { enableAutoUnmount, mount } from '@runa/testing/vue';
import { ref, defineComponent, h } from 'vue';
import { FileInput } from '../../../../src/runa/resources/js/runa/ui';
enableAutoUnmount(afterEach);
function transfer(files: globalThis.File[]) {
  const data = new DataTransfer();
  for (const file of files) data.items.add(file);
  return data;
}
const pdf = new File(['pdf'], 'report.pdf', { type: 'application/pdf' });
const image = new File(['image'], 'photo.png', { type: 'image/png' });
const Harness = defineComponent({
  props: { busy: Boolean },
  setup(props) {
    const files = ref<readonly globalThis.File[]>([pdf, image]);
    return () =>
      h('fieldset', { disabled: props.busy }, [
        h(FileInput, {
          modelValue: files.value,
          label: 'Files',
          multiple: true,
          maxBytes: 2,
          chooseLabel: 'Choose',
          'onUpdate:modelValue': (value: readonly globalThis.File[]) => {
            files.value = value;
          },
        }),
      ]);
  },
});
it('appends file batches, replaces a single selection and keeps limit violations visible', async () => {
  const w = mount(FileInput, {
    props: {
      modelValue: [pdf],
      label: 'Attachments',
      multiple: true,
      maxFiles: 1,
    },
  });
  const zone = w.get('.file-input');
  const dataTransfer = transfer([image]);
  await zone.trigger('dragenter', { dataTransfer });
  expect(w.get('.file-dropzone').classes()).toContain('file-dragging');
  await zone.trigger('drop', { dataTransfer });
  expect(w.emitted('update:modelValue')?.at(-1)).toEqual([[pdf, image]]);
  expect(w.get('.file-dropzone').classes()).not.toContain('file-dragging');
  await w.setProps({ modelValue: [pdf, image] });
  expect(w.emitted('issues')?.at(-1)).toEqual([[{ code: 'count' }]]);
  expect(w.findAll('li')).toHaveLength(3);
  const single = mount(FileInput, {
    props: { modelValue: [pdf], label: 'Document' },
  });
  await single.get('.file-input').trigger('drop', { dataTransfer });
  expect(single.emitted('update:modelValue')?.at(-1)).toEqual([[image]]);
  const input = w.get('input');
  input.element.files = transfer([pdf]).files;
  await input.trigger('change');
  expect(w.emitted('update:modelValue')?.at(-1)).toEqual([[pdf, image, pdf]]);
  expect(input.element.value).toBe('');
});
it('ignores non-file drags and blocks readonly, disabled and busy-fieldset drops', async () => {
  const w = mount(FileInput, {
    props: { modelValue: [pdf], label: 'Document', disabled: true },
  });
  const dataTransfer = transfer([image]);
  await w.get('.file-input').trigger('drop', { dataTransfer });
  expect(w.emitted('update:modelValue')).toBeUndefined();
  await w.setProps({ disabled: false, readonly: true });
  expect(w.find('.file-dropzone').exists()).toBe(false);
  await w.get('.file-input').trigger('drop', { dataTransfer });
  expect(w.emitted('update:modelValue')).toBeUndefined();
  await w.setProps({ readonly: false });
  const text = new DataTransfer();
  text.setData('text/plain', 'hello');
  await w.get('.file-input').trigger('dragenter', { dataTransfer: text });
  expect(w.get('.file-dropzone').classes()).not.toContain('file-dragging');
  const busy = mount(Harness, {
    props: { busy: true },
    attachTo: document.body,
  });
  await busy.get('.file-input').trigger('drop', { dataTransfer });
  expect(
    busy.getComponent(FileInput).emitted('update:modelValue'),
  ).toBeUndefined();
});
it('keeps a focusable picker/error relation and restores focus after controlled removal', async () => {
  const w = mount(Harness, { attachTo: document.body });
  expect(w.get('.file-dropzone').attributes('aria-invalid')).toBe('true');
  const ids = w
    .get('.file-dropzone')
    .attributes('aria-describedby')!
    .split(' ');
  expect(
    ids.some((id) =>
      document.getElementById(id)?.textContent?.includes('report.pdf'),
    ),
  ).toBe(true);
  expect(w.findAll('.file-row-invalid')).toHaveLength(2);
  await w.get('[data-file-remove]').trigger('click');
  expect(document.activeElement).toBe(w.get('[data-file-remove]').element);
  await w.get('[data-file-remove]').trigger('click');
  expect(document.activeElement).toBe(w.get('.file-dropzone').element);
});
