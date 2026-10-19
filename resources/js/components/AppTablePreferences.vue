<script setup lang="ts" generic="Row">
import { computed, ref, watch } from 'vue';
import AppButton from './AppButton.vue';
import AppIcon from './AppIcon.vue';
import AppTooltip from './AppTooltip.vue';
import AppCheckbox from './AppCheckbox.vue';
import AppTextInput from './AppTextInput.vue';
import AppNumberInput from './AppNumberInput.vue';
import AppSelect from './AppSelect.vue';
import AppDialog from './AppDialog.vue';
import AppAlert from './AppAlert.vue';
import type { TableColumn } from '../runa/ui/tables';
import type { TableViewsController } from '../composables/useTableViews';
const props = withDefaults(
  defineProps<{
    controller: TableViewsController;
    columns: readonly TableColumn<Row>[];
    english?: boolean;
    measuredWidths?: Readonly<Record<string, number>>;
  }>(),
  { english: false, measuredWidths: () => ({}) },
);
const text = (pl: string, en: string) => (props.english ? en : pl);
const open = ref(false),
  name = ref<string | null>(''),
  error = ref(''),
  message = ref('');
const dragged = ref<string | null>(null);
const widthErrors = ref<Record<string, string>>({});
const state = computed(() => props.controller.state.value);
const selected = computed(() =>
  state.value.views.find((v) => v.id === state.value.activeId),
);
watch(
  [() => selected.value?.id, () => selected.value?.name],
  ([, value]) => {
    name.value = value ?? '';
  },
  { immediate: true },
);
function act(action: () => void, success: string) {
  error.value = '';
  message.value = '';
  try {
    action();
    message.value = success;
  } catch (cause) {
    if (
      !(cause instanceof Error) ||
      !['invalid_view_name', 'view_limit'].includes(cause.message)
    )
      throw cause;
    error.value = text(
      'Podaj unikalną nazwę (1–80 znaków). Możesz zapisać do 50 widoków.',
      'Enter a unique name (1–80 characters). You can save up to 50 views.',
    );
  }
}
function visibility(key: string, visible: boolean) {
  props.controller.setColumns(
    state.value.current.columns.map((c) =>
      c.key === key ? { ...c, visible } : c,
    ),
  );
}
function width(key: string, value: string | null) {
  const number = value === null || value.trim() === '' ? null : Number(value);
  if (
    number !== null &&
    (!Number.isInteger(number) || number < 80 || number > 800)
  ) {
    widthErrors.value[key] = text('Podaj 80–800 px.', 'Enter 80–800 px.');
    return;
  }
  widthErrors.value[key] = '';
  props.controller.setColumns(
    state.value.current.columns.map((c) => {
      if (c.key !== key) return c;
      return number === null
        ? { key: c.key, visible: c.visible }
        : { ...c, width: number };
    }),
  );
}
function editWidth(key: string, value: string | null) {
  if (value === null || value.trim() === '') {
    widthErrors.value[key] = text('Podaj 80–800 px.', 'Enter 80–800 px.');
    return;
  }
  width(key, value);
}
const invalidWidths = computed(() =>
  Object.values(widthErrors.value).some(Boolean),
);
watch(open, () => {
  widthErrors.value = {};
});
function drop(key: string) {
  if (dragged.value) props.controller.reorder(dragged.value, key);
  dragged.value = null;
}
</script>
<template>
  <div class="flex flex-wrap items-end gap-2">
    <AppSelect
      size="sm"
      :locale="english ? 'en' : 'pl'"
      :model-value="
        state.activeId === state.defaultId || state.activeId === null
          ? '__default__'
          : state.activeId
      "
      :label="text('Dostępne widoki', 'Available views')"
      :options="[
        { value: '__default__', label: text('Widok domyślny', 'Default view') },
        ...state.views.map((v) => ({
          value: v.id,
          label: `${v.name}${v.id === state.defaultId ? ' ★' : ''}`,
        })),
      ]"
      @update:model-value="
        $event === '__default__'
          ? controller.selectDefault()
          : controller.select($event)
      "
    />
    <AppTooltip :text="text('Kolumny i widoki', 'Columns and views')">
      <AppButton
        size="sm"
        icon-only
        variant="ghost"
        :aria-label="text('Kolumny i widoki', 'Columns and views')"
        aria-haspopup="dialog"
        :aria-expanded="open"
        @click="open = true"
      >
        <AppIcon name="adjustments-horizontal" :size="20" />
      </AppButton>
    </AppTooltip>
    <span
      v-if="controller.dirty.value"
      role="status"
      class="self-center text-xs text-muted"
      >{{ text('Niezapisane zmiany', 'Unsaved changes') }}</span
    >
    <AppDialog
      v-model="open"
      :title="text('Kolumny i widoki', 'Columns and views')"
      :locale="english ? 'en' : 'pl'"
      size="lg"
    >
      <div class="space-y-5">
        <section class="space-y-3">
          <h3 class="font-semibold">{{ text('Kolumny', 'Columns') }}</h3>
          <p class="text-xs text-muted">
            {{
              text(
                'Zmień kolejność uchwytem lub strzałkami. Szerokość w pikselach; ↺ przywraca automatyczną.',
                'Reorder with the handle or arrow buttons. Width is in pixels; ↺ restores automatic sizing.',
              )
            }}
          </p>
          <ul class="space-y-2">
            <li
              v-for="(column, index) in state.current.columns"
              :key="column.key"
              class="grid grid-cols-[auto_minmax(0,1fr)_auto] items-center gap-2 rounded border border-line px-2 py-2 sm:grid-cols-[auto_minmax(0,1fr)_auto_auto]"
              @dragover.prevent
              @drop.prevent="drop(column.key)"
            >
              <span
                draggable="true"
                aria-hidden="true"
                class="cursor-grab px-1 text-muted"
                @dragstart="
                  dragged = column.key;
                  $event.dataTransfer?.setData('text/plain', column.key);
                "
                @dragend="dragged = null"
                >⠿</span
              >
              <AppCheckbox
                compact
                :model-value="column.visible"
                :label="
                  columns.find((c) => c.key === column.key)?.label ?? column.key
                "
                :disabled="
                  column.visible &&
                  state.current.columns.filter((c) => c.visible).length === 1
                "
                @update:model-value="visibility(column.key, $event)"
              />
              <div class="flex items-center gap-1">
                <AppButton
                  size="sm"
                  :disabled="index === 0"
                  :aria-label="`${text('Przesuń wyżej', 'Move up')}: ${columns.find((c) => c.key === column.key)?.label}`"
                  @click="controller.move(column.key, -1)"
                  >↑</AppButton
                >
                <AppButton
                  size="sm"
                  :disabled="index === state.current.columns.length - 1"
                  :aria-label="`${text('Przesuń niżej', 'Move down')}: ${columns.find((c) => c.key === column.key)?.label}`"
                  @click="controller.move(column.key, 1)"
                  >↓</AppButton
                >
              </div>
              <div
                class="col-span-3 flex items-start justify-end gap-2 sm:col-span-1"
              >
                <div class="w-24 shrink-0">
                  <AppNumberInput
                    size="sm"
                    hide-label
                    kind="integer"
                    unit="px"
                    :locale="english ? 'en' : 'pl'"
                    :invalid-message="
                      text('Podaj 80–800 px.', 'Enter 80–800 px.')
                    "
                    :model-value="
                      String(column.width ?? measuredWidths[column.key] ?? 160)
                    "
                    :error="widthErrors[column.key] ?? ''"
                    :label="`${text('Szerokość', 'Width')}: ${columns.find((c) => c.key === column.key)?.label}`"
                    @update:model-value="editWidth(column.key, $event)"
                  />
                </div>
                <AppButton
                  size="sm"
                  icon-only
                  :disabled="column.width === undefined"
                  :aria-label="`${text('Automatyczna szerokość', 'Automatic width')}: ${columns.find((c) => c.key === column.key)?.label}`"
                  @click="width(column.key, null)"
                  >↺</AppButton
                >
              </div>
            </li>
          </ul>
          <AppButton
            size="sm"
            @click="
              controller.resetColumns();
              widthErrors = {};
            "
            >{{ text('Przywróć kolumny', 'Reset columns') }}</AppButton
          >
        </section>
        <section class="space-y-3 border-t border-line pt-4">
          <h3 class="font-semibold">
            {{ text('Zapisane widoki', 'Saved views') }}
          </h3>
          <p class="text-xs text-muted">
            {{
              text(
                'Widok zapisuje wyszukiwanie, filtry, sortowanie, rozmiar strony i układ kolumn. Widok domyślny otwiera się po ponownym wejściu.',
                'Views save search, filters, sorting, page size and columns. The default view opens on your next visit.',
              )
            }}
          </p>
          <AppTextInput
            v-model="name"
            size="sm"
            :label="text('Nazwa widoku', 'View name')"
            :maxlength="80"
          />
          <div class="flex flex-wrap gap-2">
            <AppButton
              size="sm"
              :disabled="invalidWidths"
              @click="
                act(
                  () => controller.create(name ?? ''),
                  text('Utworzono widok.', 'View created.'),
                )
              "
              >{{ text('Zapisz jako nowy', 'Save as new') }}</AppButton
            >
            <AppButton
              size="sm"
              :disabled="!selected || invalidWidths"
              @click="
                act(
                  controller.update,
                  text('Zapisano zmiany.', 'Changes saved.'),
                )
              "
              >{{ text('Zapisz zmiany', 'Save changes') }}</AppButton
            >
            <AppButton
              size="sm"
              :disabled="!selected"
              @click="
                act(
                  () => controller.rename(name ?? ''),
                  text('Zmieniono nazwę.', 'View renamed.'),
                )
              "
              >{{ text('Zmień nazwę', 'Rename') }}</AppButton
            >
            <AppButton
              size="sm"
              :disabled="!selected"
              @click="controller.setDefault(state.activeId)"
              >{{ text('Ustaw domyślny', 'Set default') }}</AppButton
            >
            <AppButton
              size="sm"
              :disabled="!state.defaultId"
              @click="controller.setDefault(null)"
              >{{ text('Usuń domyślny', 'Clear default') }}</AppButton
            >
            <AppButton
              size="sm"
              variant="outline"
              tone="danger"
              :disabled="!selected"
              @click="controller.remove()"
              >{{ text('Usuń widok', 'Delete view') }}</AppButton
            >
          </div>
          <AppButton size="sm" @click="controller.reset()">{{
            text('Przywróć ustawienia początkowe', 'Reset current view')
          }}</AppButton>
          <p v-if="state.defaultId" class="text-xs text-muted">
            {{ text('Domyślny', 'Default') }}:
            {{ state.views.find((v) => v.id === state.defaultId)?.name }}
          </p>
          <AppAlert v-if="error" tone="danger">{{ error }}</AppAlert>
          <p role="status" class="text-xs text-muted">{{ message }}</p>
        </section>
      </div>
    </AppDialog>
  </div>
</template>
