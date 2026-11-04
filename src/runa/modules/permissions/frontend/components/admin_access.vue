<script setup lang="ts">
import { useAdminLocale } from '@/runa/admin';
const { t: tr } = useAdminLocale();
import { ref } from 'vue';
import { Button, TextInput } from '@/runa/ui';
import { AdminPage } from '@/runa/admin';
import type { AdminPageProps } from '@/runa/admin';
const props = defineProps<AdminPageProps>();
const subject = ref<string | null>(null);
const invalid = ref(false);
const selecting = ref(false);
async function select() {
  if (selecting.value || props.form.busy.value) return;
  invalid.value =
    !!subject.value &&
    !/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i.test(
      subject.value,
    );
  if (invalid.value) return;
  selecting.value = true;
  try {
    await props.form.selectSubject(subject.value || null);
  } finally {
    selecting.value = false;
  }
}
</script>
<template>
  <div class="space-y-6">
    <div class="flex flex-wrap items-end gap-3">
      <TextInput
        v-model="subject"
        :label="tr('Account UUID (empty uses your account)')"
        :errors="invalid ? [tr('Enter a valid UUIDv7.')] : []"
        @keydown.enter.prevent="select"
      />
      <Button :busy="selecting" @click="select">{{
        tr('Select account')
      }}</Button>
    </div>
    <AdminPage v-bind="props" />
  </div>
</template>
