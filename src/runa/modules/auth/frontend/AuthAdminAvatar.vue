<script setup lang="ts">
import { computed, onScopeDispose, ref, watch } from 'vue';
import { ApiError, request } from '@/runa/http';
import {
  Avatar,
  Button,
  ConfirmDialog,
  Dialog,
  FileInput,
  FormConflict,
  FormNotifications,
} from '@/runa/ui';
import { authFailure } from './entry';
import AuthAvatarCropper from './AuthAvatarCropper.vue';
import { useAdminLocale } from '@/runa/admin';
import type { AdminSectionPresentationProps } from '@/runa/admin';
const props = defineProps<AdminSectionPresentationProps>();
const { locale, t: tr } = useAdminLocale();
const originals = ref<readonly File[]>([]);
const cropOpen = ref(false);
const cropReady = ref(false);
const cropping = ref(false);
const cropError = ref('');
const preview = ref('');
const cropper = ref<InstanceType<typeof AuthAvatarCropper>>();
function clearPreview() {
  if (preview.value) URL.revokeObjectURL(preview.value);
  preview.value = '';
}
onScopeDispose(() => {
  cropOpen.value = false;
  clearPreview();
});
watch(originals, (value) => {
  cropError.value = '';
  cropReady.value = false;
  cropOpen.value = !!value.length;
  if (value.length) draftRevision.value = record.value?.revision ?? '';
  else files.value = [];
  props.controller.set(
    props.controller.fieldPath(props.section.key, 'avatar_upload_pending'),
    !!value.length,
  );
});
async function applyCrop() {
  if (!cropper.value || !cropReady.value || cropping.value) return;
  const id = record.value?.public_id;
  const source = originals.value[0];
  cropError.value = '';
  cropping.value = true;
  try {
    const file = await cropper.value.crop();
    if (
      !cropOpen.value ||
      props.section.readonly ||
      id !== record.value?.public_id ||
      source !== originals.value[0]
    )
      return;
    files.value = [file];
    clearPreview();
    preview.value = URL.createObjectURL(file);
    cropOpen.value = false;
  } catch {
    cropError.value = tr({
      pl: 'Nie udało się przygotować zdjęcia. Wybierz inny plik.',
      en: 'Could not prepare the image. Choose another file.',
    });
  } finally {
    cropping.value = false;
  }
}
function closeCrop(value: boolean) {
  if (cropping.value) return;
  cropOpen.value = value;
  if (!value && !files.value.length) originals.value = [];
}
function clearDraft() {
  originals.value = [];
  files.value = [];
  cropOpen.value = false;
  clearPreview();
}
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
    originals.value.length > 0 &&
    draftRevision.value !== record.value?.revision,
);
watch(files, (value) => {
  if (!value.length) clearPreview();
  props.controller.set(
    props.controller.fieldPath(props.section.key, 'avatar_upload_pending'),
    originals.value.length > 0,
  );
});
watch(
  () =>
    props.controller.values.value[
      props.controller.fieldPath(props.section.key, 'avatar_upload_pending')
    ],
  (pending) => {
    if (pending === false && originals.value.length) clearDraft();
  },
);
watch([() => record.value?.public_id, () => props.section.readonly], () => {
  clearDraft();
  removing.value = false;
});
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
        if (
          cause instanceof ApiError &&
          cause.status === 422 &&
          !authFailure(cause).reason
        )
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
    clearDraft();
    removing.value = false;
  }
}
</script>
<template>
  <div
    v-if="record && !section.readonly && record.values.avatar_max_kib"
    class="mt-4 space-y-4"
  >
    <div class="flex justify-center" data-admin-avatar-preview>
      <Avatar
        :key="record.public_id ?? ''"
        :src="
          preview ||
          (record.values.has_avatar
            ? `/api/runa/auth/users/avatar?public_id=${encodeURIComponent(record.public_id!)}&v=${record.revision}`
            : '')
        "
        :initials="String(record.values.avatar_initials ?? '')"
        :size="80"
      />
    </div>
    <FileInput
      v-model="originals"
      :label="
        tr({ pl: 'Obraz JPEG, PNG lub WebP', en: 'JPEG, PNG or WebP image' })
      "
      accept="image/png,image/jpeg,image/webp"
      :choose-label="tr({ pl: 'Wybierz plik', en: 'Choose file' })"
      :empty-label="tr({ pl: 'Nie wybrano pliku', en: 'No file selected' })"
      :count-message="tr({ pl: 'Wybierz jeden plik.', en: 'Choose one file.' })"
      :max-files="1"
      :max-bytes="20 * 1024 * 1024"
      :locale="locale"
      :error="
        (
          controller.errors.value[
            controller.fieldPath(section.key, 'avatar_upload_pending')
          ] ?? []
        ).map(tr)
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
      @accept="clearDraft"
      @keep="draftRevision = record.revision"
    />
    <div class="flex flex-wrap gap-3">
      <Button
        v-if="files.length"
        :disabled="controller.busy.value || cropping"
        @click="cropOpen = true"
        >{{ tr({ pl: 'Popraw kadr', en: 'Adjust crop' }) }}</Button
      >
      <Button
        v-if="originals.length"
        :disabled="controller.busy.value || cropping"
        @click="clearDraft"
        >{{ tr({ pl: 'Cofnij zmiany', en: 'Revert changes' }) }}</Button
      >
      <Button
        v-if="files.length"
        variant="outline"
        :disabled="!!conflict || controller.busy.value || cropping"
        @click="mutate()"
        >{{ tr({ pl: 'Zapisz avatar', en: 'Save avatar' }) }}</Button
      >
      <Button
        v-if="record.values.has_avatar"
        variant="outline"
        tone="danger"
        :disabled="controller.busy.value || cropping || !!originals.length"
        @click="
          removeRevision = record.revision;
          removing = true;
        "
        >{{ tr({ pl: 'Usuń avatar', en: 'Remove avatar' }) }}</Button
      >
    </div>
    <Dialog
      :model-value="cropOpen"
      :locale="locale"
      :busy="cropping"
      :title="tr({ pl: 'Kadrowanie zdjęcia', en: 'Crop image' })"
      @update:model-value="closeCrop"
    >
      <FormNotifications :locale="locale" :error="cropError" />
      <AuthAvatarCropper
        v-if="cropOpen && originals[0]"
        ref="cropper"
        :file="originals[0]"
        :locale="locale"
        :max-bytes="limit"
        @ready="cropReady = $event"
      />
      <template #footer>
        <Button :disabled="cropping" @click="closeCrop(false)">{{
          tr({ pl: 'Anuluj', en: 'Cancel' })
        }}</Button>
        <Button
          variant="outline"
          :busy="cropping"
          :disabled="!cropReady"
          @click="applyCrop"
          >{{ tr({ pl: 'Zastosuj kadr', en: 'Apply crop' }) }}</Button
        >
      </template>
    </Dialog>
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
