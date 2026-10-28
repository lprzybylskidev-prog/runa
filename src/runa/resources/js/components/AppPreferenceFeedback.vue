<script setup lang="ts">
import { useAccountPreferenceStatus } from '../composables/useAccountPreferenceStatus';
import { accountPreferences } from '../../../modules/auth/frontend/preferences/session';
import AppFormNotifications from './AppFormNotifications.vue';
import AppButton from './AppButton.vue';
import type { Locale } from '../locales/messages';
defineProps<{ locale: Locale }>();
const status = useAccountPreferenceStatus();
function reload() {
  const account = accountPreferences.currentAccount();
  if (account) void accountPreferences.refresh().catch(() => {});
}
</script>
<template>
  <AppFormNotifications
    :locale="locale"
    :error="
      status.error
        ? locale === 'pl'
          ? status.error === 'conflict'
            ? 'Nie zapisano tej zmiany. Aktualne preferencje pobrano automatycznie; wybierz ustawienie ponownie.'
            : 'Nie udało się zsynchronizować preferencji. Spróbuj ponownie.'
          : status.error === 'conflict'
            ? 'This change was not saved. Current preferences were loaded automatically; choose the setting again.'
            : 'Unable to synchronize preferences. Try again.'
        : ''
    "
  >
    <template v-if="status.error !== 'conflict'" #actions
      ><AppButton @click="reload">{{
        locale === 'pl' ? 'Wczytaj ustawienia' : 'Reload settings'
      }}</AppButton></template
    >
  </AppFormNotifications>
</template>
