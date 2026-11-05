<script setup lang="ts">
import { Select, Switch } from '@/runa/ui';
import type { AuthEmailPreferenceFieldsProps } from './contracts/fields';
defineProps<AuthEmailPreferenceFieldsProps>();
const emit = defineEmits<{
  'update:enabled': [value: boolean];
  'update:recipients': [value: string[]];
}>();
</script>
<template>
  <div
    class="grid min-w-0 items-center gap-x-5 gap-y-2 sm:grid-cols-[minmax(0,1fr)_minmax(12rem,20rem)]"
    data-auth-email-preference-fields
  >
    <div class="min-w-0 [overflow-wrap:anywhere]">
      <Switch
        compact
        :label="label"
        :model-value="enabled"
        :disabled="disabled"
        @update:model-value="emit('update:enabled', $event)"
      />
    </div>
    <Select
      v-if="enabled && recipientSelection === true && options.length > 1"
      :multiple="true"
      size="sm"
      :label="locale === 'pl' ? 'Wyślij na' : 'Send to'"
      :model-value="recipients"
      :options="options"
      :locale="locale"
      :disabled="disabled"
      :error="error ?? ''"
      @update:model-value="emit('update:recipients', $event)"
    />
  </div>
</template>
