<script setup lang="ts">
import { ref } from 'vue';
import avatarImage from '../images/runa-logo.png';
import { Avatar, Button, ButtonGroup, DropdownButton, Icon } from '@/runa/ui';
import type {
  ButtonVariant,
  ControlSize,
  ControlTone,
  IconName,
} from '@/runa/ui';
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
const icons: IconName[] = ['layers-intersect', 'sun', 'moon', 'device-desktop'];
const disabled = ref(false);
const busy = ref(false);
const activations = ref(0);
const lastAction = ref('—');
const selected = ref('day');
const actions = [
  { id: 'save', label: 'Zapisz / Save' },
  { id: 'copy', label: 'Kopiuj / Copy' },
  { id: 'delete', label: 'Usuń / Delete', disabled: true },
];
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
        {{ english ? 'Groups and action lists' : 'Grupy i listy akcji' }}
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
        <DropdownButton
          :label="english ? 'Actions' : 'Akcje'"
          :actions="actions"
          :disabled="disabled"
          :busy="busy"
          @select="lastAction = $event"
        />
        <DropdownButton
          split
          variant="solid"
          :label="english ? 'Save' : 'Zapisz'"
          :actions="actions"
          :disabled="disabled"
          :busy="busy"
          @click="activations++"
          @select="lastAction = $event"
        />
      </div>
      <output class="block text-sm" aria-live="polite"
        >{{ english ? 'Last action' : 'Ostatnia akcja' }}:
        {{ lastAction }}</output
      >
    </section>
    <section class="space-y-4">
      <h2 class="text-lg font-semibold">
        {{ english ? 'Available icons' : 'Dostępne ikony' }}
      </h2>
      <div class="flex flex-wrap gap-6">
        <div v-for="icon in icons" :key="icon" class="space-y-3">
          <div class="flex items-center gap-3">
            <Icon
              v-for="size in [16, 24, 32]"
              :key="size"
              :name="icon"
              :size="size"
              color="var(--runa-primary-text)"
            />
          </div>
          <p class="text-xs">{{ icon }}</p>
        </div>
      </div>
    </section>
    <section class="space-y-4">
      <h2 class="text-lg font-semibold">
        {{
          english
            ? 'Avatars — image, initials and fallback'
            : 'Awatary — obraz, inicjały i fallback'
        }}
      </h2>
      <div
        v-for="shape in ['square', 'circle'] as const"
        :key="shape"
        class="flex flex-wrap items-center gap-4"
      >
        <template v-for="size in sizes" :key="size"
          ><Avatar
            name="Łukasz Przybylski"
            :size="size"
            :shape="shape" /><Avatar
            name="Runa"
            :src="avatarImage"
            :size="size"
            :shape="shape" /><Avatar
            name="Anna Nowak"
            src="/missing-review-avatar.png"
            :size="size"
            :shape="shape" /><Avatar name="" :size="size" :shape="shape"
        /></template>
      </div>
    </section>
  </div>
</template>
