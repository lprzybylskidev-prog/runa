import { afterEach, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import AppBrandIllustration from '../../resources/js/components/AppBrandIllustration.vue';

afterEach(() => vi.useRealTimers());
it('moves only the six illustration points through one closed lap and then rests', async () => {
  vi.useFakeTimers();
  vi.spyOn(Math, 'random').mockReturnValue(0);
  vi.spyOn(document, 'visibilityState', 'get').mockReturnValue('visible');
  const animate = vi.spyOn(Element.prototype, 'animate');
  const staticArt = mount(AppBrandIllustration);
  await vi.advanceTimersByTimeAsync(20000);
  expect(animate).not.toHaveBeenCalled();
  const wrapper = mount(AppBrandIllustration, {
    props: { animated: true },
    attachTo: document.body,
  });
  await vi.advanceTimersByTimeAsync(7999);
  expect(animate).not.toHaveBeenCalled();
  await vi.advanceTimersByTimeAsync(1);
  expect(animate).toHaveBeenCalledTimes(6);
  for (const [index, call] of animate.mock.calls.entries()) {
    const frames = call[0] as Keyframe[];
    const first = frames[0]!,
      last = frames.at(-1)!;
    expect(parseFloat(String(last.left))).toBeCloseTo(
      parseFloat(String(first.left)),
      8,
    );
    expect(parseFloat(String(last.top))).toBeCloseTo(
      parseFloat(String(first.top)),
      8,
    );
    expect(frames[18]!.left).not.toEqual(first.left);
    expect(Number(frames[12]!.opacity)).toBeGreaterThan(Number(first.opacity));
    expect(
      (animate.mock.contexts[index] as Element).classList.contains(
        'art-orbit-point',
      ),
    ).toBe(true);
  }
  await vi.advanceTimersByTimeAsync(6000);
  expect(wrapper.element.getAnimations({ subtree: true })).toHaveLength(0);
  await vi.advanceTimersByTimeAsync(7999);
  expect(animate).toHaveBeenCalledTimes(6);
  await wrapper.setProps({ animated: false });
  expect(vi.getTimerCount()).toBe(0);
  wrapper.unmount();
  staticArt.unmount();
});
