import { afterEach, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import AppBackdrop from '../../resources/js/components/AppBackdrop.vue';

afterEach(() => vi.useRealTimers());
it('keeps the shared backdrop static by default and glows one point at a time only when opted in', async () => {
  vi.useFakeTimers();
  vi.spyOn(Math, 'random').mockReturnValue(0.5);
  vi.spyOn(document, 'visibilityState', 'get').mockReturnValue('visible');
  vi.spyOn(Element.prototype, 'getBoundingClientRect').mockReturnValue(
    new DOMRect(10, 10, 16, 16),
  );
  const animate = vi.spyOn(Element.prototype, 'animate');
  const application = mount(AppBackdrop, { attachTo: document.body });
  await vi.advanceTimersByTimeAsync(20000);
  expect(animate).not.toHaveBeenCalled();
  const minimal = mount(AppBackdrop, {
    props: { constellationGlow: true },
    attachTo: document.body,
  });
  await vi.advanceTimersByTimeAsync(10999);
  expect(animate).not.toHaveBeenCalled();
  await vi.advanceTimersByTimeAsync(1);
  expect(animate).toHaveBeenCalledTimes(3);
  const first = animate.mock.contexts[0] as Element;
  expect(
    animate.mock.contexts.every(
      (circle) => (circle as Element).parentElement === first.parentElement,
    ),
  ).toBe(true);
  await vi.advanceTimersByTimeAsync(3000);
  expect(first.getAnimations()).toHaveLength(0);
  await vi.advanceTimersByTimeAsync(11000);
  expect(animate).toHaveBeenCalledTimes(6);
  expect((animate.mock.contexts[3] as Element).parentElement).not.toBe(
    first.parentElement,
  );
  minimal.unmount();
  application.unmount();
  expect(vi.getTimerCount()).toBe(0);
});

it('cancels decorative glow and pending timers on reduced motion, print, hidden documents and opt-out', async () => {
  vi.useFakeTimers();
  vi.spyOn(Math, 'random').mockReturnValue(0.5);
  let visibility: DocumentVisibilityState = 'visible';
  vi.spyOn(document, 'visibilityState', 'get').mockImplementation(
    () => visibility,
  );
  vi.spyOn(Element.prototype, 'getBoundingClientRect').mockReturnValue(
    new DOMRect(10, 10, 16, 16),
  );
  const media = new Map<string, MediaQueryList>();
  vi.spyOn(window, 'matchMedia').mockImplementation((query) => {
    const result = Object.assign(new EventTarget(), {
      matches: false,
      media: query,
    }) as MediaQueryList;
    media.set(query, result);
    return result;
  });
  const animate = vi.spyOn(Element.prototype, 'animate');
  const wrapper = mount(AppBackdrop, {
    props: { constellationGlow: true },
    attachTo: document.body,
  });
  await vi.advanceTimersByTimeAsync(11000);
  const circle = animate.mock.contexts[0] as Element;
  const reduced = media.get('(prefers-reduced-motion: reduce)')!;
  Object.defineProperty(reduced, 'matches', {
    value: true,
    configurable: true,
  });
  reduced.dispatchEvent(new Event('change'));
  expect(circle.getAnimations()).toHaveLength(0);
  expect(vi.getTimerCount()).toBe(0);
  Object.defineProperty(reduced, 'matches', { value: false });
  reduced.dispatchEvent(new Event('change'));
  const print = media.get('print')!;
  Object.defineProperty(print, 'matches', { value: true, configurable: true });
  print.dispatchEvent(new Event('change'));
  expect(vi.getTimerCount()).toBe(0);
  Object.defineProperty(print, 'matches', { value: false });
  print.dispatchEvent(new Event('change'));
  visibility = 'hidden';
  document.dispatchEvent(new Event('visibilitychange'));
  expect(vi.getTimerCount()).toBe(0);
  visibility = 'visible';
  document.dispatchEvent(new Event('visibilitychange'));
  expect(vi.getTimerCount()).toBe(1);
  await wrapper.setProps({ constellationGlow: false });
  expect(vi.getTimerCount()).toBe(0);
  wrapper.unmount();
});

it('previews all constellation points together every three seconds only when explicitly enabled', async () => {
  vi.useFakeTimers();
  vi.spyOn(document, 'visibilityState', 'get').mockReturnValue('visible');
  const animate = vi.spyOn(Element.prototype, 'animate');
  const wrapper = mount(AppBackdrop, {
    props: { constellationGlow: true, glowPreview: true },
    attachTo: document.body,
  });
  const circles = wrapper.findAll('svg g circle').length;
  await vi.advanceTimersByTimeAsync(2999);
  expect(animate).not.toHaveBeenCalled();
  await vi.advanceTimersByTimeAsync(1);
  expect(animate).toHaveBeenCalledTimes(circles);
  await vi.advanceTimersByTimeAsync(3000);
  expect(animate).toHaveBeenCalledTimes(circles * 2);
  wrapper.unmount();
  expect(vi.getTimerCount()).toBe(0);
});
