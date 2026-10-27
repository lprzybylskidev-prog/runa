<script setup lang="ts">
import { computed, ref } from 'vue';
import {
  Card,
  Divider,
  Accordion,
  Tabs,
  Breadcrumbs,
  Pagination,
  Dropdown,
  Button,
} from '@/runa/ui';
const props = defineProps<{ english: boolean }>();
const text = (pl: string, en: string) => (props.english ? en : pl);
const expanded = ref(true);
const tab = ref('overview');
const page = ref(1);
const dropdown = ref(false);
const tabs = computed(() => [
  { id: 'overview', label: text('Podsumowanie', 'Overview') },
  { id: 'details', label: text('Szczegóły', 'Details') },
  { id: 'locked', label: text('Niedostępne', 'Unavailable'), disabled: true },
]);
</script>
<template>
  <div class="space-y-6">
    <Breadcrumbs
      :label="text('Ścieżka nawigacji', 'Breadcrumbs')"
      :items="[
        { label: 'Runa', href: '/' },
        { label: text('Komponenty', 'Components') },
        { label: text('Kontenery', 'Containers') },
      ]"
    />
    <Card
      ><template #header
        ><h2 class="font-semibold">{{ text('Karta', 'Card') }}</h2></template
      ><template #actions
        ><Dropdown
          v-model="dropdown"
          :label="text('Opcje widoku', 'View options')"
          ><template #default="{ close }"
            ><p class="mb-4">
              {{
                text(
                  'Dowolna treść panelu, niezależna od nawigacji aplikacji.',
                  'Custom panel content, independent of application navigation.',
                )
              }}
            </p>
            <Button @click="close()">{{
              text('Gotowe', 'Done')
            }}</Button></template
          ></Dropdown
        ></template
      >
      <p>
        {{
          text(
            'Nagłówek, akcje, treść i stopka są niezależnymi slotami.',
            'Header, actions, content and footer are independent slots.',
          )
        }}
      </p>
      <template #footer>{{
        text('Stopka karty', 'Card footer')
      }}</template></Card
    >
    <Divider :label="text('Sekcje', 'Sections')" />
    <Accordion
      v-model="expanded"
      :label="text('Informacje dodatkowe', 'Additional information')"
      ><p>
        {{
          text(
            'Rozwinięcie kontroluje aplikacja. Enter i Spacja przełączają sekcję.',
            'Expansion is controlled by the application. Enter and Space toggle the section.',
          )
        }}
      </p></Accordion
    >
    <Accordion
      :model-value="false"
      disabled
      :label="text('Sekcja niedostępna', 'Unavailable section')"
    />
    <Tabs v-model="tab" :items="tabs" :label="text('Informacje', 'Information')"
      ><template #overview
        ><p>
          {{
            text(
              'Użyj strzałek, Home i End, aby przełączać karty.',
              'Use arrow keys, Home and End to switch tabs.',
            )
          }}
        </p></template
      ><template #details
        ><p>
          {{
            text(
              'Treść drugiej karty. Nieaktywne panele zachowują własny stan.',
              'Second tab content. Inactive panels retain their own state.',
            )
          }}
        </p></template
      ></Tabs
    >
    <Divider />
    <div class="space-y-3">
      <p role="status" class="text-sm text-muted">
        {{ text('Strona', 'Page') }} {{ page }} / 20
      </p>
      <Pagination
        v-model="page"
        :pages="20"
        :label="text('Stronicowanie', 'Pagination')"
        :previous-label="text('Poprzednia strona', 'Previous page')"
        :next-label="text('Następna strona', 'Next page')"
      />
    </div>
  </div>
</template>
