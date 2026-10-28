<script setup lang="ts">
import { computed, ref } from 'vue';
import {
  Alert,
  Button,
  Card,
  Checkbox,
  ConfirmDialog,
  Form,
  NumberInput,
  Select,
  Table,
  TableActions,
  Tabs,
  TextInput,
  createClientExportProvider,
  createClientPrintAdapter,
  type ExportColumn,
  type RowSelection,
  type TableColumn,
  type TableQuery,
} from '@/runa/ui';
const props = withDefaults(defineProps<{ english?: boolean }>(), {
  english: false,
});
const text = (pl: string, en: string) => (props.english ? en : pl);
const name = ref<string | null>(''),
  amount = ref<string | null>('12.50'),
  amountValid = ref(true),
  city = ref('warsaw'),
  enabled = ref(true),
  tab = ref('form'),
  confirming = ref(false),
  submitted = ref(false),
  invalid = ref(false);
const error = computed(() =>
  invalid.value && !name.value?.trim()
    ? text('Podaj nazwę.', 'Enter a name.')
    : '',
);
function submit() {
  invalid.value = true;
  if (name.value?.trim() && amountValid.value) confirming.value = true;
}
const rows = [
  { public_id: 'record-a', name: 'Atlas', city: 'Warszawa', count: 12 },
  { public_id: 'record-b', name: 'Aurora', city: 'Gdańsk', count: 25 },
];
type Row = (typeof rows)[number];
const columns = computed<TableColumn<Row>[]>(() => [
  {
    key: 'name',
    label: text('Nazwa', 'Name'),
    value: (row) => row.name,
    sortable: true,
  },
  {
    key: 'city',
    label: text('Miasto', 'City'),
    value: (row) => row.city,
    sortable: true,
  },
  {
    key: 'count',
    label: text('Dokumenty', 'Documents'),
    value: (row) => row.count,
    sortable: true,
  },
]);
const exportColumns = computed<ExportColumn<Row>[]>(() =>
  columns.value.map((column) => ({
    key: column.key,
    label: column.label,
    value: column.value,
  })),
);
const query = ref<TableQuery>({ page: 1, perPage: 5, sort: null, filters: {} });
const selection = ref<RowSelection<TableQuery>>({
  mode: 'explicit',
  publicIds: [],
});
const provider = createClientExportProvider({
  rows: () => rows,
  columns: () => exportColumns.value,
  tableColumns: () => columns.value,
  rowKey: (row) => row.public_id,
});
const printer = createClientPrintAdapter({
  rows: () => rows,
  columns: () => exportColumns.value,
  tableColumns: () => columns.value,
  rowKey: (row) => row.public_id,
  title: () => text('Projekty', 'Projects'),
});
</script>
<template>
  <main class="mx-auto max-w-6xl space-y-6 p-4 sm:p-8">
    <h1 class="text-2xl font-semibold">
      {{ text('Komponenty bez layoutu', 'Components without a layout') }}
    </h1>
    <nav
      :aria-label="text('Opcje podglądu', 'Preview options')"
      class="flex flex-wrap gap-4 text-sm underline"
    >
      <a href="?locale=pl&theme=light">PL · jasny</a
      ><a href="?locale=pl&theme=dark">PL · ciemny</a
      ><a href="?locale=en&theme=light">EN · light</a
      ><a href="?locale=en&theme=dark">EN · dark</a>
    </nav>
    <p class="text-sm text-muted">
      {{
        text(
          'Niezależny frontend testowy. Brak layoutu, routera i inicjalizacji preferencji.',
          'Independent test frontend. No layout, router or preference initialization.',
        )
      }}
    </p>
    <Tabs
      v-model="tab"
      :label="text('Obszar testowy', 'Test area')"
      :items="[
        { id: 'form', label: text('Formularz', 'Form') },
        { id: 'data', label: text('Tabela', 'Table') },
      ]"
    >
      <template #form>
        <Card>
          <Form
            :label="text('Dane testowe', 'Test data')"
            :valid="!error && amountValid"
            @submit="submit"
            @invalid="submit"
          >
            <TextInput
              v-model="name"
              :label="text('Nazwa', 'Name')"
              :error="error"
              required
            />
            <NumberInput
              v-model="amount"
              :label="text('Kwota', 'Amount')"
              :locale="english ? 'en' : 'pl'"
              kind="decimal"
              :invalid-message="
                text('Podaj prawidłową liczbę.', 'Enter a valid number.')
              "
              @validity="amountValid = $event"
            />
            <Select
              v-model="city"
              :label="text('Miasto', 'City')"
              :locale="english ? 'en' : 'pl'"
              :options="[
                { value: 'warsaw', label: 'Warszawa' },
                { value: 'gdansk', label: 'Gdańsk' },
              ]"
            />
            <Checkbox v-model="enabled" :label="text('Aktywny', 'Active')" />
            <template #actions
              ><Button type="submit">{{
                text('Zapisz', 'Save')
              }}</Button></template
            >
          </Form>
          <Alert
            v-if="submitted"
            tone="success"
            :title="text('Zapisano dane testowe', 'Test data saved')"
          />
        </Card>
      </template>
      <template #data>
        <Table
          v-model:query="query"
          v-model:selection="selection"
          mode="client"
          :rows="rows"
          :columns="columns"
          :row-key="(row) => row.public_id"
          selectable
          :english="english"
          :caption="text('Projekty', 'Projects')"
        >
          <template #toolbar="{ feedbackTarget }"
            ><TableActions
              :feedback-target="feedbackTarget"
              :provider="provider"
              :print-adapter="printer"
              :query="query"
              :selection="selection"
              :columns="exportColumns"
              :english="english"
              filename="standalone"
          /></template>
        </Table>
      </template>
    </Tabs>
    <ConfirmDialog
      v-model="confirming"
      :title="text('Zapisać dane testowe?', 'Save test data?')"
      :locale="english ? 'en' : 'pl'"
      @confirm="
        submitted = true;
        confirming = false;
      "
      >{{ name }}</ConfirmDialog
    >
  </main>
</template>
