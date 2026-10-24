<script setup lang="ts">
import { useAccountPreferenceStatus } from '../composables/useAccountPreferenceStatus';
import { accountPreferences } from '../lib/preferenceStore';
import AppFormNotifications from './AppFormNotifications.vue';
import AppButton from './AppButton.vue';
import type { Locale } from '../locales/messages';
defineProps<{ locale: Locale }>();
const status = useAccountPreferenceStatus();
function reload() {
  const account = accountPreferences.currentAccount();
  if (account)
    void accountPreferences
      .connect(account, accountPreferences.application())
      .catch(() => {});
}
</script>
<template>
  <AppFormNotifications
    :locale="locale"
    :error="
      status.error
        ? locale === 'pl'
          ? 'Nie zapisano preferencji lub są nieaktualne. Wczytaj zapisane ustawienia i spróbuj ponownie.'
          : 'Preferences could not be saved or are out of date. Reload saved settings and try again.'
        : ''
    "
  >
    <template #actions
      ><AppButton @click="reload">{{
        locale === 'pl' ? 'Wczytaj ustawienia' : 'Reload settings'
      }}</AppButton></template
    >
  </AppFormNotifications>
</template>
