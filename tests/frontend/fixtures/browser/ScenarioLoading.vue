<script setup lang="ts">
import { computed, ref } from 'vue';
import {
  Card,
  Button,
  Spinner,
  Skeleton,
  Progress,
  AsyncContent,
  EmptyState,
  Statistic,
  Statistics,
  useStatisticsMode,
} from '@/runa/ui';
import type { AsyncContentState, FeedbackTone } from '@/runa/ui';
const props = defineProps<{ english: boolean }>();
const text = (pl: string, en: string) => (props.english ? en : pl);
const state = ref<AsyncContentState>('loading');
const amount = ref(35);
const mode = useStatisticsMode({
  applicationId: 'runa-review',
  moduleKey: 'ui',
  viewKey: 'loading',
});
const filtered = ref(false);
const states = computed(() => [
  { value: 'loading' as const, label: text('Ładowanie', 'Loading') },
  { value: 'error' as const, label: text('Błąd', 'Error') },
  { value: 'empty' as const, label: text('Brak wyników', 'Empty') },
  { value: 'success' as const, label: text('Gotowe', 'Success') },
]);
const tones: FeedbackTone[] = [
  'neutral',
  'success',
  'info',
  'warning',
  'danger',
];
</script>
<template>
  <div class="space-y-6">
    <Card
      ><template #header
        ><h2>
          {{ text('Wskaźniki ładowania', 'Loading indicators') }}
        </h2></template
      >
      <div class="flex flex-wrap items-center gap-6 text-primary">
        <Spinner
          v-for="size in ['sm', 'md', 'lg'] as const"
          :key="size"
          :size="size"
          :label="text('Ładowanie', 'Loading')"
        /><Button busy>{{ text('Zapisywanie', 'Saving') }}</Button>
      </div>
      <div class="mt-6 grid gap-5 sm:grid-cols-2">
        <Skeleton /><Skeleton shape="block" /></div
    ></Card>
    <Card
      ><template #header
        ><h2>{{ text('Postęp operacji', 'Operation progress') }}</h2></template
      >
      <div class="space-y-5">
        <Progress
          :label="text('Przetwarzanie danych', 'Processing data')"
          :value="amount"
        />
        <div class="flex flex-wrap gap-2">
          <Button
            :disabled="amount === 0"
            @click="amount = Math.max(0, amount - 10)"
            >−10%</Button
          ><Button
            :disabled="amount === 100"
            @click="amount = Math.min(100, amount + 10)"
            >+10%</Button
          >
        </div>
        <Progress
          :label="text('Oczekiwanie na wynik', 'Waiting for a result')"
        /><Progress
          :label="text('Zakończono', 'Completed')"
          :value="100"
          tone="success"
        /></div
    ></Card>
    <Card
      ><template #header
        ><h2>{{ text('Treść asynchroniczna', 'Async content') }}</h2></template
      >
      <div class="space-y-5">
        <div
          role="group"
          class="flex flex-wrap gap-2"
          :aria-label="text('Stan treści', 'Content state')"
        >
          <Button
            v-for="option in states"
            :key="option.value"
            :aria-pressed="state === option.value"
            @click="state = option.value"
            >{{ option.label }}</Button
          >
        </div>
        <AsyncContent
          :state="state"
          :loading-label="text('Wczytywanie listy', 'Loading list')"
          :error-title="
            text('Nie udało się wczytać listy', 'Unable to load list')
          "
          :error-message="
            text(
              'Sprawdź połączenie i spróbuj ponownie.',
              'Check your connection and try again.',
            )
          "
          :retry-label="text('Spróbuj ponownie', 'Retry')"
          :empty-title="text('Brak pasujących wyników', 'No matching results')"
          :empty-description="
            text(
              'Zmień filtry, aby zobaczyć więcej danych.',
              'Change filters to see more data.',
            )
          "
          @retry="state = 'loading'"
          ><p class="py-5 text-sm">
            {{
              text(
                'Lista została wczytana. Ten przykład nie wykonuje żądań sieciowych.',
                'The list has loaded. This example makes no network requests.',
              )
            }}
          </p></AsyncContent
        >
      </div></Card
    >
    <EmptyState
      :title="text('Nie dodano jeszcze elementów', 'No items added yet')"
      :description="
        text(
          'Przykład pustego widoku z własną akcją.',
          'Empty view with a caller-owned action.',
        )
      "
      icon="layers-intersect"
      ><template #actions
        ><Button @click="state = 'success'">{{
          text('Pokaż przykładową treść', 'Show sample content')
        }}</Button></template
      ></EmptyState
    >
    <Statistics
      v-model="mode"
      :label="text('Statystyki', 'Statistics')"
      :global-label="text('Globalne', 'Global')"
      :filtered-label="text('Filtrowane', 'Filtered')"
      ><template #default="{ mode: activeMode }"
        ><Statistic
          :label="text('Otwarte tematy', 'Open topics')"
          :value="activeMode === 'filtered' && filtered ? 4 : 12"
          icon="layers-intersect"
          :description="
            text(
              'Zestaw danych dostarcza widok.',
              'The view supplies the dataset.',
            )
          "
          :trend="
            text(
              '↑ 2 względem poprzedniego okresu',
              '↑ 2 over the previous period',
            )
          "
          tone="info" /><Statistic
          :label="text('Postęp', 'Progress')"
          :value="activeMode === 'filtered' && filtered ? '50%' : '75%'"
          :trend="text('Realizacja planu', 'Plan completion')"
          tone="success"
          ><template #visualization
            ><Progress
              :label="text('Zrealizowano', 'Completed')"
              :value="activeMode === 'filtered' && filtered ? 50 : 75"
              tone="success" /></template></Statistic
        ><Statistic
          :label="text('Członkowie zespołu', 'Team members')"
          value="8"
          :description="
            text(
              'Wartość niezależna od filtra.',
              'Value independent of the filter.',
            )
          " /></template
    ></Statistics>
    <div class="flex flex-wrap items-center gap-3">
      <Button :aria-pressed="filtered" @click="filtered = !filtered">{{
        text('Filtr: bieżący etap', 'Filter: current stage')
      }}</Button>
      <p class="text-xs text-muted">
        {{
          text(
            'Tryb jest zapamiętywany dla tego widoku. Filtr wpływa tylko na statystyki filtrowane.',
            'The mode is remembered for this view. Filters only affect filtered statistics.',
          )
        }}
      </p>
    </div>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <Statistic
        v-for="tone in tones"
        :key="tone"
        :tone="tone"
        :label="tone"
        value="24"
        :trend="text('Przykład roli semantycznej', 'Semantic role example')"
      />
    </div>
  </div>
</template>
