<script setup lang="ts">
import { ref } from 'vue';
import { Select, Checkbox, Card, type SelectionProvider } from '@/runa/ui';
const smallChoice = ref('a');
const props = defineProps<{ english: boolean }>();
const text = (pl: string, en: string) => (props.english ? en : pl);
const single = ref('');
const multiple = ref<string[]>(['warsaw']);
const remote = ref<string[]>([]);
const fail = ref(false);
const options = [
  { value: 'warsaw', label: 'Warszawa' },
  { value: 'krakow', label: 'Kraków' },
  { value: 'gdansk', label: 'Gdańsk' },
  { value: 'closed', label: 'Niedostępne / Unavailable', disabled: true },
];
const provider: SelectionProvider = async ({
  query,
  cursor,
  limit,
  signal,
}) => {
  await new Promise<void>((resolve, reject) => {
    const timer = globalThis.setTimeout(resolve, query === 'slow' ? 1400 : 400);
    signal.addEventListener(
      'abort',
      () => {
        globalThis.clearTimeout(timer);
        reject(new Error('Aborted'));
      },
      { once: true },
    );
  });
  if (fail.value) throw new Error('Review failure');
  const start = Number(cursor ?? 0);
  const matches = Array.from({ length: 10000 }, (_, index) => ({
    value: String(index + 1),
    label: `Record ${String(index + 1).padStart(5, '0')}`,
  })).filter((o) => o.label.toLowerCase().includes(query.toLowerCase()));
  return {
    options: matches.slice(start, start + limit),
    nextCursor: start + limit < matches.length ? String(start + limit) : null,
  };
};
</script>
<template>
  <div class="space-y-6">
    <Card
      ><template #header
        ><h2>{{ text('Wybór lokalny', 'Local selection') }}</h2></template
      >
      <div class="grid gap-6 md:grid-cols-2">
        <Select
          v-model="single"
          :label="text('Miasto', 'City')"
          :options="options"
          searchable
          clearable
          :locale="english ? 'en' : 'pl'"
        />
        <Select
          v-model="multiple"
          :multiple="true"
          :label="text('Wiele miast', 'Multiple cities')"
          :options="options"
          searchable
          clearable
          :locale="english ? 'en' : 'pl'"
        />
        <Select
          :locale="english ? 'en' : 'pl'"
          model-value="warsaw"
          :label="text('Tylko odczyt', 'Read only')"
          :options="options"
          readonly
        />
        <Select
          :locale="english ? 'en' : 'pl'"
          model-value=""
          :label="text('Błąd pola', 'Invalid field')"
          :options="options"
          :error="text('Wybierz miasto.', 'Choose a city.')"
          required
        />
        <Select
          :locale="english ? 'en' : 'pl'"
          model-value="warsaw"
          :label="text('Wyłączone', 'Disabled')"
          :options="options"
          disabled
        />
      </div>
    </Card>
    <Card
      ><template #header
        ><h2>
          {{
            text(
              'Stronicowany provider · 10 000 rekordów',
              'Paged provider · 10,000 records',
            )
          }}
        </h2></template
      >
      <div class="space-y-4">
        <Checkbox
          v-model="fail"
          :label="text('Symuluj błąd pobierania', 'Simulate loading failure')"
        />
        <Select
          v-model="remote"
          :multiple="true"
          :provider="provider"
          :label="text('Rekordy zdalne', 'Remote records')"
          :helper="
            text(
              '40 wyników na stronę. Wybór pozostaje po zmianie zapytania i strony.',
              '40 results per page. Selection survives query and page changes.',
            )
          "
          clearable
          :locale="english ? 'en' : 'pl'"
        />
        <p class="text-xs text-muted">{{ remote.join(', ') || '—' }}</p>
      </div>
    </Card>
    <div class="grid gap-4 sm:grid-cols-2">
      <Select
        v-model="smallChoice"
        size="sm"
        :options="[
          { value: 'a', label: 'Alpha' },
          { value: 'b', label: 'Beta' },
        ]"
        :locale="english ? 'en' : 'pl'"
        :label="english ? 'Small select (sm)' : 'Mały select (sm)'"
      />
    </div>
  </div>
</template>
