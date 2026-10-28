<script setup lang="ts">
import { useAuthEntry } from '@/runa/auth';
import type { AuthEntryProps, AuthEntryController } from '@/runa/auth';
import type { ComponentPublicInstance } from 'vue';
import { Button, Form, TextInput } from '@/runa/ui';
const props = defineProps<AuthEntryProps>();
// An isolated consumer of the public headless API, with customer-owned markup.
// This fixture covers login/error/restriction transitions, not a complete product UI.
const {
  ready,
  screen,
  title,
  values,
  errors,
  busy,
  failure,
  fieldNames,
  label,
  submit,
  form,
  heading,
  refreshCsrf,
}: AuthEntryController = useAuthEntry(props.page, props.authenticated);
function bindHeading(
  value: globalThis.Element | ComponentPublicInstance | null,
) {
  heading.value =
    value instanceof globalThis.HTMLHeadingElement ? value : undefined;
}
function bindForm(value: globalThis.Element | ComponentPublicInstance | null) {
  form.value = (value as InstanceType<typeof Form> | null) ?? undefined;
}
</script>
<template>
  <main data-customer-entry :aria-busy="busy">
    <h1 :ref="bindHeading" tabindex="-1">
      {{ title }}
    </h1>
    <p data-screen>{{ screen }}</p>
    <p v-if="failure" role="alert">{{ failure }}</p>
    <Form v-if="ready" :ref="bindForm" :label="title" @submit="submit">
      <TextInput
        v-for="name in fieldNames"
        :key="name"
        :name="name"
        :label="label(name)"
        :type="name.includes('password') ? 'password' : 'text'"
        :model-value="values[name] ?? ''"
        :error="errors[name] ?? ''"
        @update:model-value="values[name] = $event ?? ''"
      />
      <Button type="submit" :busy="busy">Submit</Button>
      <Button v-if="failure === 'csrf'" @click="refreshCsrf"
        >Refresh CSRF</Button
      >
    </Form>
  </main>
</template>
