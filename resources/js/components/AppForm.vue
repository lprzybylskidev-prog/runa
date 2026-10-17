<script setup lang="ts">
withDefaults(defineProps<{ busy?: boolean }>(), { busy: false });
const emit = defineEmits<{ submit: [event: SubmitEvent] }>();
function submit(event: Event) {
  if (event instanceof SubmitEvent) emit('submit', event);
}
</script>

<template>
  <form novalidate :aria-busy="busy" @submit.prevent="!busy && submit($event)">
    <slot />
  </form>
</template>
