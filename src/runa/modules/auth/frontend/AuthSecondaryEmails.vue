<script setup lang="ts">
import { computed, ref, watch, useId, onScopeDispose, nextTick } from 'vue';
import {
  Badge,
  Button,
  ConfirmDialog,
  Form,
  FormNotifications,
  FormConflict,
  TextInput,
} from '@/runa/ui';
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
const titleId = useId();
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
const form = ref<{ focusInvalid: () => void }>();
const limit = computed(() =>
  Number(profile.value?.capabilities.secondary_email_limit ?? 0),
);
const canAdd = computed(
  () =>
    has('secondary_self_add') &&
    (profile.value?.secondary_emails.length ?? 0) < limit.value,
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
async function edit(address: Address) {
  if (busy.value) return;
  emailConflict.value = undefined;
  editing.value = address;
  email.value = address.email;
  Object.keys(secondaryErrors).forEach((key) => delete secondaryErrors[key]);
  await nextTick();
  document.getElementById(`${titleId}-input`)?.focus();
}
async function save() {
  if (busy.value || emailConflict.value !== undefined) return;
  props.account.form.value = form.value;
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
  <section
    v-if="profile && has('secondary_emails_enabled')"
    class="mt-6 min-w-0 space-y-4 border-t border-line pt-6"
    :aria-labelledby="titleId"
    data-secondary-emails
  >
    <div class="flex flex-wrap items-center justify-between gap-2">
      <h4 :id="titleId" class="text-sm font-semibold">
        {{ text('Dodatkowe adresy e-mail', 'Secondary email addresses') }}
      </h4>
      <span class="text-xs text-muted"
        >{{ profile.secondary_emails.length }} / {{ limit }}</span
      >
    </div>
    <ul
      v-if="profile.secondary_emails.length"
      class="divide-y divide-line"
      :aria-label="text('Dodatkowe adresy e-mail', 'Secondary email addresses')"
    >
      <li
        v-for="address in profile.secondary_emails"
        :key="address.public_id"
        class="flex min-w-0 flex-wrap items-center justify-between gap-3 py-3"
        :data-secondary-address="address.public_id"
      >
        <div class="flex min-w-0 flex-wrap items-center gap-x-3 gap-y-2">
          <p class="min-w-0 text-sm [overflow-wrap:anywhere]">
            {{ address.email }}
          </p>
          <Badge
            :tone="address.verified_at ? 'success' : 'warning'"
            size="sm"
            >{{
              address.verified_at
                ? text('Potwierdzony', 'Verified')
                : text('Oczekuje na potwierdzenie', 'Awaiting verification')
            }}</Badge
          >
        </div>
        <div class="flex flex-wrap gap-1">
          <Button
            v-if="has('secondary_self_change')"
            variant="ghost"
            size="sm"
            :disabled="busy"
            :aria-label="
              text('Edytuj adres: ', 'Edit address: ') + address.email
            "
            @click="edit(address)"
            >{{ text('Edytuj', 'Edit') }}</Button
          >
          <Button
            v-if="!address.verified_at && has('secondary_self_change')"
            variant="ghost"
            size="sm"
            :disabled="busy"
            :aria-label="
              text(
                'Wyślij ponownie potwierdzenie na: ',
                'Resend verification to: ',
              ) + address.email
            "
            @click="resend(address)"
            >{{ text('Wyślij ponownie', 'Resend') }}</Button
          >
          <Button
            v-if="has('secondary_self_remove')"
            variant="ghost"
            tone="danger"
            size="sm"
            :disabled="busy"
            :aria-label="
              text('Usuń adres: ', 'Remove address: ') + address.email
            "
            @click="removing = address"
            >{{ text('Usuń', 'Remove') }}</Button
          >
        </div>
      </li>
    </ul>
    <p v-else class="text-xs text-muted">
      {{
        text(
          'Nie dodano jeszcze dodatkowych adresów.',
          'No secondary addresses added yet.',
        )
      }}
    </p>
    <Form
      v-if="editing || canAdd"
      ref="form"
      data-account-section="secondary"
      :label="
        editing
          ? text('Edycja dodatkowego adresu', 'Edit secondary address')
          : text('Dodawanie dodatkowego adresu', 'Add secondary address')
      "
      :busy="busy"
      @submit="save"
    >
      <div class="flex min-w-0 flex-wrap items-end gap-3">
        <div class="min-w-0 flex-1 basis-56">
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
          <TextInput
            :id="`${titleId}-input`"
            :model-value="email"
            name="secondary_email"
            type="email"
            autocomplete="email"
            :label="
              editing
                ? text('Adres e-mail', 'Email address')
                : text(
                    'Nowy dodatkowy adres e-mail',
                    'New secondary email address',
                  )
            "
            required
            :error="secondaryErrors.email ?? ''"
            @update:model-value="
              email = $event ?? '';
              delete secondaryErrors.email;
            "
          />
        </div>
        <div class="flex flex-wrap gap-2">
          <Button
            type="submit"
            variant="outline"
            :busy="busy"
            :disabled="Boolean(editing && email === editing.email)"
            >{{
              editing
                ? text('Zapisz adres', 'Save address')
                : text('Dodaj adres', 'Add address')
            }}</Button
          >
          <Button v-if="editing" :disabled="busy" @click="cancelEdit">{{
            text('Anuluj', 'Cancel')
          }}</Button>
        </div>
      </div>
    </Form>
    <p v-else-if="has('secondary_self_add')" class="text-xs text-muted">
      {{
        text(
          'Osiągnięto limit dodatkowych adresów.',
          'The secondary address limit has been reached.',
        )
      }}
    </p>
  </section>
  <ConfirmDialog
    :model-value="Boolean(removing)"
    :title="text('Usunąć dodatkowy adres?', 'Remove secondary address?')"
    :confirm-label="text('Usuń adres', 'Remove address')"
    confirm-tone="danger"
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
        <Button v-if="failure === 'csrf'" @click="account.refreshCsrf">{{
          text('Odśwież zabezpieczenie', 'Renew form security')
        }}</Button>
      </template>
    </FormNotifications>
  </ConfirmDialog>
</template>
