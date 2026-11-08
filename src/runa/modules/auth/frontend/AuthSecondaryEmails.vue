<script setup lang="ts">
import { computed, ref, watch, onScopeDispose, nextTick } from 'vue';
import {
  actionIcons,
  Button,
  ConfirmDialog,
  FormNotifications,
  FormConflict,
} from '@/runa/ui';
import AuthSecondaryEmailFields from './AuthSecondaryEmailFields.vue';
import type { Profile } from './account';
import type { useAuthAccount } from './useAuthAccount';
type Address = Profile['secondary_emails'][number];
const props = defineProps<{ account: ReturnType<typeof useAuthAccount> }>();
const emit = defineEmits<{ 'feedback-modal': [active: boolean] }>();
const {
  profile,
  has,
  busy,
  locale,
  text,
  secondaryErrors,
  saveSecondary,
  failure,
  failureText,
} = props.account;
const email = ref('');
const editing = ref<Address>();
const removing = ref<Address>();
const emailConflict = ref<Address | null>();
const removalConflict = ref<Address | null>();
watch(
  () => profile.value?.secondary_emails,
  (addresses) => {
    if (!addresses) return;
    if (editing.value) {
      const current = addresses.find(
        (row) => row.public_id === editing.value!.public_id,
      );
      if (!current) emailConflict.value = null;
      else if (current.email !== editing.value.email) {
        if (email.value === editing.value.email) {
          email.value = current.email;
          editing.value = current;
        } else if (email.value !== current.email) emailConflict.value = current;
        else {
          editing.value = current;
          emailConflict.value = undefined;
        }
      }
    }
    if (removing.value) {
      const current = addresses.find(
        (row) => row.public_id === removing.value!.public_id,
      );
      if (!current || current.email !== removing.value.email)
        removalConflict.value = current ?? null;
    }
  },
);
function resolveEmail(keep = false) {
  if (busy.value) return;
  const current = emailConflict.value;
  if (!current) cancelEdit();
  else {
    editing.value = current;
    if (!keep) email.value = current.email;
  }
  emailConflict.value = undefined;
  if (failure.value === 'conflict') failure.value = '';
}
const removalAttempt = ref(0);
const form = ref<{ focusInvalid: () => void; focusEmail: () => void }>();
const limit = computed(() =>
  Number(profile.value?.capabilities.secondary_email_limit ?? 0),
);
watch(removing, (value) => {
  if (value) removalAttempt.value = 0;
  else removalConflict.value = undefined;
  emit('feedback-modal', Boolean(value));
});
onScopeDispose(() => emit('feedback-modal', false));
function cancelEdit() {
  editing.value = undefined;
  emailConflict.value = undefined;
  email.value = '';
  Object.keys(secondaryErrors).forEach((key) => delete secondaryErrors[key]);
}
watch(
  () => [
    has('secondary_emails_enabled'),
    has('secondary_self_change'),
    has('secondary_self_remove'),
  ],
  () => {
    if (
      !has('secondary_emails_enabled') ||
      (editing.value && !has('secondary_self_change'))
    )
      cancelEdit();
    if (!has('secondary_emails_enabled') || !has('secondary_self_remove'))
      removing.value = undefined;
  },
);
async function edit(address: Address) {
  if (busy.value) return;
  emailConflict.value = undefined;
  editing.value = address;
  email.value = address.email;
  Object.keys(secondaryErrors).forEach((key) => delete secondaryErrors[key]);
  await nextTick();
  form.value?.focusEmail();
}
async function save() {
  if (busy.value || emailConflict.value !== undefined) return;
  const activeForm = props.account.form;
  activeForm.value = form.value;
  const saved = await saveSecondary(
    editing.value ? 'secondaryChange' : 'secondaryAdd',
    {
      email: email.value,
      ...(editing.value
        ? {
            address_id: editing.value.public_id,
            baselineEmail: editing.value.email,
          }
        : {}),
    },
  );
  if (saved) cancelEdit();
  else {
    await nextTick();
    form.value?.focusInvalid();
  }
}
async function resend(address: Address) {
  if (busy.value) return;
  await saveSecondary('secondaryChange', {
    email: address.email,
    address_id: address.public_id,
    baselineEmail: address.email,
  });
}
async function remove() {
  if (!removing.value || busy.value || removalConflict.value !== undefined)
    return;
  removalAttempt.value++;
  const address = removing.value;
  if (
    await saveSecondary('secondaryRemove', {
      address_id: address.public_id,
      baselineEmail: address.email,
      confirmed: true,
    })
  ) {
    if (editing.value?.public_id === address.public_id) cancelEdit();
    removing.value = undefined;
  }
}
</script>
<template>
  <AuthSecondaryEmailFields
    v-if="profile && has('secondary_emails_enabled')"
    ref="form"
    class="mt-6 border-t border-line pt-6"
    :locale="locale"
    :addresses="
      profile.secondary_emails.map((row) => ({
        public_id: row.public_id,
        email: row.email,
        verified: Boolean(row.verified_at),
      }))
    "
    :limit="limit"
    :email="email"
    :editing="editing?.public_id ?? null"
    :busy="busy"
    :can-add="has('secondary_self_add')"
    :can-change="has('secondary_self_change')"
    :can-remove="has('secondary_self_remove')"
    :error="secondaryErrors.email ?? ''"
    :conflicted="emailConflict !== undefined"
    @update:email="
      email = $event;
      delete secondaryErrors.email;
    "
    @edit="
      edit(profile!.secondary_emails.find((row) => row.public_id === $event)!)
    "
    @resend="
      resend(profile!.secondary_emails.find((row) => row.public_id === $event)!)
    "
    @remove="
      removing = profile!.secondary_emails.find(
        (row) => row.public_id === $event,
      )
    "
    @submit="save"
    @cancel="cancelEdit"
  >
    <template #before-field>
      <FormConflict
        v-if="emailConflict !== undefined"
        :current="
          emailConflict?.email ??
          text('Adres został usunięty.', 'The address was removed.')
        "
        :allow-keep="Boolean(emailConflict)"
        :locale="locale"
        :busy="busy"
        @accept="resolveEmail()"
        @keep="resolveEmail(true)"
      />
    </template>
  </AuthSecondaryEmailFields>
  <ConfirmDialog
    :model-value="Boolean(removing)"
    :title="text('Usunąć dodatkowy adres?', 'Remove secondary address?')"
    :confirm-label="text('Usuń adres', 'Remove address')"
    confirm-intent="danger"
    confirm-icon="trash"
    :locale="locale"
    :busy="busy"
    @update:model-value="!$event && (removing = undefined)"
    @confirm="remove"
  >
    <FormConflict
      v-if="removalConflict !== undefined"
      :current="
        removalConflict?.email ??
        text('Adres został usunięty.', 'The address was removed.')
      "
      :allow-keep="false"
      :locale="locale"
      :busy="busy"
      @accept="
        removing = removalConflict ?? undefined;
        removalConflict = undefined;
      "
    />
    <p class="text-sm leading-7 [overflow-wrap:anywhere]">
      {{
        text(
          'Czy na pewno chcesz usunąć adres ',
          'Are you sure you want to remove ',
        )
      }}<strong>{{ removing?.email }}</strong
      >?
    </p>
    <FormNotifications
      v-if="removing && removalAttempt"
      :attempt="removalAttempt"
      :error="failure ? failureText : ''"
      :invalid="Object.keys(secondaryErrors).length > 0"
      :locale="locale"
    >
      <template #actions>
        <Button
          v-if="failure === 'csrf'"
          :icon="actionIcons.refresh"
          intent="inspect"
          :busy="busy"
          @click="account.refreshCsrf"
          >{{ text('Odśwież zabezpieczenie', 'Renew form security') }}</Button
        >
      </template>
    </FormNotifications>
  </ConfirmDialog>
</template>
