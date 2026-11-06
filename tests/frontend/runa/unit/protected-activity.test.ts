import { afterEach, expect, it, page, vi } from '@runa/testing/vitest';
import { enableAutoUnmount, flushPromises, mount } from '@runa/testing/vue';
import { defineComponent, h, ref } from 'vue';
import { useProtectedActivity } from '@/runa/auth';
enableAutoUnmount(afterEach);
afterEach(() => vi.useRealTimers());
const response = (remaining = 300, heartbeat = 30) =>
  new Response(
    JSON.stringify({
      data: { remaining_seconds: remaining, heartbeat_seconds: heartbeat },
    }),
    { headers: { 'Content-Type': 'application/json' } },
  );
function fixture(options: { initial?: boolean } = {}) {
  const identity = ref<string | undefined>('account-session'),
    active = ref(true);
  const expired = vi.fn();
  const initial = ref({ remaining_seconds: 300, heartbeat_seconds: 30 });
  const root = ref<HTMLElement>();
  const wrapper = mount(
    defineComponent({
      setup() {
        useProtectedActivity({
          endpoint: '/example/activity',
          identity: () => identity.value,
          active: () => active.value,
          root: () => root.value,
          initial: () =>
            options.initial === false ? undefined : initial.value,
          onExpired: expired,
        });
        return () =>
          h('section', { ref: root }, [
            h('button', 'Section action'),
            h('input', { 'aria-label': 'Section draft' }),
          ]);
      },
    }),
    { attachTo: document.body },
  );
  return { wrapper, identity, active, expired, initial };
}
it('protected section activity batches real clicks and typing and ignores synthetic input and background time', async () => {
  const fetch = vi
    .spyOn(window, 'fetch')
    .mockImplementation(async () => response());
  vi.useFakeTimers();
  const { wrapper, active } = fixture();
  await wrapper.get('button').trigger('click');
  wrapper.element.dispatchEvent(new Event('scroll', { bubbles: true }));
  await vi.advanceTimersByTimeAsync(40000);
  expect(fetch).not.toHaveBeenCalled();
  await page.elementLocator(wrapper.get('button').element).click();
  await vi.advanceTimersByTimeAsync(1);
  expect(fetch).toHaveBeenCalledTimes(1);
  expect(fetch.mock.calls[0]?.[1]?.method).toBe('POST');
  await page
    .elementLocator(wrapper.get('input').element)
    .fill('Draft stays active');
  await page.elementLocator(wrapper.get('button').element).click();
  await vi.advanceTimersByTimeAsync(29999);
  expect(fetch).toHaveBeenCalledTimes(2);
  await vi.advanceTimersByTimeAsync(30000);
  expect(fetch).toHaveBeenCalledTimes(2);
  active.value = false;
  await page.elementLocator(wrapper.get('button').element).click();
  await vi.advanceTimersByTimeAsync(30000);
  expect(fetch).toHaveBeenCalledTimes(2);
});
it('protected section activity isolates roots and discards a trailing batch after identity loss', async () => {
  const fetch = vi
    .spyOn(window, 'fetch')
    .mockImplementation(async () => response());
  vi.useFakeTimers();
  const { wrapper, identity, expired } = fixture();
  const outside = document.createElement('button');
  outside.textContent = 'Outside section';
  document.body.append(outside);
  try {
    await page.elementLocator(outside).click();
    await vi.advanceTimersByTimeAsync(1);
    expect(fetch).not.toHaveBeenCalled();
    await page.elementLocator(wrapper.get('button').element).click();
    await vi.advanceTimersByTimeAsync(1);
    await page.elementLocator(wrapper.get('button').element).click();
    identity.value = undefined;
    await vi.advanceTimersByTimeAsync(600000);
    expect(fetch).toHaveBeenCalledTimes(1);
    expect(expired).not.toHaveBeenCalled();
  } finally {
    outside.remove();
  }
});
it('protected section activity checks server expiry without renewal and fences obsolete status responses', async () => {
  let finish!: (value: Response) => void;
  const fetch = vi.spyOn(window, 'fetch').mockImplementation(
    () =>
      new Promise<Response>((resolve) => {
        finish = resolve;
      }),
  );
  const { identity, expired } = fixture({ initial: false });
  await flushPromises();
  expect(fetch.mock.calls[0]?.[1]?.method).toBe('GET');
  identity.value = undefined;
  finish(
    new Response(JSON.stringify({ reason: 'step_up_required' }), {
      status: 403,
    }),
  );
  await flushPromises();
  expect(expired).not.toHaveBeenCalled();
});
it('protected section activity rechecks an elapsed deadline and reports server refusal without posting activity', async () => {
  const fetch = vi.spyOn(window, 'fetch').mockResolvedValue(
    new Response(JSON.stringify({ reason: 'step_up_required' }), {
      status: 403,
    }),
  );
  vi.useFakeTimers();
  const { expired } = fixture();
  await vi.advanceTimersByTimeAsync(300000);
  expect(fetch).toHaveBeenCalledTimes(1);
  expect(fetch.mock.calls[0]?.[1]?.method).toBe('GET');
  expect(expired).toHaveBeenCalledOnce();
});

it('protected section activity keeps the throttle across bootstrap reads and cancels hidden pending work', async () => {
  const fetch = vi
    .spyOn(window, 'fetch')
    .mockImplementation(async () => response());
  vi.useFakeTimers();
  const { wrapper, initial } = fixture();
  await page.elementLocator(wrapper.get('button').element).click();
  await vi.advanceTimersByTimeAsync(1);
  initial.value = { remaining_seconds: 299, heartbeat_seconds: 30 };
  await page.elementLocator(wrapper.get('button').element).click();
  await vi.advanceTimersByTimeAsync(1000);
  expect(fetch).toHaveBeenCalledTimes(1);
  const visibility = vi
    .spyOn(document, 'visibilityState', 'get')
    .mockReturnValue('hidden');
  document.dispatchEvent(new Event('visibilitychange'));
  await vi.advanceTimersByTimeAsync(60000);
  expect(fetch).toHaveBeenCalledTimes(1);
  visibility.mockReturnValue('visible');
  document.dispatchEvent(new Event('visibilitychange'));
  await flushPromises();
  expect(fetch).toHaveBeenCalledTimes(2);
  expect(fetch.mock.calls[1]?.[1]?.method).toBe('GET');
});
