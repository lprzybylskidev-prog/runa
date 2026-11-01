<script setup lang="ts">
import AppSkeleton from './AppSkeleton.vue';
import type { ContentLoadingProps } from '../runa/ui/loading';
withDefaults(defineProps<ContentLoadingProps>(), {
  label: 'Loading',
  loading: true,
  refreshing: false,
  shape: 'text',
  lines: 3,
});
</script>
<template>
  <div
    class="min-w-0"
    :class="
      !loading && refreshing ? 'runa-content-pulse content-refreshing' : ''
    "
    :aria-busy="loading || refreshing"
    :role="loading ? 'status' : refreshing ? 'region' : undefined"
    :aria-label="loading || refreshing ? label : undefined"
    :data-content-loading="loading ? shape : undefined"
  >
    <slot v-if="loading" name="placeholder">
      <div v-if="shape === 'detail'" class="space-y-5">
        <div class="grid gap-5 sm:grid-cols-2">
          <AppSkeleton v-for="index in 2" :key="index" :lines="4" />
        </div>
        <AppSkeleton shape="block" />
      </div>
      <div v-else-if="shape === 'form'" class="grid gap-5 sm:grid-cols-2">
        <div v-for="index in 4" :key="index" class="space-y-3">
          <AppSkeleton :lines="1" class="w-28" /><AppSkeleton
            shape="block"
            class="content-field"
          />
        </div>
      </div>
      <AppSkeleton
        v-else-if="shape === 'image'"
        shape="block"
        class="content-image"
      />
      <AppSkeleton
        v-else
        :lines="shape === 'navigation' ? 1 : lines"
        :class="shape === 'navigation' ? 'w-40 max-w-full py-3' : ''"
      />
    </slot>
    <template v-else>
      <slot />
    </template>
  </div>
</template>
<style scoped>
.content-field[data-shape] :deep(span) {
  height: 36px;
}
.content-image {
  max-width: 512px;
}
.content-image[data-shape] :deep(span) {
  height: auto;
  aspect-ratio: 1;
  max-height: 512px;
}
</style>
<style src="../../css/content-loading.css"></style>
