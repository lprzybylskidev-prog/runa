<script setup lang="ts">
import { Select } from '@/runa/ui';
import type { AuthDisplayPreferenceFieldsProps } from './contracts/fields';
const props = defineProps<AuthDisplayPreferenceFieldsProps>();
const emit = defineEmits<{
  'update:language': [value: string];
  'update:theme': [value: string];
}>();
const text = (pl: string, en: string) => (props.locale === 'pl' ? pl : en);
</script>
<template>
  <fieldset
    :disabled="disabled"
    class="grid min-w-0 max-w-xl gap-3 sm:grid-cols-2"
    data-auth-display-fields
  >
    <Select
      size="sm"
      :locale="locale"
      :disabled="disabled"
      :model-value="language"
      :label="text('Język interfejsu', 'Interface language')"
      :options="[
        { value: 'pl', label: 'Polski' },
        { value: 'en', label: 'English' },
      ]"
      @update:model-value="
        $event && !Array.isArray($event) && emit('update:language', $event)
      "
    />
    <Select
      size="sm"
      :locale="locale"
      :disabled="disabled"
      :model-value="theme"
      :label="text('Motyw', 'Theme')"
      :options="[
        { value: 'system', label: text('Systemowy', 'System') },
        { value: 'light', label: text('Jasny', 'Light') },
        { value: 'dark', label: text('Ciemny', 'Dark') },
      ]"
      @update:model-value="
        $event && !Array.isArray($event) && emit('update:theme', $event)
      "
    />
  </fieldset>
</template>
