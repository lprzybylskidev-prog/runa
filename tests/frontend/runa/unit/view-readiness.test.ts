/* eslint-disable vue/one-component-per-file -- Independent mounted readiness and image-owner fixtures. */
import { afterEach, expect, it, vi } from '@runa/testing/vitest';
import { enableAutoUnmount, flushPromises, mount } from '@runa/testing/vue';
import { defineComponent, h, ref } from 'vue';
import {
  Avatar,
  AsyncContent,
  ViewBoundary,
  useViewReadiness,
  useImageReadiness,
} from '@/runa/ui';
enableAutoUnmount(afterEach);
afterEach(() => {
  vi.useRealTimers();
  vi.unstubAllGlobals();
});

it('view readiness retains DOM focus and drafts across background reads but withdraws on identity loss', async () => {
  const identity = ref<string | null>('account:a'),
    required = ref(false),
    refreshing = ref(false),
    pending = ref(false);
  let readiness!: ReturnType<typeof useViewReadiness>;
  const w = mount(
    defineComponent({
      setup() {
        readiness = useViewReadiness({
          identity,
          required,
          refreshing,
          pending,
        });
        return () =>
          h(
            ViewBoundary,
            { readiness, label: 'Loading view' },
            { default: () => h('input') },
          );
      },
    }),
    { attachTo: document.body },
  );
  expect(w.find('input').exists()).toBe(false);
  required.value = true;
  await flushPromises();
  const input = w.get('input');
  await input.setValue('Draft');
  (input.element as HTMLInputElement).focus();
  required.value = false;
  refreshing.value = true;
  await flushPromises();
  expect(readiness.phase.value).toBe('refreshing');
  expect(w.get('input').element).toBe(input.element);
  expect(document.activeElement).toBe(input.element);
  pending.value = true;
  expect(readiness.phase.value).toBe('mutating');
  identity.value = null;
  expect(readiness.ready.value).toBe(false);
  await flushPromises();
  expect(w.find('input').exists()).toBe(false);
  identity.value = 'account:b';
  required.value = true;
  await flushPromises();
  expect((w.get('input').element as HTMLInputElement).value).toBe('');
});

class DeferredImage {
  static instances: DeferredImage[] = [];
  src = '';
  decoding = '';
  naturalWidth = 1;
  onload: (() => void) | null = null;
  onerror: (() => void) | null = null;
  finish!: () => void;
  decode = vi.fn(
    () =>
      new Promise<void>((resolve) => {
        this.finish = resolve;
      }),
  );
  constructor() {
    DeferredImage.instances.push(this);
  }
}
function imageHarness(timeout = 2500) {
  DeferredImage.instances = [];
  vi.stubGlobal('Image', DeferredImage);
  const source = ref('/avatar/a'),
    identity = ref<string | null>('a');
  let image!: ReturnType<typeof useImageReadiness>;
  const w = mount(
    defineComponent({
      setup() {
        image = useImageReadiness(source, identity, timeout);
        return () =>
          h(Avatar, {
            image,
            initials: identity.value ?? '',
            src: source.value,
          });
      },
    }),
  );
  return { w, source, identity, image };
}
it('image readiness waits for decode shares the prepared image and fences old identity completions', async () => {
  const { w, source, identity, image } = imageHarness();
  const first = DeferredImage.instances[0]!;
  first.onload!();
  await flushPromises();
  expect(image.settled.value).toBe(false);
  expect(w.find('img').exists()).toBe(false);
  first.finish();
  await flushPromises();
  expect(image.src.value).toBe('/avatar/a');
  const dropdown = mount(Avatar, {
    props: { image, src: source.value, initials: 'A' },
  });
  expect(dropdown.get('img').attributes('src')).toBe('/avatar/a');
  expect(DeferredImage.instances).toHaveLength(1);
  source.value = '/avatar/new';
  await flushPromises();
  expect(image.src.value).toBe('/avatar/a');
  const old = DeferredImage.instances.at(-1)!;
  old.onload!();
  identity.value = 'b';
  expect(image.src.value).toBe('');
  old.finish();
  await flushPromises();
  expect(image.src.value).toBe('');
  identity.value = null;
  expect(image.state.value).toBe('fallback');
});
it('image readiness bounds download and decode waits and falls back on errors without late resurrection', async () => {
  vi.useFakeTimers();
  const { image, source } = imageHarness(100);
  const first = DeferredImage.instances[0]!;
  first.onload!();
  await vi.advanceTimersByTimeAsync(100);
  expect(image.state.value).toBe('fallback');
  first.finish();
  await flushPromises();
  expect(image.src.value).toBe('');
  source.value = '/avatar/b';
  DeferredImage.instances.at(-1)!.onerror!();
  expect(image.state.value).toBe('fallback');
  source.value = '/avatar/c';
  await vi.advanceTimersByTimeAsync(100);
  expect(image.state.value).toBe('fallback');
});
it('async content refresh retains the same editable success region', async () => {
  const w = mount(AsyncContent, {
    props: { state: 'success' },
    slots: { default: '<input />' },
  });
  const input = w.get('input');
  await input.setValue('Draft');
  await w.setProps({ state: 'refreshing' });
  expect(w.attributes('aria-busy')).toBe('true');
  expect(w.get('input').element).toBe(input.element);
  expect((input.element as HTMLInputElement).value).toBe('Draft');
});

it('application startup waits for public presentation and keeps the root mounted on failed background hydration', async () => {
  const { createApplication } = await import('@/runa/ui');
  let finish!: (value: Response) => void;
  let initial = true;
  const json = (data: unknown) =>
    new Response(JSON.stringify({ data }), {
      headers: { 'Content-Type': 'application/json' },
    });
  vi.spyOn(window, 'fetch').mockImplementation(async (input) => {
    if (String(input) === '/api/identity')
      return json({
        applicationId: 'runa',
        applicationName: 'Runa',
        applicationVersion: '0.7.0',
        platformVersion: '0.7.0',
      });
    if (initial)
      return new Promise<Response>((resolve) => {
        finish = resolve;
      });
    throw new Error('Offline');
  });
  const host = document.createElement('div');
  document.body.append(host);
  const app = createApplication(
    defineComponent({ setup: () => () => h('input') }),
  );
  app.mount(host);
  try {
    await flushPromises();
    expect(host.querySelector('input')).toBeNull();
    finish(json({ name: 'Prepared brand', logo: null, revision: 1 }));
    await vi.waitFor(() => expect(host.querySelector('input')).not.toBeNull());
    const input = host.querySelector('input')!;
    input.value = 'Draft';
    input.focus();
    initial = false;
    window.dispatchEvent(new Event('focus'));
    await flushPromises();
    expect(host.querySelector('input')).toBe(input);
    expect(document.activeElement).toBe(input);
    expect(input.value).toBe('Draft');
  } finally {
    app.unmount();
    host.remove();
  }
});
