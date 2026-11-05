<script setup lang="ts">
import { computed, ref, useId, watch } from 'vue';
import { Badge, Button, Form, TextInput } from '@/runa/ui';
import type { AuthSecondaryEmailFieldsProps } from './contracts/fields';
const props = defineProps<AuthSecondaryEmailFieldsProps>();
const emit = defineEmits<{
  'update:email': [value: string];
  edit: [publicId: string];
  resend: [publicId: string];
  verify: [publicId: string];
  remove: [publicId: string];
  submit: [];
  cancel: [];
}>();
const titleId = useId();
const text = (pl: string, en: string) => (props.locale === 'pl' ? pl : en);
const form = ref<{ focusInvalid: () => void }>();
const attempted = ref(false);
const mayAdd = computed(
  () => props.canAdd && props.addresses.length < props.limit,
);
const originalEmail = computed(
  () => props.addresses.find((row) => row.public_id === props.editing)?.email,
);
const issue = computed(() =>
  !props.email.trim()
    ? text('Wypełnij to pole.', 'Complete this field.')
    : !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(props.email) ||
        props.email.length > 254 ||
        /[^\x21-\x7e]/.test(props.email)
      ? text('Podaj poprawny adres e-mail.', 'Enter a valid email address.')
      : '',
);
watch(
  () => props.editing,
  () => {
    attempted.value = false;
  },
);
defineExpose({
  focusInvalid: () => form.value?.focusInvalid(),
  focusEmail: () => document.getElementById(`${titleId}-input`)?.focus(),
});
</script>
<template>
  <section
    class="min-w-0 space-y-4"
    :aria-labelledby="titleId"
    data-secondary-emails
  >
    <div class="flex flex-wrap items-center justify-between gap-2">
      <h4 :id="titleId" class="text-sm font-semibold">
        {{ text('Dodatkowe adresy e-mail', 'Secondary email addresses') }}
      </h4>
      <span class="text-xs text-muted"
        >{{ addresses.length }} / {{ limit }}</span
      >
    </div>
    <ul
      v-if="addresses.length"
      class="divide-y divide-line"
      :aria-label="text('Dodatkowe adresy e-mail', 'Secondary email addresses')"
    >
      <li
        v-for="address in addresses"
        :key="address.public_id"
        class="flex min-w-0 flex-wrap items-center justify-between gap-3 py-3"
        :data-secondary-address="address.public_id"
      >
        <div class="flex min-w-0 flex-wrap items-center gap-x-3 gap-y-2">
          <p class="min-w-0 text-sm [overflow-wrap:anywhere]">
            {{ address.email }}
          </p>
          <Badge :tone="address.verified ? 'success' : 'warning'" size="sm">{{
            address.verified
              ? text('Potwierdzony', 'Verified')
              : text('Oczekuje na potwierdzenie', 'Awaiting verification')
          }}</Badge>
        </div>
        <div class="flex flex-wrap gap-1">
          <Button
            v-if="canChange"
            intent="inspect"
            variant="ghost"
            size="sm"
            :disabled="busy"
            :aria-label="
              text('Edytuj adres: ', 'Edit address: ') + address.email
            "
            @click="emit('edit', address.public_id)"
            >{{ text('Edytuj', 'Edit') }}</Button
          >
          <Button
            v-if="!address.verified && canChange"
            intent="inspect"
            :busy="busy"
            variant="ghost"
            size="sm"
            :disabled="busy"
            :aria-label="
              text(
                'Wyślij ponownie potwierdzenie na: ',
                'Resend verification to: ',
              ) + address.email
            "
            @click="emit('resend', address.public_id)"
            >{{ text('Wyślij ponownie', 'Resend') }}</Button
          >
          <Button
            v-if="!address.verified && canVerify"
            intent="commit"
            variant="ghost"
            size="sm"
            :disabled="busy"
            :aria-label="
              text(
                'Potwierdź jako administrator: ',
                'Verify as administrator: ',
              ) + address.email
            "
            @click="emit('verify', address.public_id)"
            >{{ text('Potwierdź', 'Verify') }}</Button
          >
          <Button
            v-if="canRemove"
            intent="danger"
            variant="ghost"
            size="sm"
            :disabled="busy"
            :aria-label="
              text('Usuń adres: ', 'Remove address: ') + address.email
            "
            @click="emit('remove', address.public_id)"
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
      v-if="editing ? canChange : mayAdd"
      ref="form"
      data-account-section="secondary"
      :label="
        editing
          ? text('Edycja dodatkowego adresu', 'Edit secondary address')
          : text('Dodawanie dodatkowego adresu', 'Add secondary address')
      "
      :busy="busy"
      :valid="!issue && !conflicted"
      @invalid="attempted = true"
      @submit="emit('submit')"
    >
      <div class="flex min-w-0 flex-wrap items-end gap-3">
        <div class="min-w-0 flex-1 basis-56">
          <slot name="before-field" />
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
            :error="[
              ...(attempted && issue ? [issue] : []),
              ...(typeof error === 'string'
                ? error
                  ? [error]
                  : []
                : (error ?? [])),
            ]"
            :maxlength="254"
            @update:model-value="emit('update:email', $event ?? '')"
          />
        </div>
        <div class="flex flex-wrap gap-2">
          <Button
            intent="commit"
            type="submit"
            :busy="busy"
            :disabled="Boolean(editing && email === originalEmail)"
            >{{
              editing
                ? text('Zapisz adres', 'Save address')
                : text('Dodaj adres', 'Add address')
            }}</Button
          >
          <Button
            v-if="editing || email"
            intent="cancel"
            :disabled="busy"
            @click="emit('cancel')"
            >{{ text('Anuluj', 'Cancel') }}</Button
          >
        </div>
      </div>
    </Form>
    <p v-else-if="canAdd" class="text-xs text-muted">
      {{
        text(
          'Osiągnięto limit dodatkowych adresów.',
          'The secondary address limit has been reached.',
        )
      }}
    </p>
  </section>
</template>
