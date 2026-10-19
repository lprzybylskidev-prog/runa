<script setup lang="ts">
import { onScopeDispose, ref } from 'vue';
import {
  AlertDialog,
  ConfirmDialog,
  Dialog,
  Button,
  Card,
  Checkbox,
  Select,
  TextInput,
  type DialogSize,
} from '@/runa/ui';
const props = defineProps<{ english: boolean }>();
const text = (pl: string, en: string) => (props.english ? en : pl);
const alertOpen = ref(false),
  confirmOpen = ref(false),
  customOpen = ref(false),
  nestedOpen = ref(false);
const size = ref<DialogSize>('md');
const outside = ref(true),
  escape = ref(true),
  long = ref(false),
  fail = ref(false);
const busy = ref(false),
  error = ref(''),
  result = ref('');
const name = ref<string | null>(''),
  city = ref('');
let timer: ReturnType<typeof globalThis.setTimeout> | undefined;
let failedAttempt = false;
function openConfirm() {
  failedAttempt = false;
  error.value = '';
  confirmOpen.value = true;
}
function save() {
  if (busy.value) return;
  busy.value = true;
  error.value = '';
  timer = globalThis.setTimeout(() => {
    busy.value = false;
    if (fail.value && !failedAttempt) {
      failedAttempt = true;
      error.value = text(
        'Operacja nie powiodła się. Ponów próbę lub anuluj.',
        'The operation failed. Retry or cancel.',
      );
      return;
    }
    confirmOpen.value = false;
    result.value = text(
      'Potwierdzono lokalną operację testową.',
      'Local test operation confirmed.',
    );
  }, 1000);
}
function changeSize(value: string) {
  if (['sm', 'md', 'lg', 'xl', 'full'].includes(value))
    size.value = value as DialogSize;
}
onScopeDispose(() => globalThis.clearTimeout(timer));
</script>
<template>
  <div class="space-y-6">
    <Card
      ><template #header
        ><h2>{{ text('Dialogi', 'Dialogs') }}</h2></template
      >
      <div class="flex flex-wrap gap-3">
        <Button @click="alertOpen = true">{{
          text('Otwórz alert', 'Open alert')
        }}</Button>
        <Button @click="openConfirm">{{
          text('Otwórz potwierdzenie', 'Open confirmation')
        }}</Button>
        <Button @click="customOpen = true">{{
          text('Otwórz własny dialog', 'Open custom dialog')
        }}</Button>
      </div>
    </Card>
    <Card
      ><template #header
        ><h2>
          {{ text('Sterowanie testowe dialogów', 'Dialog test controls') }}
        </h2></template
      >
      <div class="space-y-5">
        <Select
          :model-value="size"
          :label="text('Rozmiar', 'Size')"
          :locale="english ? 'en' : 'pl'"
          :options="[
            { value: 'sm', label: 'sm · 380px' },
            { value: 'md', label: 'md · 560px' },
            { value: 'lg', label: 'lg · 800px' },
            { value: 'xl', label: 'xl · 1120px' },
            { value: 'full', label: text('Pełny', 'Full') },
          ]"
          @update:model-value="changeSize"
        />
        <Select
          :model-value="outside ? 'both' : 'button'"
          :label="text('Zamykanie myszą', 'Pointer dismissal')"
          :locale="english ? 'en' : 'pl'"
          :options="[
            {
              value: 'button',
              label: text('Tylko przycisk ×', 'Close button only'),
            },
            {
              value: 'both',
              label: text(
                'Przycisk × i kliknięcie poza modalem',
                'Close button and outside click',
              ),
            },
          ]"
          @update:model-value="outside = $event === 'both'"
        />
        <Checkbox
          v-model="escape"
          :label="text('Zamykaj klawiszem Escape', 'Close with Escape')"
        />
        <Checkbox
          v-model="long"
          :label="
            text('Długa treść własnego dialogu', 'Long custom-dialog content')
          "
        />
        <Checkbox
          v-model="fail"
          :label="
            text(
              'Symuluj pierwszy błąd potwierdzenia',
              'Simulate first confirmation failure',
            )
          "
        />
        <p class="text-xs text-muted">
          {{
            text(
              'Potwierdzenie trwa sekundę; stan busy blokuje ponowienie i zamknięcie. Testowa operacja nie wysyła danych.',
              'Confirmation takes one second; busy state blocks duplicates and dismissal. The test operation sends no data.',
            )
          }}
        </p>
        <p v-if="result" role="status" class="text-xs text-muted">
          {{ result }}
        </p>
      </div>
    </Card>
    <AlertDialog
      v-model="alertOpen"
      :title="text('Informacja', 'Information')"
      :size="size"
      :close-on-outside="outside"
      :close-on-escape="escape"
      :locale="english ? 'en' : 'pl'"
    >
      <p>
        {{
          text(
            'Eksport zawiera wyłącznie widoczne kolumny. Sprawdź zakres danych przed rozpoczęciem.',
            'The export includes visible columns only. Check the data scope before starting.',
          )
        }}
      </p>
    </AlertDialog>
    <ConfirmDialog
      v-model="confirmOpen"
      :title="text('Potwierdź operację', 'Confirm operation')"
      :description="
        text(
          'Przykład lokalnej operacji z obsługą błędu i ponowienia.',
          'A local operation example with error recovery and retry.',
        )
      "
      :size="size"
      :busy="busy"
      :error="error"
      :close-on-outside="outside"
      :close-on-escape="escape"
      :locale="english ? 'en' : 'pl'"
      :confirm-label="text('Wykonaj operację', 'Run operation')"
      @confirm="save"
    >
      <p>
        {{
          text(
            'Czy wykonać operację dla wybranych rekordów?',
            'Run the operation for the selected records?',
          )
        }}
      </p>
    </ConfirmDialog>
    <Dialog
      v-model="customOpen"
      :title="text('Edytuj dane', 'Edit details')"
      :description="
        text(
          'Własna treść formularza pozostaje pod kontrolą aplikacji.',
          'Custom form content stays under application control.',
        )
      "
      :size="size"
      :close-on-outside="outside"
      :close-on-escape="escape"
      :locale="english ? 'en' : 'pl'"
    >
      <TextInput
        v-model="name"
        :label="text('Nazwa', 'Name')"
        autocomplete="organization"
      />
      <Select
        v-model="city"
        :label="text('Miasto', 'City')"
        searchable
        clearable
        :locale="english ? 'en' : 'pl'"
        :options="[
          { value: 'warsaw', label: 'Warszawa' },
          { value: 'krakow', label: 'Kraków' },
        ]"
      />
      <p v-for="n in long ? 24 : 0" :key="n">
        {{ n }} ·
        {{
          text(
            'Treść dialogu przewija się niezależnie od strony. Nagłówek i działania pozostają dostępne.',
            'Dialog content scrolls independently of the page. The heading and actions remain available.',
          )
        }}
      </p>
      <Button @click="nestedOpen = true">{{
        text('Otwórz zagnieżdżone potwierdzenie', 'Open nested confirmation')
      }}</Button>
      <ConfirmDialog
        v-model="nestedOpen"
        :title="text('Zagnieżdżone potwierdzenie', 'Nested confirmation')"
        :close-on-outside="outside"
        :locale="english ? 'en' : 'pl'"
        @confirm="nestedOpen = false"
        ><p>
          {{
            text(
              'Zamknięcie przywraca fokus do własnego dialogu.',
              'Closing returns focus to the custom dialog.',
            )
          }}
        </p></ConfirmDialog
      >
      <template #footer="{ close }"
        ><Button @click="close()">{{
          text('Zamknij', 'Close')
        }}</Button></template
      >
    </Dialog>
  </div>
</template>
