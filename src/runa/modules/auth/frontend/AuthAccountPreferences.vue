<script setup lang="ts">
import { computed, reactive, ref, watch, onScopeDispose, nextTick } from 'vue';
import {
  Button,
  Card,
  ConfirmDialog,
  Form,
  FormNotifications,
  RadioGroup,
  Select,
  Spinner,
  Switch,
} from '@/runa/ui';
import type { useDisplayPreferences } from '../../../resources/js/composables/useDisplayPreferences';
import type { useAuthAccount } from './useAuthAccount';
type Choice = ReturnType<typeof useAuthAccount>['delivery']['value'][number];
const props = defineProps<{
  account: ReturnType<typeof useAuthAccount>;
  display: ReturnType<typeof useDisplayPreferences>;
}>();
const emit = defineEmits<{ 'feedback-modal': [active: boolean] }>();
const {
  text,
  locale,
  profile,
  delivery,
  busy,
  confirmed,
  failure,
  failureText,
  notice,
  saveDelivery,
  resetPreferences,
  selectTab,
} = props.account;
const resetOpen = ref(false),
  attempt = ref(0),
  failedKey = ref<string>();
const drafts = reactive<Record<string, Choice>>({});
const recipientErrors = reactive<Record<string, string>>({});
const forms = new Map<string, { focusInvalid: () => void }>();
const optional = computed(() =>
  delivery.value.filter((choice) => !choice.mandatory && !choice.sensitive),
);
const required = computed(() =>
  delivery.value.filter((choice) => choice.mandatory || choice.sensitive),
);
const recipientOptions = computed(() => [
  { value: 'primary', label: profile.value?.email ?? '' },
  ...(profile.value?.secondary_emails
    .filter((address) => address.verified_at)
    .map((address) => ({ value: address.public_id, label: address.email })) ??
    []),
]);
const keyOf = (choice: Choice) => choice.module + ':' + choice.key;
const copy = (choice: Choice): Choice => ({
  ...choice,
  names: { ...choice.names },
  recipients: [...choice.recipients],
});
function same(a: Choice | undefined, b: Choice | undefined) {
  return Boolean(
    a &&
    b &&
    a.enabled === b.enabled &&
    [...a.recipients].sort().join('\0') === [...b.recipients].sort().join('\0'),
  );
}
function dirty(choice: Choice) {
  return !same(drafts[keyOf(choice)], choice);
}
watch(
  delivery,
  (next, previous) => {
    for (const choice of next) {
      const key = keyOf(choice);
      const before = previous?.find((item) => keyOf(item) === key);
      if (!drafts[key] || same(drafts[key], before)) drafts[key] = copy(choice);
    }
    for (const key of Object.keys(drafts))
      if (!next.some((item) => keyOf(item) === key)) {
        delete drafts[key];
        delete recipientErrors[key];
        forms.delete(key);
      }
  },
  { immediate: true },
);
watch(resetOpen, (value) => emit('feedback-modal', value));
onScopeDispose(() => {
  confirmed.value = false;
  forms.clear();
  emit('feedback-modal', false);
});
function selectDisplay(preference: 'language' | 'theme', value: string) {
  const origin =
    document.activeElement instanceof window.HTMLElement
      ? document.activeElement
      : undefined;
  props.display.selectPreference(preference, value, origin);
}
function revert(choice: Choice) {
  if (busy.value) return;
  const key = keyOf(choice);
  drafts[key] = copy(choice);
  delete recipientErrors[key];
  if (failedKey.value === key) {
    failure.value = '';
    notice.value = '';
    failedKey.value = undefined;
  }
}
async function save(choice: Choice) {
  if (busy.value) return;
  const key = keyOf(choice),
    draft = drafts[key];
  if (!draft || !dirty(choice)) return;
  failedKey.value = key;
  if (!draft.recipients.length) {
    recipientErrors[key] = text(
      'Wybierz co najmniej jeden adres e-mail.',
      'Select at least one email address.',
    );
    await nextTick();
    forms.get(key)?.focusInvalid();
    return;
  }
  delete recipientErrors[key];
  props.account.form.value = forms.get(key);
  const submitted = copy(draft);
  await saveDelivery(submitted);
  if (!failure.value && notice.value) {
    const current = delivery.value.find((item) => keyOf(item) === key);
    if (current && same(drafts[key], submitted)) drafts[key] = copy(current);
    failedKey.value = undefined;
  }
}
async function reload(key = failedKey.value) {
  if (busy.value) return;
  await selectTab('preferences');
  if (failure.value) return;
  const current = delivery.value.find((item) => keyOf(item) === key);
  if (current) revert(current);
}
function beginReset() {
  if (busy.value) return;
  confirmed.value = false;
  failure.value = '';
  notice.value = '';
  attempt.value = 0;
  resetOpen.value = true;
}
function closeReset(value: boolean) {
  if (value || busy.value) return;
  resetOpen.value = false;
  confirmed.value = false;
  failure.value = '';
  notice.value = '';
}
function reloadPage() {
  if (!busy.value) window.location.reload();
}
async function reset() {
  if (busy.value) return;
  attempt.value++;
  confirmed.value = true;
  await resetPreferences(true);
  if (!failure.value && notice.value) {
    for (const choice of delivery.value) {
      drafts[keyOf(choice)] = copy(choice);
      delete recipientErrors[keyOf(choice)];
    }
    resetOpen.value = false;
    failedKey.value = undefined;
  }
  confirmed.value = false;
}
defineExpose({ beginReset, reload });
</script>
<template>
  <div
    v-if="profile"
    class="grid min-w-0 items-start gap-6 xl:grid-cols-2"
    data-account-preferences
  >
    <Card data-preferences-display>
      <template #header
        ><h3>
          {{ text('Wygląd i język', 'Appearance and language') }}
        </h3></template
      >
      <fieldset :disabled="busy" class="min-w-0 space-y-6">
        <RadioGroup
          :model-value="display.locale.value"
          name="preference-language"
          :label="text('Język interfejsu', 'Interface language')"
          :options="[
            { value: 'pl', label: 'Polski' },
            { value: 'en', label: 'English' },
          ]"
          @update:model-value="selectDisplay('language', $event)"
        />
        <RadioGroup
          :model-value="display.theme.value"
          name="preference-theme"
          :label="text('Motyw', 'Theme')"
          :options="[
            { value: 'system', label: text('Systemowy', 'System') },
            { value: 'light', label: text('Jasny', 'Light') },
            { value: 'dark', label: text('Ciemny', 'Dark') },
          ]"
          :helper="
            text(
              'Motyw systemowy dopasowuje się do ustawień urządzenia.',
              'System theme follows your device settings.',
            )
          "
          @update:model-value="selectDisplay('theme', $event)"
        />
        <p class="text-xs leading-relaxed text-muted">
          {{
            text(
              'Zmiany zapisują się automatycznie na Twoim koncie.',
              'Changes are saved to your account automatically.',
            )
          }}
        </p>
      </fieldset>
    </Card>
    <Card data-preferences-delivery>
      <template #header
        ><h3>
          {{ text('Powiadomienia e-mail', 'Email notifications') }}
        </h3></template
      >
      <div class="space-y-5">
        <p class="text-xs leading-relaxed text-muted">
          {{
            text(
              'Włącz wybrane wiadomości, wskaż odbiorców i zapisz zmiany w danym wierszu.',
              'Enable the emails you want, select recipients and save each row.',
            )
          }}
        </p>
        <Spinner
          v-if="busy && !delivery.length"
          :label="text('Wczytywanie powiadomień', 'Loading notifications')"
        />
        <div v-else-if="!delivery.length" class="space-y-3">
          <p class="text-sm text-muted">
            {{
              failure
                ? text(
                    'Ustawienia powiadomień są niedostępne.',
                    'Notification settings are unavailable.',
                  )
                : text(
                    'Brak dostępnych powiadomień e-mail.',
                    'No email notifications are available.',
                  )
            }}
          </p>
          <Button
            v-if="failure"
            variant="outline"
            :disabled="busy"
            @click="reload()"
            >{{ text('Wczytaj ponownie', 'Reload') }}</Button
          >
        </div>
        <div v-if="optional.length" class="divide-y divide-line">
          <Form
            v-for="choice in optional"
            :key="keyOf(choice)"
            :ref="
              (value) => {
                if (value)
                  forms.set(
                    keyOf(choice),
                    value as { focusInvalid: () => void },
                  );
                else forms.delete(keyOf(choice));
              }
            "
            :label="choice.names[locale]"
            :dirty="dirty(choice)"
            :busy="busy"
            class="py-5 first:pt-0 last:pb-0"
            :data-delivery-category="choice.key"
            @submit="save(choice)"
          >
            <div class="min-w-0 font-semibold [overflow-wrap:anywhere]">
              <Switch
                v-model="drafts[keyOf(choice)]!.enabled"
                :label="choice.names[locale]"
              />
            </div>
            <Select
              v-model="drafts[keyOf(choice)]!.recipients"
              multiple
              size="sm"
              :label="text('Wyślij na', 'Send to')"
              :options="recipientOptions"
              :locale="locale"
              :disabled="!drafts[keyOf(choice)]!.enabled"
              :error="recipientErrors[keyOf(choice)] ?? ''"
              @update:model-value="delete recipientErrors[keyOf(choice)]"
            />
            <template v-if="dirty(choice)" #actions>
              <Button type="submit" size="sm" variant="outline" :busy="busy">{{
                text('Zapisz', 'Save')
              }}</Button>
              <Button size="sm" :disabled="busy" @click="revert(choice)">{{
                text('Cofnij zmiany', 'Revert changes')
              }}</Button>
              <Button
                v-if="failure && failedKey === keyOf(choice)"
                size="sm"
                :disabled="busy"
                @click="reload(keyOf(choice))"
                >{{ text('Wczytaj zapisane', 'Reload saved') }}</Button
              >
            </template>
          </Form>
        </div>
        <section
          v-if="required.length"
          class="space-y-3 border-t border-line pt-5"
          data-required-delivery
        >
          <h4 class="text-sm font-semibold">
            {{ text('Zawsze włączone', 'Always enabled') }}
          </h4>
          <p class="text-xs leading-relaxed text-muted">
            {{
              text(
                'Wymagane wiadomości bezpieczeństwa trafiają na główny adres e-mail:',
                'Required security messages go to your primary email address:',
              )
            }}
            <span class="[overflow-wrap:anywhere]">{{ profile.email }}</span>
          </p>
          <ul class="list-disc space-y-1 pl-4 text-xs text-muted">
            <li
              v-for="choice in required"
              :key="keyOf(choice)"
              :data-delivery-category="choice.key"
            >
              {{ choice.names[locale] }}
            </li>
          </ul>
        </section>
      </div>
    </Card>
  </div>
  <ConfirmDialog
    :model-value="resetOpen"
    :title="
      text('Przywrócić ustawienia domyślne?', 'Restore default settings?')
    "
    :confirm-label="text('Przywróć domyślne', 'Restore defaults')"
    confirm-tone="danger"
    :locale="locale"
    :busy="busy"
    @update:model-value="closeReset"
    @confirm="reset"
  >
    <p class="text-sm leading-7">
      {{
        text(
          'Przywrócisz domyślny język, motyw, nawigację oraz powiadomienia e-mail i ich odbiorców. Zapisane układy kolumn, filtry oraz widoki tabel zostaną usunięte.',
          'This restores the default language, theme, navigation, email notifications and their recipients. Saved column layouts, filters and table views will be removed.',
        )
      }}
    </p>
    <FormNotifications
      v-if="resetOpen && attempt"
      :attempt="attempt"
      :error="failure ? failureText : ''"
      :locale="locale"
      ><template #actions
        ><Button v-if="failure === 'csrf'" @click="account.refreshCsrf">{{
          text('Odśwież zabezpieczenie', 'Renew form security')
        }}</Button
        ><Button v-if="failure === 'conflict'" @click="reloadPage">{{
          text('Odśwież stronę', 'Reload page')
        }}</Button></template
      ></FormNotifications
    >
  </ConfirmDialog>
</template>
