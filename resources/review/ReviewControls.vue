<script setup lang="ts">
import { ref } from 'vue';
import { Button, ButtonGroup, Icon } from '@/runa/ui';
import type { ButtonVariant, ControlSize, ControlTone } from '@/runa/ui';
const props = defineProps<{ english: boolean }>();
const tones: ControlTone[] = [
  'primary',
  'secondary',
  'success',
  'danger',
  'warning',
  'info',
  'accent',
];
const variants: ButtonVariant[] = [
  'neutral',
  'solid',
  'outline',
  'soft',
  'ghost',
];
const sizes: ControlSize[] = ['sm', 'md', 'lg'];
const disabled = ref(false);
const busy = ref(false);
const activations = ref(0);
const selected = ref('day');
</script>
<template>
  <div class="space-y-10">
    <section class="space-y-4">
      <h2 class="text-lg font-semibold">
        {{
          props.english
            ? 'Buttons — variants and tones'
            : 'Przyciski — warianty i kolory'
        }}
      </h2>
      <div class="flex flex-wrap gap-3">
        <Button :aria-pressed="disabled" @click="disabled = !disabled"
          >Disabled: {{ disabled }}</Button
        >
        <Button :aria-pressed="busy" @click="busy = !busy"
          >Busy: {{ busy }}</Button
        >
      </div>
      <p class="text-sm text-muted">
        {{
          english
            ? 'Test hover, press and keyboard focus. Every example is interactive.'
            : 'Sprawdź najechanie, naciśnięcie i fokus klawiatury. Każdy przykład jest interaktywny.'
        }}
      </p>
      <div v-for="variant in variants" :key="variant" class="space-y-2">
        <h3 class="text-sm">{{ variant }}</h3>
        <div class="flex flex-wrap gap-3">
          <Button
            v-for="tone in tones"
            :key="tone"
            :tone="tone"
            :variant="variant"
            :disabled="disabled"
            :busy="busy"
            @click="activations++"
            >{{ tone }}</Button
          >
        </div>
      </div>
      <output class="block text-sm" aria-live="polite"
        >{{ english ? 'Activations' : 'Kliknięcia' }}: {{ activations }}</output
      >
    </section>
    <section class="space-y-4">
      <h2 class="text-lg font-semibold">
        {{ english ? 'Sizes, icons and states' : 'Rozmiary, ikony i stany' }}
      </h2>
      <div
        v-for="size in sizes"
        :key="size"
        class="flex flex-wrap items-center gap-3"
      >
        <Button :size="size" variant="solid" @click="activations++"
          ><Icon name="layers-intersect" :size="18" />{{ size }}</Button
        >
        <Button
          :size="size"
          icon-only
          :aria-label="english ? 'Change theme' : 'Zmień motyw'"
          @click="activations++"
          ><Icon name="sun" :size="18"
        /></Button>
        <Button :size="size" busy>{{
          english ? 'Saving…' : 'Zapisywanie…'
        }}</Button>
        <Button :size="size" disabled>{{
          english ? 'Unavailable' : 'Niedostępny'
        }}</Button>
      </div>
      <Button
        :aria-pressed="selected === 'toggle'"
        @click="selected = selected === 'toggle' ? 'day' : 'toggle'"
        >Toggle: {{ selected === 'toggle' }}</Button
      >
    </section>
    <section class="space-y-4">
      <h2 class="text-lg font-semibold">
        {{ english ? 'Button groups' : 'Grupy przycisków' }}
      </h2>
      <div class="flex flex-wrap items-start gap-4">
        <ButtonGroup :label="english ? 'Period' : 'Okres'">
          <Button
            v-for="period in ['day', 'week', 'month']"
            :key="period"
            :aria-pressed="selected === period"
            :variant="selected === period ? 'solid' : 'neutral'"
            @click="selected = period"
            >{{ period }}</Button
          >
        </ButtonGroup>
      </div>
    </section>
  </div>
</template>
