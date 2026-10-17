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
      ellipse at 48% 40%,
      color-mix(in srgb, var(--runa-circle-violet) 10%, transparent),
      transparent 62%
    ),
    radial-gradient(
      ellipse at 62% 62%,
      color-mix(in srgb, var(--runa-circle-cyan) 7%, transparent),
      transparent 60%
    ),
    radial-gradient(
      ellipse at 52% 0%,
      color-mix(in srgb, var(--runa-circle-rose) 5%, transparent),
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
.app-backdrop::before,
.app-backdrop::after {
  content: '';
  position: fixed;
  z-index: -1;
  pointer-events: none;
  border-radius: 50%;
}
.app-backdrop::before {
  width: min(85vw, 1000px);
  aspect-ratio: 1;
  top: -40%;
  left: -20%;
  background: radial-gradient(
    circle,
    color-mix(
        in srgb,
        var(--runa-circle-violet) calc(var(--runa-circle-violet-opacity) * 100%),
        transparent
      )
      20%,
    transparent 72%
  );
  border: clamp(35px, 7vw, 110px) solid
    color-mix(
      in srgb,
      var(--runa-circle-rose) calc(var(--runa-circle-rose-opacity) * 100%),
      transparent
    );
}
.app-backdrop::after {
  width: min(75vw, 850px);
  aspect-ratio: 1;
  right: -20%;
  bottom: -35%;
  background: radial-gradient(
    circle,
    color-mix(
      in srgb,
      var(--runa-circle-cyan) calc(var(--runa-circle-cyan-opacity) * 100%),
      transparent
    ),
    transparent 75%
  );
  border: clamp(30px, 6vw, 95px) solid
    color-mix(
      in srgb,
      var(--runa-circle-cyan) calc(var(--runa-circle-cyan-opacity) * 100%),
      transparent
    );
}
</style>
