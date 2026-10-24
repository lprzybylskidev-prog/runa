<script setup lang="ts">
import { computed } from 'vue';
import { Button, RadioGroup, TextInput } from '@/runa/ui';
import type { AccountSection, useAuthAccount } from './useAuthAccount';
const props = defineProps<{
  account: ReturnType<typeof useAuthAccount>;
  section: AccountSection;
}>();
const {
  text,
  method,
  proofMethods,
  security,
  sectionFields,
  sectionErrors,
  sendCode,
} = props.account;
const needsProof = computed(
  () =>
    proofMethods.value.length > 0 &&
    (props.section === 'security' ||
      (props.section === 'password' &&
        security.value?.password_change_factor === true)),
);
</script>
<template>
  <TextInput
    :model-value="sectionFields[section].current_password ?? ''"
    name="current_password"
    type="password"
    autocomplete="current-password"
    :label="text('Aktualne hasło', 'Current password')"
    required
    :error="sectionErrors[section].current_password ?? ''"
    @update:model-value="
      sectionFields[section].current_password = $event ?? '';
      delete sectionErrors[section].current_password;
    "
  />
  <template v-if="needsProof">
    <RadioGroup
      v-model="method"
      :label="
        text('Potwierdź obecną metodą', 'Confirm with an existing method')
      "
      :options="
        proofMethods.map((value) => ({
          value,
          label:
            value === 'totp'
              ? text('Aplikacja', 'Authenticator')
              : value === 'email'
                ? 'E-mail'
                : text('Kod odzyskiwania', 'Recovery code'),
        }))
      "
    />
    <TextInput
      :model-value="sectionFields[section].code ?? ''"
      name="code"
      autocomplete="one-time-code"
      :label="text('Kod weryfikacyjny', 'Verification code')"
      required
      :error="sectionErrors[section].code ?? ''"
      @update:model-value="
        sectionFields[section].code = $event ?? '';
        delete sectionErrors[section].code;
      "
    />
    <Button v-if="method === 'email'" @click="sendCode">{{
      text('Wyślij kod', 'Send code')
    }}</Button>
  </template>
</template>
