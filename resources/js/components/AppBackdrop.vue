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
      ellipse 78% 85% at -5% -12%,
      color-mix(in srgb, var(--runa-circle-violet) 30%, transparent),
      color-mix(in srgb, var(--runa-gradient-primary-end) 13%, transparent) 40%,
      transparent 76%
    ),
    radial-gradient(
      ellipse 58% 64% at 8% 0%,
      color-mix(in srgb, var(--runa-circle-rose) 16%, transparent),
      transparent 73%
    ),
    radial-gradient(
      ellipse 78% 85% at 105% 112%,
      color-mix(in srgb, var(--runa-circle-cyan) 28%, transparent),
      color-mix(in srgb, var(--runa-gradient-cool-end) 12%, transparent) 42%,
      transparent 76%
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
