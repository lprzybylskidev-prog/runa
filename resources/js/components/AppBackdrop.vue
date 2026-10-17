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
.app-backdrop::before,
.app-backdrop::after {
  content: '';
  position: absolute;
  pointer-events: none;
  width: clamp(680px, 100vw, 1500px);
  aspect-ratio: 1;
  border-radius: 50%;
  /* Broad off-screen arcs with gently tapered edges, not floating circles. */
  mask-image: radial-gradient(
    closest-side,
    transparent 67%,
    black 70%,
    black 77%,
    transparent 81%
  );
}
.app-backdrop::before {
  top: 0;
  left: 0;
  transform: translate(-65%, -66%);
  background: conic-gradient(
    from 25deg,
    transparent,
    color-mix(
        in srgb,
        var(--runa-gradient-primary-end)
          calc(var(--runa-circle-violet-opacity) * 110%),
        transparent
      )
      15%,
    color-mix(
        in srgb,
        var(--runa-circle-rose) calc(var(--runa-circle-rose-opacity) * 120%),
        transparent
      )
      30%,
    transparent 48%,
    transparent
  );
}
.app-backdrop::after {
  right: 0;
  bottom: 0;
  transform: translate(58%, 62%);
  background: conic-gradient(
    from 175deg,
    transparent,
    color-mix(
        in srgb,
        var(--runa-circle-cyan) calc(var(--runa-circle-cyan-opacity) * 120%),
        transparent
      )
      16%,
    color-mix(
        in srgb,
        var(--runa-gradient-cool-end)
          calc(var(--runa-circle-cyan-opacity) * 100%),
        transparent
      )
      31%,
    transparent 48%,
    transparent
  );
}
</style>
