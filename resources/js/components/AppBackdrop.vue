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
  opacity: 0.045;
  background-image:
    linear-gradient(var(--runa-text-secondary) 1px, transparent 1px),
    linear-gradient(90deg, var(--runa-text-secondary) 1px, transparent 1px);
  background-size: 88px 88px;
  mask-image: radial-gradient(ellipse at 50% 42%, black, transparent 72%);
}

.constellation-one {
  top: 8%;
  right: 4%;
  width: 260px;
  color: var(--runa-accent-text);
}
.constellation-two {
  bottom: 5%;
  left: 4%;
  width: 300px;
}
.constellation-three {
  top: 3%;
  left: 42%;
  width: 200px;
  opacity: 0.4;
}
@media (max-width: 47.99rem) {
  .constellation-one {
    top: 12%;
    right: -6%;
    width: 180px;
    opacity: 0.4;
  }
  .constellation-two {
    left: -8%;
    bottom: 4%;
    width: 210px;
    opacity: 0.4;
  }
  .constellation-three {
    display: none;
  }
}
</style>
