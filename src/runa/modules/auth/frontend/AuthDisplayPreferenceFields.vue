<script setup lang="ts">
import { RadioGroup } from '@/runa/ui';
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
    class="min-w-0 space-y-6"
    data-auth-display-fields
  >
    <RadioGroup
      :model-value="language"
      :label="text('Język interfejsu', 'Interface language')"
      :options="[
        { value: 'pl', label: 'Polski' },
        { value: 'en', label: 'English' },
      ]"
      @update:model-value="emit('update:language', $event)"
    />
    <RadioGroup
      :model-value="theme"
      :label="text('Motyw', 'Theme')"
      :options="[
        { value: 'system', label: text('Systemowy', 'System') },
        { value: 'light', label: text('Jasny', 'Light') },
        { value: 'dark', label: text('Ciemny', 'Dark') },
      ]"
      @update:model-value="emit('update:theme', $event)"
    />
  </fieldset>
</template>
