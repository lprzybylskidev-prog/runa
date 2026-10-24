<script setup lang="ts">
import { nextTick, onScopeDispose, ref, shallowRef, watch } from 'vue';
import { Alert, Spinner } from '@/runa/ui';
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
const viewSize = 512;
const cropDiameter = 360;
function geometry() {
  const source = image.value;
  if (!source) return;
  const side =
    (Math.min(source.naturalWidth, source.naturalHeight) * 100) / zoom.value;
  return {
    side,
    x: ((source.naturalWidth - side) * horizontal.value) / 100,
    y: ((source.naturalHeight - side) * vertical.value) / 100,
  };
}
function draw() {
  const source = image.value,
    target = canvas.value,
    frame = geometry();
  if (!source || !target || !frame) return;
  const context = target.getContext('2d');
  if (!context) {
    failure.value = true;
    emit('ready', false);
    return;
  }
  const scale = cropDiameter / frame.side;
  const inset = (viewSize - cropDiameter) / 2;
  context.clearRect(0, 0, viewSize, viewSize);
  context.fillStyle = '#111321';
  context.fillRect(0, 0, viewSize, viewSize);
  context.drawImage(
    source,
    inset - frame.x * scale,
    inset - frame.y * scale,
    source.naturalWidth * scale,
    source.naturalHeight * scale,
  );
  // Only the circular avatar remains bright; the surrounding image stays visible.
  context.fillStyle = 'rgba(0, 0, 0, 0.64)';
  context.beginPath();
  context.rect(0, 0, viewSize, viewSize);
  context.arc(
    viewSize / 2,
    viewSize / 2,
    cropDiameter / 2,
    0,
    Math.PI * 2,
    true,
  );
  context.fill('evenodd');
  context.beginPath();
  context.arc(viewSize / 2, viewSize / 2, cropDiameter / 2, 0, Math.PI * 2);
  context.strokeStyle = '#ffffff';
  context.lineWidth = 2;
  context.stroke();
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
  const source = image.value,
    frame = geometry(),
    target = canvas.value;
  if (
    !drag ||
    drag.id !== event.pointerId ||
    !target ||
    !source ||
    !frame ||
    target.closest('fieldset:disabled, [inert]')
  )
    return;
  const scale =
    ((cropDiameter / frame.side) * target.getBoundingClientRect().width) /
    viewSize;
  const xRange = source.naturalWidth - frame.side,
    yRange = source.naturalHeight - frame.side;
  horizontal.value =
    xRange > 0
      ? Math.max(
          0,
          Math.min(
            100,
            drag.horizontal -
              ((event.clientX - drag.x) * 100) / (scale * xRange),
          ),
        )
      : 50;
  vertical.value =
    yRange > 0
      ? Math.max(
          0,
          Math.min(
            100,
            drag.vertical - ((event.clientY - drag.y) * 100) / (scale * yRange),
          ),
        )
      : 50;
}
function changeZoom(value: number) {
  const source = image.value,
    old = geometry();
  if (!source || !old) return;
  zoom.value = Math.max(100, Math.min(400, value));
  const next = geometry()!;
  horizontal.value =
    source.naturalWidth > next.side
      ? Math.max(
          0,
          Math.min(
            100,
            ((old.x + old.side / 2 - next.side / 2) * 100) /
              (source.naturalWidth - next.side),
          ),
        )
      : 50;
  vertical.value =
    source.naturalHeight > next.side
      ? Math.max(
          0,
          Math.min(
            100,
            ((old.y + old.side / 2 - next.side / 2) * 100) /
              (source.naturalHeight - next.side),
          ),
        )
      : 50;
}
function wheel(event: WheelEvent) {
  if (event.ctrlKey || canvas.value?.closest('fieldset:disabled, [inert]'))
    return;
  event.preventDefault();
  changeZoom(
    zoom.value * Math.exp(-Math.max(-100, Math.min(100, event.deltaY)) * 0.002),
  );
}
function key(event: KeyboardEvent) {
  if (canvas.value?.closest('fieldset:disabled, [inert]')) return;
  const step = event.shiftKey ? 10 : 2;
  if (event.key === '+' || event.key === '=') changeZoom(zoom.value * 1.1);
  else if (event.key === '-') changeZoom(zoom.value / 1.1);
  else if (event.key === 'ArrowLeft')
    horizontal.value = Math.max(0, horizontal.value - step);
  else if (event.key === 'ArrowRight')
    horizontal.value = Math.min(100, horizontal.value + step);
  else if (event.key === 'ArrowUp')
    vertical.value = Math.max(0, vertical.value - step);
  else if (event.key === 'ArrowDown')
    vertical.value = Math.min(100, vertical.value + step);
  else if (event.key === 'Home') {
    zoom.value = 100;
    horizontal.value = vertical.value = 50;
  } else return;
  event.preventDefault();
}
let pinch: { distance: number; zoom: number } | undefined;
function touch(event: TouchEvent) {
  if (
    event.touches.length !== 2 ||
    canvas.value?.closest('fieldset:disabled, [inert]')
  )
    return;
  event.preventDefault();
  drag = undefined;
  const a = event.touches[0]!,
    b = event.touches[1]!;
  const distance = Math.hypot(a.clientX - b.clientX, a.clientY - b.clientY);
  if (!pinch || event.type === 'touchstart')
    pinch = { distance, zoom: zoom.value };
  else if (pinch.distance > 0)
    changeZoom((pinch.zoom * distance) / pinch.distance);
}
async function crop(): Promise<File> {
  if (!image.value || !canvas.value || loading.value || failure.value)
    throw new Error('Image is not ready.');
  const current = epoch;
  const frame = geometry()!;
  const output = document.createElement('canvas');
  output.width = output.height = 512;
  const context = output.getContext('2d');
  if (!context) throw new Error('Image export unavailable.');
  context.drawImage(
    image.value,
    frame.x,
    frame.y,
    frame.side,
    frame.side,
    0,
    0,
    512,
    512,
  );
  const blob = await new Promise<Blob | null>((resolve) =>
    output.toBlob(resolve, 'image/png'),
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
        tabindex="0"
        :aria-description="
          text(
            'Przeciągnij zdjęcie. Kółko myszy lub klawisze plus i minus zmieniają powiększenie, strzałki przesuwają, Home resetuje kadr.',
            'Drag the image. Use the mouse wheel or plus and minus keys to zoom, arrow keys to move, Home to reset.',
          )
        "
        @wheel="wheel"
        @keydown="key"
        @touchstart="touch"
        @touchmove="touch"
        @touchend="pinch = undefined"
        @touchcancel="pinch = undefined"
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
            'Przeciągnij zdjęcie, aby ustawić kadr. Przybliżaj kółkiem myszy lub gestem dwóch palców.',
            'Drag to position the image. Zoom with the mouse wheel or a two-finger pinch.',
          )
        }}
      </p>
    </template>
  </div>
</template>
<style scoped>
.avatar-crop-preview {
  display: block;
  width: 100%;
  max-width: 512px;
  margin-inline: auto;
  height: auto;
  aspect-ratio: 1;
  border: 1px solid var(--runa-control-border);
  border-radius: 4px;
  touch-action: none;
  cursor: grab;
}
.avatar-crop-preview:focus-visible {
  outline: 2px solid var(--runa-focus-ring);
  outline-offset: 3px;
}
.avatar-crop-preview:active {
  cursor: grabbing;
}
</style>
