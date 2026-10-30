<script setup lang="ts">
import { ref } from 'vue';
import { Alert, Button, TextInput } from '@/runa/ui';
import { AdminPage } from '@/runa/admin';
import type { AdminPageProps } from '@/runa/admin';
const props = defineProps<AdminPageProps>();
const subject = ref<string | null>(null);
const invalid = ref(false);
async function select() {
  invalid.value =
    !!subject.value &&
    !/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i.test(
      subject.value,
    );
  if (!invalid.value) await props.form.selectSubject(subject.value || null);
}
</script>
<template>
  <div class="space-y-6">
    <div class="flex flex-wrap items-end gap-3">
      <TextInput
        v-model="subject"
        label="Account UUID (empty uses your account)"
        :errors="invalid ? ['Enter a valid UUIDv7.'] : []"
        @keydown.enter.prevent="select"
      />
      <Button :disabled="form.busy.value" @click="select"
        >Select account</Button
      >
    </div>
    <Alert tone="info"
      >Ordinary assignments, copy and presets preserve protected membership.
      Each operation requires its own review and fresh both-factor proof.
      Protected authority uses a separate action and confirmation.</Alert
    >
    <AdminPage v-bind="props" />
  </div>
</template>
