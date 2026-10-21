import {
  computed,
  inject,
  provide,
  type ComputedRef,
  type InjectionKey,
} from 'vue';
import type { FeedbackTone } from '../runa/ui/feedback';

const feedbackToneKey: InjectionKey<ComputedRef<FeedbackTone>> =
  Symbol('feedback-tone');

export function useInheritedFeedbackTone() {
  return inject(
    feedbackToneKey,
    computed<FeedbackTone>(() => 'neutral'),
  );
}

/** Nearest semantic surface supplies defaults; explicit child tones always win. */
export function useFeedbackTone(
  value: () => FeedbackTone | undefined,
  fallback: FeedbackTone = 'neutral',
) {
  const parent = inject(feedbackToneKey, null);
  const tone = computed(() => value() ?? parent?.value ?? fallback);
  provide(feedbackToneKey, tone);
  return tone;
}
