<script setup lang="ts">
defineProps<{ label?: string }>();
</script>
<template>
  <div v-if="label" class="flex items-center gap-3 text-xs text-muted">
    <span class="grow border-t border-line" />{{ label
    }}<span class="grow border-t border-line" />
  </div>
  <hr v-else class="border-0 border-t border-line" />
</template>
