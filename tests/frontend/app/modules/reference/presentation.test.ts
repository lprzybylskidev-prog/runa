import { afterEach, expect, it, vi } from '@runa/testing/vitest';
import { DOMWrapper, enableAutoUnmount, mount } from '@runa/testing/vue';
import Panel from '../../../../../src/app/modules/reference/frontend/components/panel.vue';
import Widget from '../../../../../src/app/modules/reference/frontend/components/response_widget.vue';
import { Button } from '@/runa/ui';
import { computed, defineComponent, h, ref, shallowRef } from 'vue';
import InspectSection from '../../../../../src/app/modules/reference/frontend/components/inspect_section.vue';
import { useAdminForm } from '@/runa/admin';
import type {
  AdminWidgetData,
  AdminFormController,
  AdminPageController,
  AdminPageData,
  AdminSectionData,
} from '@/runa/admin';

enableAutoUnmount(afterEach);
afterEach(() => vi.restoreAllMocks());
const response = (message: string) =>
  new Response(JSON.stringify({ message }), {
    headers: { 'Content-Type': 'application/json' },
  });

it('reference panel retains its last response while refreshing and keeps dismissed localized failures closed', async () => {
  let finish!: (value: Response) => void;
  const fetch = vi
    .spyOn(window, 'fetch')
    .mockResolvedValueOnce(response('Existing label'))
    .mockImplementationOnce(
      () =>
        new Promise((resolve) => {
          finish = resolve;
        }),
    )
    .mockRejectedValueOnce(new Error('Offline'));
  const wrapper = mount(Panel, { props: { locale: 'pl' } });
  await wrapper.get('form').trigger('submit');
  await vi.waitFor(() =>
    expect(wrapper.get('[role=status]').text()).toBe('Existing label'),
  );
  await wrapper.get('form').trigger('submit');
  await wrapper.get('form').trigger('submit');
  expect(fetch).toHaveBeenCalledTimes(2);
  expect(wrapper.get('[role=status]').text()).toBe('Existing label');
  finish(response('New label'));
  await vi.waitFor(() =>
    expect(wrapper.get('[role=status]').text()).toBe('New label'),
  );
  await wrapper.get('form').trigger('submit');
  await vi.waitFor(() =>
    expect(document.body.textContent).toContain(
      'Nie udało się odczytać odpowiedzi',
    ),
  );
  await new DOMWrapper(document.body)
    .get('button[aria-label="Zamknij"]')
    .trigger('click');
  await wrapper.setProps({ locale: 'en' });
  expect(document.body.textContent).not.toContain(
    'The response could not be read',
  );
  expect(wrapper.get('[role=status]').text()).toBe('New label');
  expect(wrapper.getComponent(Button).text()).toBe('Check connection');
});

it('reference panel exposes its read state to replacement markup and aborts disposal', async () => {
  let signal: AbortSignal | undefined;
  vi.spyOn(window, 'fetch').mockImplementation((_input, init) => {
    signal = init?.signal ?? undefined;
    return new Promise(() => {});
  });
  const wrapper = mount(Panel, {
    slots: {
      default: '<output>Custom response: {{ params.message }}</output>',
    },
  });
  await wrapper.get('form').trigger('submit');
  expect(wrapper.get('output').text()).toBe('Custom response:');
  expect(signal?.aborted).toBe(false);
  wrapper.unmount();
  expect(signal?.aborted).toBe(true);
});

it('reference widget clears unavailable values and localizes its public presentation', async () => {
  const widget: AdminWidgetData = {
    id: 'app.reference.response',
    title: { pl: 'Moduł referencyjny', en: 'Reference module' },
    component: null,
    values: { message: 'Configured label' },
    observed_at: null,
    expires_at: null,
    diagnostic: null,
  };
  const wrapper = mount(Widget, { props: { widget } });
  expect(wrapper.text()).toContain('Configured label');
  expect(wrapper.text()).not.toContain('message');
  await wrapper.setProps({ widget: { ...widget, diagnostic: 'unavailable' } });
  expect(wrapper.text()).not.toContain('Configured label');
});

it('reference Admin section composes the public controller and retains its value during a guarded refresh', async () => {
  const section: AdminSectionData = {
    key: 'response',
    title: { pl: 'Odpowiedź API', en: 'API response' },
    view: 'record',
    readonly: true,
    required: true,
    component: null,
    record: null,
    diagnostic: null,
    commands: [],
    fields: [
      {
        key: 'message',
        type: 'text',
        label: { pl: 'Etykieta', en: 'Label' },
        readonly: true,
        sensitive: false,
        filterable: false,
        sortable: false,
        lookup: false,
        required: false,
        max_length: null,
        choices: [],
        multiple: false,
        component: null,
      },
    ],
  };
  const page: AdminPageController = {
    page: shallowRef<AdminPageData>({
      schema_version: 1,
      owner: 'app.reference',
      key: 'inspect',
      title: { pl: 'Moduł referencyjny', en: 'Reference module' },
      generation: 1,
      revision: 'p1',
      component: null,
      sections: [section],
    }),
    state: computed(() => 'ready'),
    error: ref(),
    refresh: vi.fn(async () => {}),
  };
  const record = (message: string) =>
    new Response(
      JSON.stringify({
        data: {
          public_id: null,
          revision: message,
          values: { message },
        },
      }),
      { headers: { 'Content-Type': 'application/json' } },
    );
  let finish!: (value: Response) => void;
  const fetch = vi
    .spyOn(window, 'fetch')
    .mockResolvedValueOnce(record('Existing response'))
    .mockImplementationOnce(
      () =>
        new Promise((resolve) => {
          finish = resolve;
        }),
    );
  let form!: AdminFormController;
  const wrapper = mount(
    defineComponent({
      setup() {
        form = useAdminForm(page, { autoRead: false });
        return () => h(InspectSection, { page, controller: form, section });
      },
    }),
  );
  await vi.waitFor(() => expect(wrapper.text()).toContain('Existing response'));
  expect(wrapper.find('input').exists()).toBe(false);
  await wrapper.getComponent(Button).trigger('click');
  expect(wrapper.getComponent(Button).props('busy')).toBe(true);
  expect(wrapper.text()).toContain('Existing response');
  expect(fetch).toHaveBeenCalledTimes(2);
  finish(record('Updated response'));
  await vi.waitFor(() => expect(wrapper.text()).toContain('Updated response'));
  expect(wrapper.getComponent(Button).props('busy')).toBe(false);
});
