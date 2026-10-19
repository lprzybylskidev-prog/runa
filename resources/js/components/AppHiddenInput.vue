<script setup lang="ts">
defineProps<{ modelValue: string | null; name: string; disabled?: boolean }>();
</script>
<template>
  <input
    type="hidden"
    :name="name"
    :value="modelValue ?? ''"
    :disabled="disabled"
  />
</template>
