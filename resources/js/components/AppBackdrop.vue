<template>
  <div class="app-backdrop" aria-hidden="true">
    <span class="backdrop-geometry" />
  </div>
</template>

<style scoped>
.app-backdrop {
  position: fixed;
  inset: 0;
  z-index: -1;
  overflow: hidden;
  pointer-events: none;
  background:
    radial-gradient(
      ellipse at 8% 12%,
      color-mix(in srgb, var(--runa-circle-violet) 9%, transparent),
      transparent 62%
    ),
    radial-gradient(
      ellipse at 94% 90%,
      color-mix(in srgb, var(--runa-circle-cyan) 7%, transparent),
      transparent 60%
    ),
    radial-gradient(
      ellipse at 38% 0%,
      color-mix(in srgb, var(--runa-circle-rose) 3%, transparent),
      transparent 56%
    );
}
.backdrop-geometry {
  position: absolute;
  inset: 0;
  opacity: 0.045;
  background-image:
    linear-gradient(var(--runa-text-secondary) 1px, transparent 1px),
    linear-gradient(90deg, var(--runa-text-secondary) 1px, transparent 1px);
  background-size: 88px 88px;
  mask-image: radial-gradient(ellipse at 50% 42%, black, transparent 72%);
}
</style>
