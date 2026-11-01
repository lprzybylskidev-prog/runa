<script setup lang="ts">
import { computed, ref, useId, watch } from 'vue';
import { formatJsonText } from '../lib/formatJsonText';
import AppTextLink from './AppTextLink.vue';
import { jsonTokens } from '../lib/jsonTokens';
import type { ValuePreviewProps } from '../runa/ui/valuePreview';
const props = withDefaults(defineProps<ValuePreviewProps>(), {
  format: 'text',
  locale: 'pl',
});
const id = useId();
const expanded = ref(false);
watch(
  () => props.text,
  () => {
    expanded.value = false;
  },
);
const formatted = computed(() =>
  props.format === 'json' ? formatJsonText(props.text) : props.text,
);
const preview = computed(() => {
  const lines = formatted.value.split('\n');
  return Array.from(lines.slice(0, 8).join('\n')).slice(0, 480).join('');
});
const truncated = computed(() => preview.value.length < formatted.value.length);
const visible = computed(() =>
  expanded.value || !truncated.value ? formatted.value : preview.value + '…',
);
const tokens = computed(() =>
  props.format === 'json'
    ? jsonTokens(visible.value)
    : [{ text: visible.value, kind: 'plain' }],
);
</script>
<template>
  <div
    class="min-w-0 max-w-full"
    :class="
      format === 'json'
        ? 'rounded border border-line bg-[var(--runa-surface-raised)] p-3'
        : ''
    "
  >
    <p
      :id="id"
      class="m-0 whitespace-pre-wrap text-sm leading-relaxed [overflow-wrap:anywhere]"
      :class="format === 'json' ? 'font-mono text-xs' : ''"
    >
      <span
        v-for="(token, index) in tokens"
        :key="index"
        :data-json-token="format === 'json' ? token.kind : undefined"
        :class="format === 'json' ? 'json-' + token.kind : ''"
        >{{ token.text }}</span
      >
    </p>
    <AppTextLink
      v-if="truncated"
      :href="'#' + id"
      class="mt-1"
      :aria-controls="id"
      :aria-expanded="expanded"
      @click.prevent="expanded = !expanded"
      >{{
        locale === 'pl'
          ? expanded
            ? 'Zwiń'
            : 'Pokaż wszystko'
          : expanded
            ? 'Collapse'
            : 'Show all'
      }}</AppTextLink
    >
  </div>
</template>
<style scoped>
.json-key {
  color: var(--runa-primary-text);
}
.json-string {
  color: var(--runa-success-text);
}
.json-number {
  color: var(--runa-warning-text);
}
.json-literal {
  color: var(--runa-accent-text);
}
.json-punctuation {
  color: var(--runa-text-secondary);
}
@media (forced-colors: active) {
  [data-json-token] {
    color: CanvasText;
  }
}
</style>
