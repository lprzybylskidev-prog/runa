<script setup lang="ts">
import {
  computed,
  nextTick,
  onScopeDispose,
  ref,
  shallowRef,
  watch,
} from 'vue';
import { Alert, Spinner } from '@/runa/ui';
import AppPickerSlider from '../../../resources/js/components/AppPickerSlider.vue';
const props = defineProps<{
  file: File;
  locale: 'pl' | 'en';
  maxBytes: number;
}>();
const emit = defineEmits<{ ready: [value: boolean] }>();
const text = (pl: string, en: string) => (props.locale === 'pl' ? pl : en);
const canvas = ref<HTMLCanvasElement>();
const image = shallowRef<HTMLImageElement>();
const loading = ref(false),
  failure = ref(false);
const zoom = ref(100),
  horizontal = ref(50),
  vertical = ref(50);
const controls = computed(() => [
  {
    key: 'zoom',
    label: text('Powiększenie', 'Zoom'),
    value: zoom,
    min: 100,
    max: 300,
  },
  {
    key: 'horizontal',
    label: text('Pozycja pozioma', 'Horizontal position'),
    value: horizontal,
    min: 0,
    max: 100,
  },
  {
    key: 'vertical',
    label: text('Pozycja pionowa', 'Vertical position'),
    value: vertical,
    min: 0,
    max: 100,
  },
]);
let epoch = 0,
  objectUrl = '',
  pendingImage: HTMLImageElement | undefined;
let drag:
  | { id: number; x: number; y: number; horizontal: number; vertical: number }
  | undefined;
function release() {
  if (objectUrl) URL.revokeObjectURL(objectUrl);
  objectUrl = '';
  if (pendingImage) {
    pendingImage.onload = null;
    pendingImage.onerror = null;
    pendingImage.src = '';
  }
  pendingImage = undefined;
}
function draw() {
  const source = image.value,
    target = canvas.value;
  if (!source || !target) return;
  const side =
    (Math.min(source.naturalWidth, source.naturalHeight) * 100) / zoom.value;
  const context = target.getContext('2d');
  if (!context) {
    failure.value = true;
    emit('ready', false);
    return;
  }
  context.clearRect(0, 0, 512, 512);
  context.drawImage(
    source,
    ((source.naturalWidth - side) * horizontal.value) / 100,
    ((source.naturalHeight - side) * vertical.value) / 100,
    side,
    side,
    0,
    0,
    512,
    512,
  );
}
watch(
  () => props.file,
  (file) => {
    const current = ++epoch;
    release();
    image.value = undefined;
    failure.value = false;
    loading.value = true;
    emit('ready', false);
    zoom.value = 100;
    horizontal.value = vertical.value = 50;
    if (
      file.size > 20 * 1024 * 1024 ||
      !['image/jpeg', 'image/png', 'image/webp'].includes(file.type)
    ) {
      loading.value = false;
      failure.value = true;
      return;
    }
    const source = new Image();
    pendingImage = source;
    source.onload = async () => {
      if (current !== epoch) return;
      if (
        !source.naturalWidth ||
        !source.naturalHeight ||
        source.naturalWidth * source.naturalHeight > 40_000_000 ||
        Math.max(source.naturalWidth, source.naturalHeight) > 12000
      ) {
        loading.value = false;
        failure.value = true;
        release();
        return;
      }
      image.value = source;
      loading.value = false;
      pendingImage = undefined;
      release();
      await nextTick();
      if (current !== epoch) return;
      draw();
      emit('ready', !failure.value);
    };
    source.onerror = () => {
      if (current !== epoch) return;
      loading.value = false;
      failure.value = true;
      release();
    };
    objectUrl = URL.createObjectURL(file);
    source.src = objectUrl;
  },
  { immediate: true },
);
watch([zoom, horizontal, vertical], draw);
function start(event: PointerEvent) {
  if (
    !image.value ||
    !canvas.value ||
    event.button !== 0 ||
    canvas.value.closest('fieldset:disabled, [inert]')
  )
    return;
  canvas.value.setPointerCapture(event.pointerId);
  drag = {
    id: event.pointerId,
    x: event.clientX,
    y: event.clientY,
    horizontal: horizontal.value,
    vertical: vertical.value,
  };
}
function move(event: PointerEvent) {
  if (
    !drag ||
    drag.id !== event.pointerId ||
    !canvas.value ||
    canvas.value.closest('fieldset:disabled, [inert]')
  )
    return;
  const width = canvas.value.getBoundingClientRect().width;
  horizontal.value = Math.max(
    0,
    Math.min(100, drag.horizontal - ((event.clientX - drag.x) * 100) / width),
  );
  vertical.value = Math.max(
    0,
    Math.min(100, drag.vertical - ((event.clientY - drag.y) * 100) / width),
  );
}
async function crop(): Promise<File> {
  if (!image.value || !canvas.value || loading.value || failure.value)
    throw new Error('Image is not ready.');
  const current = epoch;
  draw();
  const blob = await new Promise<Blob | null>((resolve) =>
    canvas.value!.toBlob(resolve, 'image/png'),
  );
  if (current !== epoch || !image.value) throw new Error('Image changed.');
  if (!blob || blob.size > props.maxBytes)
    throw new Error('Cropped image exceeds the upload limit.');
  return new File([blob], 'avatar.png', { type: 'image/png' });
}
defineExpose({ crop });
onScopeDispose(() => {
  epoch++;
  release();
  image.value = undefined;
  drag = undefined;
});
</script>
<template>
  <div class="space-y-4">
    <Spinner
      v-if="loading"
      :label="text('Wczytywanie zdjęcia', 'Loading image')"
    />
    <Alert v-if="failure" tone="danger">{{
      text(
        'Nie można wczytać zdjęcia. Wybierz JPEG, PNG lub WebP do 20 MB i 40 megapikseli (maks. 12000 px na bok).',
        'Cannot load image. Choose JPEG, PNG or WebP up to 20 MB and 40 megapixels (max. 12000 px per side).',
      )
    }}</Alert>
    <template v-if="image && !failure">
      <canvas
        ref="canvas"
        width="512"
        height="512"
        role="img"
        :aria-label="
          text(
            'Podgląd kadru zdjęcia profilowego',
            'Profile image crop preview',
          )
        "
        class="avatar-crop-preview"
        @pointerdown="start"
        @pointermove="move"
        @pointerup="drag = undefined"
        @pointercancel="drag = undefined"
        @lostpointercapture="drag = undefined"
      />
      <p class="text-xs text-muted">
        {{
          text(
            'Przesuń zdjęcie lub użyj suwaków, aby ustawić kadr.',
            'Drag the image or use the sliders to adjust the crop.',
          )
        }}
      </p>
      <div class="grid gap-4 sm:grid-cols-3">
        <AppPickerSlider
          v-for="control in controls"
          :key="control.key"
          v-model="control.value.value"
          :min="control.min"
          :max="control.max"
          :label="control.label"
          unit="%"
          gradient="linear-gradient(to right, var(--runa-surface-muted), var(--runa-primary-solid))"
        />
      </div>
    </template>
  </div>
</template>
<style scoped>
.avatar-crop-preview {
  display: block;
  width: min(100%, 280px);
  height: auto;
  aspect-ratio: 1;
  border: 1px solid var(--runa-control-border);
  border-radius: 4px;
  touch-action: none;
  cursor: grab;
}
.avatar-crop-preview:active {
  cursor: grabbing;
}
</style>
