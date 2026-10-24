<script setup lang="ts">
import { useAuthEntry } from '@/runa/auth';
import type { EntryPage, EntrySession } from '@/runa/auth';
import { Button, Form, TextInput } from '@/runa/ui';
const props = defineProps<{
  page: EntryPage;
  authenticated?: ((session: EntrySession) => void) | undefined;
}>();
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
} = useAuthEntry(props.page, props.authenticated);
</script>
<template>
  <main data-customer-entry :aria-busy="busy">
    <h1 ref="heading" tabindex="-1">{{ title }}</h1>
    <p data-screen>{{ screen }}</p>
    <p v-if="failure" role="alert">{{ failure }}</p>
    <Form v-if="ready" ref="form" :label="title" @submit="submit">
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
