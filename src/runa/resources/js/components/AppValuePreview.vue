<script setup lang="ts">
import { computed, ref, useId, watch } from 'vue';
import { formatJsonText } from '../lib/formatJsonText';
import AppButton from './AppButton.vue';
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
</script>
<template>
  <div class="min-w-0 max-w-full">
    <p
      :id="id"
      class="m-0 whitespace-pre-wrap text-sm leading-relaxed [overflow-wrap:anywhere]"
      :class="
        format === 'json'
          ? 'rounded border border-line bg-[var(--runa-surface-raised)] p-3 font-mono text-xs'
          : ''
      "
    >
      {{ expanded || !truncated ? formatted : preview + '…' }}
    </p>
    <AppButton
      v-if="truncated"
      size="sm"
      class="mt-2"
      :aria-controls="id"
      :aria-expanded="expanded"
      @click="expanded = !expanded"
      >{{
        locale === 'pl'
          ? expanded
            ? 'Zwiń'
            : 'Pokaż wszystko'
          : expanded
            ? 'Collapse'
            : 'Show all'
      }}</AppButton
    >
  </div>
</template>
