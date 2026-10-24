<script setup lang="ts">
import { computed, ref, watch, onScopeDispose, nextTick } from 'vue';
import {
  Badge,
  Button,
  Card,
  Checkbox,
  ConfirmDialog,
  Dialog,
  Form,
  FormNotifications,
  Spinner,
  Switch,
} from '@/runa/ui';
import AppPreferenceControls from '../../../resources/js/components/AppPreferenceControls.vue';
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
const editing = ref<Choice>();
const resetOpen = ref(false);
const attempt = ref(0);
const recipientError = ref('');
const form = ref<{ focusInvalid: () => void }>();
const eligibleAddresses = computed(
  () =>
    profile.value?.secondary_emails.filter((address) => address.verified_at) ??
    [],
);
const optional = computed(() =>
  delivery.value.filter((choice) => !choice.mandatory && !choice.sensitive),
);
const required = computed(() =>
  delivery.value.filter((choice) => choice.mandatory || choice.sensitive),
);
watch(
  () => Boolean(editing.value) || resetOpen.value,
  (value) => emit('feedback-modal', value),
);
onScopeDispose(() => {
  confirmed.value = false;
  emit('feedback-modal', false);
});
function recipientNames(choice: Choice) {
  return choice.recipients.flatMap((id) => {
    if (id === 'primary') return profile.value ? [profile.value.email] : [];
    const address = eligibleAddresses.value.find(
      (item) => item.public_id === id,
    );
    return address ? [address.email] : [];
  });
}
function edit(choice: Choice) {
  if (busy.value || choice.mandatory || choice.sensitive) return;
  editing.value = {
    ...choice,
    names: { ...choice.names },
    recipients: [...choice.recipients],
  };
  attempt.value = 0;
  recipientError.value = '';
  failure.value = '';
  notice.value = '';
}
function setRecipient(id: string, enabled: boolean) {
  if (!editing.value) return;
  editing.value = {
    ...editing.value,
    recipients: enabled
      ? [...new Set([...editing.value.recipients, id])]
      : editing.value.recipients.filter((value) => value !== id),
  };
  recipientError.value = '';
}
function close(value: boolean) {
  if (value || busy.value) return;
  editing.value = undefined;
  recipientError.value = '';
  failure.value = '';
  notice.value = '';
}
async function save() {
  if (busy.value || !editing.value) return;
  attempt.value++;
  if (!editing.value.recipients.length) {
    recipientError.value = text(
      'Wybierz co najmniej jeden adres e-mail.',
      'Select at least one email address.',
    );
    await nextTick();
    form.value?.focusInvalid();
    return;
  }
  props.account.form.value = form.value;
  await saveDelivery(editing.value);
  if (!failure.value && notice.value) editing.value = undefined;
}
async function reload() {
  if (busy.value) return;
  const selected = editing.value;
  await selectTab('preferences');
  if (failure.value || !selected) return;
  const current = delivery.value.find(
    (choice) =>
      choice.module === selected.module && choice.key === selected.key,
  );
  if (current && !current.mandatory && !current.sensitive) edit(current);
  else editing.value = undefined;
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
  await resetPreferences();
  if (!failure.value && notice.value) resetOpen.value = false;
  confirmed.value = false;
}
</script>
<template>
  <div
    v-if="profile"
    class="grid min-w-0 items-start gap-6 xl:grid-cols-2"
    data-account-preferences
  >
    <div class="min-w-0 space-y-6">
      <Card data-preferences-display>
        <template #header
          ><h3>
            {{ text('Wygląd i język', 'Appearance and language') }}
          </h3></template
        >
        <div class="space-y-5">
          <div class="flex flex-wrap items-start justify-between gap-4">
            <dl class="grid min-w-0 gap-x-8 gap-y-4 sm:grid-cols-2">
              <div class="space-y-1">
                <dt class="text-xs text-muted">
                  {{ text('Język', 'Language') }}
                </dt>
                <dd class="text-sm font-semibold">
                  {{ locale === 'pl' ? 'Polski' : 'English' }}
                </dd>
              </div>
              <div class="space-y-1">
                <dt class="text-xs text-muted">{{ text('Motyw', 'Theme') }}</dt>
                <dd class="text-sm font-semibold">
                  {{ display.t(display.theme.value) }}
                </dd>
              </div>
            </dl>
            <fieldset :disabled="busy" class="min-w-0">
              <AppPreferenceControls
                :locale="display.locale.value"
                :theme="display.theme.value"
                :theme-label="display.themeLabel.value"
                :language-label="display.languageLabel.value"
                :t="display.t"
                @change="display.changePreference"
              />
            </fieldset>
          </div>
          <p class="text-xs leading-relaxed text-muted">
            {{
              text(
                'Zmiana języka lub motywu zapisuje się automatycznie na Twoim koncie.',
                'Language and theme changes are saved to your account automatically.',
              )
            }}
          </p>
        </div>
      </Card>
      <Card data-preferences-reset>
        <template #header
          ><h3>
            {{ text('Ustawienia domyślne', 'Default settings') }}
          </h3></template
        >
        <div class="space-y-5">
          <p class="text-sm leading-relaxed text-muted">
            {{
              text(
                'Przywróć domyślne ustawienia wyglądu, nawigacji i tabel.',
                'Restore default appearance, navigation and table settings.',
              )
            }}
          </p>
          <Button
            variant="outline"
            tone="danger"
            :disabled="busy"
            @click="beginReset"
            >{{ text('Przywróć domyślne', 'Restore defaults') }}</Button
          >
        </div>
      </Card>
    </div>
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
              'Wybierz wiadomości, które chcesz otrzymywać, i adresy ich doręczenia.',
              'Choose which emails to receive and where they should be delivered.',
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
            @click="reload"
            >{{ text('Wczytaj ponownie', 'Reload') }}</Button
          >
        </div>
        <div v-if="optional.length" class="divide-y divide-line">
          <div
            v-for="choice in optional"
            :key="choice.module + ':' + choice.key"
            class="space-y-3 py-4 first:pt-0 last:pb-0"
            :data-delivery-category="choice.key"
          >
            <div class="flex flex-wrap items-center justify-between gap-3">
              <div class="min-w-0 space-y-2">
                <h4 class="text-sm font-semibold [overflow-wrap:anywhere]">
                  {{ choice.names[locale] }}
                </h4>
                <Badge
                  size="sm"
                  :tone="choice.enabled ? 'success' : undefined"
                  >{{
                    choice.enabled
                      ? text('Włączone', 'Enabled')
                      : text('Wyłączone', 'Disabled')
                  }}</Badge
                >
              </div>
              <Button
                size="sm"
                variant="outline"
                :aria-label="text('Zmień: ', 'Edit: ') + choice.names[locale]"
                :disabled="busy"
                @click="edit(choice)"
                >{{ text('Zmień', 'Edit') }}</Button
              >
            </div>
            <ul
              v-if="choice.enabled"
              class="space-y-1 text-xs text-muted"
              :aria-label="text('Adresy odbiorców', 'Recipient addresses')"
            >
              <li
                v-for="address in recipientNames(choice)"
                :key="address"
                class="[overflow-wrap:anywhere]"
              >
                {{ address }}
              </li>
            </ul>
          </div>
        </div>
        <section
          v-if="required.length"
          class="space-y-4"
          :class="optional.length ? 'border-t border-line pt-5' : ''"
        >
          <h4 class="text-xs font-semibold text-muted">
            {{ text('Wiadomości wymagane', 'Required messages') }}
          </h4>
          <p class="text-xs leading-relaxed text-muted">
            {{
              text(
                'Wymagane wiadomości bezpieczeństwa trafiają na główny adres e-mail.',
                'Required security messages go to your primary email address.',
              )
            }}
          </p>
          <div
            v-for="choice in required"
            :key="choice.module + ':' + choice.key"
            class="flex min-w-0 flex-wrap items-center justify-between gap-2"
            :data-delivery-category="choice.key"
          >
            <span class="text-sm [overflow-wrap:anywhere]">{{
              choice.names[locale]
            }}</span
            ><Badge size="sm">{{ text('Obowiązkowe', 'Required') }}</Badge>
          </div>
        </section>
      </div>
    </Card>
  </div>
  <Dialog
    :model-value="Boolean(editing)"
    :title="
      editing?.names[locale] ??
      text('Powiadomienia e-mail', 'Email notifications')
    "
    :locale="locale"
    :busy="busy"
    @update:model-value="close"
  >
    <Form
      v-if="editing"
      ref="form"
      :label="editing.names[locale]"
      :busy="busy"
      data-delivery-editor
      @submit="save"
    >
      <Switch
        v-model="editing.enabled"
        :label="
          text('Otrzymuj te powiadomienia', 'Receive these notifications')
        "
      />
      <fieldset class="min-w-0 space-y-3 [overflow-wrap:anywhere]">
        <legend class="mb-3 text-sm font-semibold">
          {{ text('Adresy odbiorców', 'Recipient addresses') }}
        </legend>
        <Checkbox
          :model-value="editing.recipients.includes('primary')"
          :label="profile?.email ?? ''"
          :helper="text('Główny adres e-mail', 'Primary email address')"
          :error="recipientError"
          @update:model-value="setRecipient('primary', $event)"
        />
        <Checkbox
          v-for="address in eligibleAddresses"
          :key="address.public_id"
          :model-value="editing.recipients.includes(address.public_id)"
          :label="address.email"
          @update:model-value="setRecipient(address.public_id, $event)"
        />
      </fieldset>
      <p class="text-xs leading-relaxed text-muted">
        {{
          text(
            'Dodatkowe adresy dodasz i potwierdzisz w danych konta.',
            'Add and verify secondary addresses in Account details.',
          )
        }}
      </p>
      <template #actions
        ><Button type="submit" variant="outline" :busy="busy">{{
          text('Zapisz ustawienia', 'Save settings')
        }}</Button
        ><Button :disabled="busy" @click="close(false)">{{
          text('Anuluj', 'Cancel')
        }}</Button></template
      >
    </Form>
    <FormNotifications
      v-if="editing"
      :attempt="attempt"
      :error="failure ? failureText : ''"
      :invalid="Boolean(recipientError)"
      :locale="locale"
    >
      <template #actions
        ><Button v-if="failure === 'csrf'" @click="account.refreshCsrf">{{
          text('Odśwież zabezpieczenie', 'Renew form security')
        }}</Button
        ><Button v-if="failure === 'conflict'" @click="reload">{{
          text('Wczytaj aktualne dane', 'Reload current values')
        }}</Button></template
      >
    </FormNotifications>
  </Dialog>
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
          'Przywrócisz domyślny język, motyw i ustawienia nawigacji. Zapisane układy kolumn, filtry oraz widoki tabel zostaną usunięte.',
          'This restores the default language, theme and navigation settings. Saved column layouts, filters and table views will be removed.',
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
