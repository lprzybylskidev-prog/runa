<script setup lang="ts">
import { computed, ref, shallowRef } from 'vue';
import {
  Card,
  Form,
  DateInput,
  TimeInput,
  DateTimeInput,
  DateRangeInput,
  Checkbox,
  Switch,
  RadioGroup,
  FileInput,
  ColorInput,
  TagsInput,
  HiddenInput,
  Button,
  Alert,
  Select,
  formatDateOnly,
} from '@/runa/ui';
import type { DateRangeValue, TimeDisambiguation } from '@/runa/ui';
const props = defineProps<{ english: boolean }>();
const text = (pl: string, en: string) => (props.english ? en : pl);
const locale = computed(() =>
  props.english ? ('en' as const) : ('pl' as const),
);
const date = ref<string | null>('2026-10-19'),
  time = ref<string | null>('09:30'),
  instant = ref<string | null>('2026-10-19T07:30:00.000Z');
const range = ref<DateRangeValue>({ start: '2026-10-19', end: '2026-10-23' });
const dateValid = ref(true),
  timeValid = ref(true),
  instantValid = ref(true),
  rangeValid = ref(true),
  colorValid = ref(true);
const agree = ref<boolean | null>(null),
  active = ref(true),
  radio = ref<string | null>('standard');
const single = shallowRef<readonly globalThis.File[]>([]),
  multiple = shallowRef<readonly globalThis.File[]>([]);
const singleIssues = ref(0),
  multipleIssues = ref(0);
const tagsValid = ref(true);
const tagsControl = ref<InstanceType<typeof TagsInput>>();
const color = ref<string | null>('#8650ba'),
  tags = ref<readonly string[]>(['Runa', 'Vue']);
const disambiguation = ref<TimeDisambiguation>('reject');
const payload = ref('');
const rejected = ref(false);
const valid = computed(
  () =>
    dateValid.value &&
    timeValid.value &&
    instantValid.value &&
    rangeValid.value &&
    colorValid.value &&
    tagsValid.value &&
    !singleIssues.value &&
    !multipleIssues.value,
);
function reject() {
  rejected.value = true;
  if (!tagsValid.value) tagsControl.value?.focus();
}
function show() {
  rejected.value = false;
  payload.value = JSON.stringify(
    {
      date: date.value,
      time: time.value,
      instant: instant.value,
      timeZone: 'Europe/Warsaw',
      range: range.value,
      checkbox: agree.value,
      enabled: active.value,
      plan: radio.value,
      color: color.value,
      tags: tags.value,
      files: [...single.value, ...multiple.value].map((file) => ({
        name: file.name,
        bytes: file.size,
        type: file.type,
      })),
      publicId: 'demo-public-id',
    },
    null,
    2,
  );
}
function mode(value: string) {
  if (['reject', 'earlier', 'later'].includes(value))
    disambiguation.value = value as TimeDisambiguation;
}
const fileLabels = computed(() => ({
  chooseLabel: text('Wybierz pliki', 'Choose files'),
  removeLabel: text('Usuń', 'Remove'),
  emptyLabel: text('Nie wybrano plików', 'No files selected'),
  countMessage: text('Przekroczono limit liczby plików.', 'Too many files.'),
  sizeMessage: text('Plik jest za duży.', 'File is too large.'),
  typeMessage: text('Niedozwolony typ pliku.', 'File type is not accepted.'),
}));
</script>
<template>
  <Form
    :valid="valid"
    :label="text('Dodatkowe pola', 'Additional fields')"
    @submit="show"
    @invalid="reject"
    ><template #feedback
      ><Alert
        v-if="rejected && !valid"
        tone="danger"
        :title="text('Popraw wskazane pola', 'Correct the indicated fields')"
    /></template>
    <Card
      ><template #header
        ><h2>{{ text('Data i czas', 'Date and time') }}</h2></template
      >
      <div class="grid min-w-0 grid-cols-1 gap-5 md:grid-cols-2">
        <DateInput
          v-model="date"
          :label="text('Data', 'Date')"
          :locale="locale"
          :helper="date && dateValid ? formatDateOnly(date, locale) : ''"
          :invalid-message="text('Sprawdź datę.', 'Check the date.')"
          @validity="dateValid = $event"
        /><TimeInput
          v-model="time"
          :label="text('Godzina', 'Time')"
          :locale="locale"
          :invalid-message="text('Sprawdź godzinę.', 'Check the time.')"
          @validity="timeValid = $event"
        />
        <div class="md:col-span-2">
          <DateTimeInput
            v-model="instant"
            :label="text('Termin', 'Scheduled time')"
            :locale="locale"
            time-zone="Europe/Warsaw"
            :zone-label="text('Strefa czasowa', 'Time zone')"
            :disambiguation="disambiguation"
            :invalid-message="
              text(
                'Nieprawidłowa, nieistniejąca lub niejednoznaczna godzina. Sprawdź termin i regułę zmiany czasu.',
                'Invalid, nonexistent or ambiguous time. Check the date and daylight-saving rule.',
              )
            "
            @validity="instantValid = $event"
          />
        </div>
        <DateRangeInput
          v-model="range"
          :label="text('Zakres dat', 'Date range')"
          :start-label="text('Od', 'From')"
          :end-label="text('Do', 'To')"
          :locale="locale"
          class="md:col-span-2"
          :invalid-message="
            text(
              'Sprawdź daty i ich kolejność.',
              'Check the dates and their order.',
            )
          "
          @validity="rangeValid = $event"
        /><Select
          :model-value="disambiguation"
          :label="
            text(
              'Powtórzona godzina przy zmianie czasu',
              'Repeated daylight-saving time',
            )
          "
          :options="[
            {
              value: 'reject',
              label: text('Wymagaj rozstrzygnięcia', 'Require a decision'),
            },
            {
              value: 'earlier',
              label: text('Pierwsze wystąpienie', 'Earlier occurrence'),
            },
            {
              value: 'later',
              label: text('Drugie wystąpienie', 'Later occurrence'),
            },
          ]"
          @update:model-value="mode"
        />
      </div>
      <p class="mt-4 break-all text-xs text-muted">
        {{ text('Wartość UTC', 'UTC value') }}: {{ instant ?? '—' }}
      </p>
      <p class="mt-2 text-xs text-muted">
        {{
          text(
            'Próby zmiany czasu: 29.03.2026 02:30 (brak godziny), 25.10.2026 02:30 (dwa wystąpienia). Okna wyboru daty i czasu są obsługiwane przez przeglądarkę.',
            'Daylight-saving examples: 2026-03-29 02:30 (gap), 2026-10-25 02:30 (two occurrences). Date/time picker windows are provided by the browser.',
          )
        }}
      </p></Card
    >
    <Card
      ><template #header
        ><h2>{{ text('Pola logiczne', 'Logical inputs') }}</h2></template
      >
      <div class="space-y-5">
        <Checkbox
          v-model="agree"
          :label="text('Uwzględnij w zestawieniu', 'Include in summary')"
          :helper="
            text(
              'Początkowo stan nieokreślony; Spacja zmienia wybór.',
              'Initially indeterminate; Space changes selection.',
            )
          "
        /><Switch
          v-model="active"
          :label="text('Aktywny widok', 'Active view')"
        /><RadioGroup
          v-model="radio"
          :label="text('Tryb prezentacji', 'Presentation mode')"
          :options="[
            { value: 'standard', label: text('Standardowy', 'Standard') },
            { value: 'compact', label: text('Kompaktowy', 'Compact') },
            {
              value: 'unavailable',
              label: text('Niedostępny', 'Unavailable'),
              disabled: true,
            },
          ]"
        /><Checkbox
          :model-value="true"
          disabled
          :label="text('Wyłączony wybór', 'Disabled choice')"
        /><Switch
          :model-value="true"
          readonly
          :label="text('Przełącznik tylko do odczytu', 'Read-only switch')"
        /></div
    ></Card>
    <Card
      ><template #header
        ><h2>{{ text('Wybór plików', 'File selection') }}</h2></template
      >
      <div class="grid min-w-0 grid-cols-1 gap-5 md:grid-cols-2">
        <FileInput
          v-model="single"
          :label="text('Jeden dokument', 'Single document')"
          accept=".pdf"
          :max-bytes="1048576"
          v-bind="fileLabels"
          :helper="
            text(
              'PDF, do 1 MB. Tylko wybór lokalny, bez wysyłania.',
              'PDF, up to 1 MB. Local selection only, no upload.',
            )
          "
          @issues="singleIssues = $event.length"
        /><FileInput
          v-model="multiple"
          :label="text('Wiele załączników', 'Multiple attachments')"
          multiple
          :max-files="3"
          :max-bytes="2097152"
          accept="image/*,.pdf"
          v-bind="fileLabels"
          :helper="
            text(
              'Do 3 plików, po 2 MB. Obrazy lub PDF.',
              'Up to 3 files, 2 MB each. Images or PDF.',
            )
          "
          @issues="multipleIssues = $event.length"
        /></div
    ></Card>
    <Card
      ><template #header
        ><h2>
          {{
            text('Kolor, tagi i pole ukryte', 'Color, tags and hidden input')
          }}
        </h2></template
      >
      <div class="space-y-5">
        <ColorInput
          v-model="color"
          :label="text('Kolor wyróżnienia', 'Accent color')"
          :pick-label="text('Wybierz kolor', 'Choose color')"
          :invalid-message="
            text(
              'Podaj kolor w formacie #RRGGBB.',
              'Enter a color in #RRGGBB format.',
            )
          "
          @validity="colorValid = $event"
        /><TagsInput
          ref="tagsControl"
          v-model="tags"
          :label="text('Tagi', 'Tags')"
          :helper="
            text(
              'Wpisz tag i zatwierdź Enterem. Do 6 tagów; Escape usuwa niedokończony wpis.',
              'Type a tag and confirm with Enter. Up to 6 tags; Escape clears the unfinished entry.',
            )
          "
          :max-tags="6"
          :remove-label="text('Usuń', 'Remove')"
          :limit-message="text('Osiągnięto limit tagów.', 'Tag limit reached.')"
          :duplicate-message="
            text('Ten tag już istnieje.', 'This tag already exists.')
          "
          @validity="tagsValid = $event"
        /><HiddenInput model-value="demo-public-id" name="public_id" />
        <p class="text-xs text-muted">
          {{
            text(
              'Pole hidden nie jest widoczne i nie stanowi zabezpieczenia. Jego wartość pojawi się w podglądzie danych.',
              'The hidden field is invisible and is not a security boundary. Its value appears in the data preview.',
            )
          }}
        </p>
      </div></Card
    >
    <template #actions
      ><Button type="submit" variant="solid">{{
        text('Pokaż wartości', 'Show values')
      }}</Button></template
    ></Form
  >
  <pre
    v-if="payload"
    class="mt-5 overflow-x-auto rounded border border-line bg-surface p-4 text-xs"
    >{{ payload }}</pre>
</template>
