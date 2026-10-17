<script setup lang="ts">
defineProps<{
  name:
    | 'refresh'
    | 'circle-check'
    | 'alert-circle'
    | 'loader-2'
    | 'layers-intersect'
    | 'sparkles';
}>();
</script>

<template>
  <svg
    aria-hidden="true"
    focusable="false"
    width="24"
    height="24"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="1.6"
    stroke-linecap="round"
    stroke-linejoin="round"
  >
    <g v-if="name === 'refresh'">
      <path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4" />
      <path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4" />
    </g>
    <g v-if="name === 'circle-check'">
      <path d="M3 12a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" />
      <path d="M9 12l2 2l4 -4" />
    </g>
    <g v-if="name === 'alert-circle'">
      <path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0" />
      <path d="M12 8v4" />
      <path d="M12 16h.01" />
    </g>
    <g v-if="name === 'loader-2'">
      <path d="M12 3a9 9 0 1 0 9 9" />
    </g>
    <g v-if="name === 'layers-intersect'">
      <path
        d="M8 6a2 2 0 0 1 2 -2h8a2 2 0 0 1 2 2v8a2 2 0 0 1 -2 2h-8a2 2 0 0 1 -2 -2l0 -8"
      />
      <path
        d="M4 10a2 2 0 0 1 2 -2h8a2 2 0 0 1 2 2v8a2 2 0 0 1 -2 2h-8a2 2 0 0 1 -2 -2l0 -8"
      />
    </g>
    <g v-if="name === 'sparkles'">
      <path
        d="M16 18a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2m0 -12a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2m-7 12a6 6 0 0 1 6 -6a6 6 0 0 1 -6 -6a6 6 0 0 1 -6 6a6 6 0 0 1 6 6"
      />
    </g>
  </svg>
</template>
