<script setup lang="ts">
import AppConstellation from './AppConstellation.vue';
</script>

<template>
  <div class="app-backdrop" aria-hidden="true">
    <span class="backdrop-geometry" />
    <AppConstellation variant="one" class="constellation-one" />
    <AppConstellation variant="two" class="constellation-two" />
    <AppConstellation variant="three" class="constellation-three" />
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
  opacity: var(--runa-backdrop-grid-opacity);
  background-image:
    linear-gradient(var(--runa-text-secondary) 1px, transparent 1px),
    linear-gradient(90deg, var(--runa-text-secondary) 1px, transparent 1px);
  background-size: 88px 88px;
  mask-image: radial-gradient(ellipse at 50% 42%, black, transparent 72%);
}

.constellation-one {
  top: -12%;
  right: -8%;
  width: clamp(580px, 56vw, 1100px);
  color: var(--runa-accent-text);
  opacity: 0.45;
}
.constellation-two {
  bottom: -18%;
  left: -12%;
  width: clamp(650px, 62vw, 1200px);
  opacity: 0.4;
}
.constellation-three {
  top: -22%;
  left: 16%;
  width: clamp(500px, 48vw, 950px);
  opacity: 0.22;
}
@media (max-width: 47.99rem) {
  .constellation-one {
    top: -2%;
    right: -50%;
    width: 580px;
    opacity: 0.3;
  }
  .constellation-two {
    bottom: -10%;
    left: -65%;
    width: 650px;
    opacity: 0.25;
  }
  .constellation-three {
    display: none;
  }
}
</style>
