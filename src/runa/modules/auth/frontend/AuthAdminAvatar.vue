<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { ApiError, request } from '@/runa/http';
import { Button, ConfirmDialog, FileInput, FormConflict } from '@/runa/ui';
import { useAdminLocale } from '@/runa/admin';
import type { AdminSectionPresentationProps } from '@/runa/admin';
const props = defineProps<AdminSectionPresentationProps>();
const { locale, t: tr } = useAdminLocale();
const files = ref<readonly File[]>([]);
const removing = ref(false);
const draftRevision = ref('');
const removeRevision = ref('');
const record = computed(
  () => props.controller.records.value[props.section.key],
);
const limit = computed(
  () => Number(record.value?.values.avatar_max_kib ?? 0) * 1024,
);
const conflict = computed(
  () =>
    files.value.length > 0 && draftRevision.value !== record.value?.revision,
);
watch(files, (value) => {
  if (value.length) draftRevision.value = record.value?.revision ?? '';
  props.controller.set(
    props.controller.fieldPath(props.section.key, 'avatar_upload_pending'),
    value.length > 0,
  );
});
watch(
  () =>
    props.controller.values.value[
      props.controller.fieldPath(props.section.key, 'avatar_upload_pending')
    ],
  (pending) => {
    if (pending === false && files.value.length) files.value = [];
  },
);
watch(
  () => record.value?.public_id,
  () => {
    files.value = [];
    removing.value = false;
  },
);
async function mutate(remove = false) {
  const file = files.value[0];
  if (!remove && (!file || conflict.value)) return;
  const success = await props.controller.runIndependent(
    props.section.key,
    async (snapshot, signal) => {
      if (!snapshot.public_id || props.page.state.value !== 'ready')
        throw new Error('Account unavailable.');
      const body = new FormData();
      body.set('public_id', snapshot.public_id);
      body.set('version', remove ? removeRevision.value : draftRevision.value);
      if (remove) body.set('_method', 'DELETE');
      else if (file) body.set('avatar', file);
      let response: unknown;
      try {
        response = await request('/runa/auth/users/avatar', {
          method: 'POST',
          body,
          signal,
        });
      } catch (cause) {
        if (cause instanceof ApiError && cause.status === 422)
          throw new ApiError(422, {
            errors: { avatar_upload_pending: ['Invalid input.'] },
          });
        throw cause;
      }
      if (
        !response ||
        typeof response !== 'object' ||
        !('data' in response) ||
        !response.data ||
        typeof response.data !== 'object' ||
        !('updated' in response.data) ||
        response.data.updated !== true ||
        !('version' in response.data) ||
        !Number.isSafeInteger(response.data.version) ||
        Number(response.data.version) < 1
      )
        throw new ApiError(503, {});
    },
  );
  if (success) {
    files.value = [];
    removing.value = false;
  }
}
</script>
<template>
  <div
    v-if="record && !section.readonly && record.values.avatar_max_kib"
    class="mt-4 space-y-4"
  >
    <FileInput
      v-model="files"
      :label="tr({ pl: 'Nowy avatar', en: 'New avatar' })"
      accept="image/png,image/jpeg,image/webp"
      :max-files="1"
      :max-bytes="limit"
      :locale="locale"
      :error="
        controller.errors.value[
          controller.fieldPath(section.key, 'avatar_upload_pending')
        ] ?? []
      "
      :disabled="controller.busy.value"
    />
    <FormConflict
      v-if="conflict"
      :locale="locale"
      :current="
        tr({
          pl: 'Dane konta zmieniły się po wybraniu pliku.',
          en: 'Account data changed after selecting the file.',
        })
      "
      :busy="controller.busy.value"
      @accept="files = []"
      @keep="draftRevision = record.revision"
    />
    <div class="flex flex-wrap gap-3">
      <Button
        :disabled="!files.length || !!conflict || controller.busy.value"
        @click="mutate()"
        >{{ tr({ pl: 'Zapisz avatar', en: 'Save avatar' }) }}</Button
      >
      <Button
        v-if="record.values.has_avatar"
        variant="outline"
        tone="danger"
        :disabled="controller.busy.value"
        @click="
          removeRevision = record.revision;
          removing = true;
        "
        >{{ tr({ pl: 'Usuń avatar', en: 'Remove avatar' }) }}</Button
      >
    </div>
    <ConfirmDialog
      :model-value="removing"
      :locale="locale"
      :busy="controller.busy.value"
      :title="
        tr({ pl: 'Usunąć avatar użytkownika?', en: 'Remove the user avatar?' })
      "
      @confirm="mutate(true)"
      @cancel="removing = false"
      @update:model-value="!$event && (removing = false)"
    />
  </div>
</template>
