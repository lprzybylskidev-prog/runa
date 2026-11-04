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
  <div class="min-w-0 space-y-3" data-auth-email-preference-fields>
    <div class="min-w-0 font-semibold [overflow-wrap:anywhere]">
      <Switch
        :label="label"
        :model-value="enabled"
        :disabled="disabled"
        @update:model-value="emit('update:enabled', $event)"
      />
    </div>
    <Select
      v-if="enabled"
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
