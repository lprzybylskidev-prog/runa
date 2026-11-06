import { afterEach, expect, it, vi } from '@runa/testing/vitest';
import { DOMWrapper, enableAutoUnmount, mount } from '@runa/testing/vue';
import Panel from '../../../../../../src/app/modules/reference/frontend/components/panel.vue';
import Widget from '../../../../../../src/app/modules/reference/frontend/components/response_widget.vue';
import { Button } from '@/runa/ui';
import type { AdminWidgetData } from '@/runa/admin';

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
