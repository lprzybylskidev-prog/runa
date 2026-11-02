import { afterEach, expect, it } from '@runa/testing/vitest';
import { enableAutoUnmount, mount } from '@runa/testing/vue';
import { defineComponent, h, ref } from 'vue';
import { provideLocale } from '../../../../src/runa/resources/js/composables/useLocale';
import {
  Alert,
  AsyncContent,
  ContentLoading,
  Footer,
  PageNavigation,
  ToastViewport,
  ViewBoundary,
  useViewReadiness,
  DateInput,
  DateTimeInput,
  TimeInput,
  NumberInput,
  ColorInput,
  FileInput,
  TagsInput,
} from '@/runa/ui';
enableAutoUnmount(afterEach);

it('localizes shared feedback and retained tag errors when the application language changes', async () => {
  const locale = ref<'pl' | 'en'>('pl');
  const emptyTitle = ref('');
  const wrapper = mount(
    defineComponent({
      setup() {
        provideLocale(locale);
        const readiness = useViewReadiness({
          identity: 'localized-error',
          required: false,
          failed: true,
        });
        return () =>
          h('div', [
            h(ContentLoading),
            h(Footer),
            h(PageNavigation, { entries: [] }),
            h(ViewBoundary, { readiness }),
            h(ToastViewport, {
              messages: [
                { id: 'notice', title: 'Notice', tone: 'info', duration: 0 },
              ],
            }),
            h(Alert, { dismissible: true }),
            h(AsyncContent, { state: 'empty', emptyTitle: emptyTitle.value }),
            h(TagsInput, { label: 'Tags', modelValue: ['Runa'] }),
          ]);
      },
    }),
  );
  expect(
    wrapper.get('[role="status"][aria-label]').attributes('aria-label'),
  ).toBe('Wczytywanie');
  expect(wrapper.find('[aria-label="Zamknij"]').exists()).toBe(true);
  expect(wrapper.text()).toContain('Brak wyników');
  expect(wrapper.text()).toContain('Nie udało się wczytać treści.');
  expect(wrapper.text()).toContain('Oparte na');
  expect(wrapper.find('[aria-label="Nawigacja strony"]').exists()).toBe(true);
  expect(document.querySelector('[aria-label="Powiadomienia"]')).not.toBeNull();
  await wrapper.get('input').setValue('Runa');
  await wrapper.get('input').trigger('keydown', { key: 'Enter' });
  expect(wrapper.text()).toContain('Ten tag już istnieje.');
  locale.value = 'en';
  await wrapper.vm.$nextTick();
  expect(
    wrapper.get('[role="status"][aria-label]').attributes('aria-label'),
  ).toBe('Loading');
  expect(wrapper.find('[aria-label="Close"]').exists()).toBe(true);
  expect(wrapper.text()).toContain('No results');
  expect(wrapper.text()).toContain('Unable to load content.');
  expect(wrapper.text()).toContain('Powered by');
  expect(wrapper.find('[aria-label="Page navigation"]').exists()).toBe(true);
  expect(document.querySelector('[aria-label="Notifications"]')).not.toBeNull();
  expect(wrapper.text()).toContain('This tag already exists.');
  expect(wrapper.get('input').element.value).toBe('Runa');
  emptyTitle.value = 'Custom empty state';
  await wrapper.vm.$nextTick();
  expect(wrapper.text()).toContain('Custom empty state');
});

it('localizes invalid temporal numeric and color drafts without changing their values', async () => {
  for (const component of [DateInput, TimeInput, DateTimeInput]) {
    const wrapper = mount(component, {
      props: { modelValue: null, label: 'Date', locale: 'pl' },
    });
    await wrapper.get('input').setValue('invalid');
    expect(wrapper.text()).toContain('Sprawdź datę lub godzinę.');
    await wrapper.setProps({ locale: 'en' });
    expect(wrapper.text()).toContain('Check the date or time.');
    expect(wrapper.get('input').element.value).toBe('invalid');
    await wrapper.setProps({ invalidMessage: 'Custom date error' });
    expect(wrapper.text()).toContain('Custom date error');
    wrapper.unmount();
  }
  const number = mount(NumberInput, {
    props: { modelValue: null, label: 'Number' },
  });
  await number.get('input').setValue('invalid');
  expect(number.text()).toContain('Podaj poprawną liczbę.');
  await number.setProps({ locale: 'en' });
  expect(number.text()).toContain('Enter a valid number.');
  const color = mount(ColorInput, {
    props: { modelValue: null, label: 'Color' },
  });
  await color.get('input').setValue('#BAD');
  expect(color.text()).toContain('Podaj kolor w formacie #RRGGBB.');
  await color.setProps({ locale: 'en' });
  expect(color.text()).toContain('Enter a color in #RRGGBB format.');
});

it('localizes file controls and all constraint failures while preserving caller messages', async () => {
  const file = new globalThis.File(['oversized'], 'document.txt', {
    type: 'text/plain',
  });
  const wrapper = mount(FileInput, {
    props: {
      label: 'Files',
      modelValue: [],
      multiple: true,
      maxFiles: 1,
      maxBytes: 1,
      accept: 'image/png',
    },
  });
  expect(wrapper.text()).toContain('Nie wybrano plików');
  expect(wrapper.find('[aria-label="Wybierz pliki: Files"]').exists()).toBe(
    true,
  );
  await wrapper.setProps({ modelValue: [file, file] });
  expect(wrapper.text()).toContain('Wybrano zbyt wiele plików.');
  expect(wrapper.text()).toContain('Plik jest zbyt duży.');
  expect(wrapper.text()).toContain('Ten typ pliku nie jest dozwolony.');
  await wrapper.setProps({ locale: 'en' });
  expect(wrapper.text()).toContain('Too many files.');
  expect(wrapper.text()).toContain('File is too large.');
  expect(wrapper.text()).toContain('File type is not accepted.');
  await wrapper.setProps({
    sizeMessage: 'Custom file limit',
    removeLabel: 'Discard',
  });
  expect(wrapper.text()).toContain('Custom file limit');
  expect(wrapper.find('[aria-label="Discard: document.txt"]').exists()).toBe(
    true,
  );
});
