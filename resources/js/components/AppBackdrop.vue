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
  position: absolute;
  pointer-events: none;
  border-radius: 50%;
  aspect-ratio: 1;
  /* Feather both edges of the colored ribbon; keep the center transparent. */
  mask-image: radial-gradient(
    closest-side,
    transparent 71%,
    black 74%,
    black 96%,
    transparent 100%
  );
}
.app-backdrop::before {
  width: min(85vw, 1000px);
  top: -40%;
  left: -20%;
  background: conic-gradient(
    from 30deg,
    color-mix(
      in srgb,
      var(--runa-circle-violet) calc(var(--runa-circle-violet-opacity) * 260%),
      transparent
    ),
    color-mix(
        in srgb,
        var(--runa-gradient-primary-end)
          calc(var(--runa-circle-violet-opacity) * 120%),
        transparent
      )
      27%,
    color-mix(
        in srgb,
        var(--runa-circle-rose) calc(var(--runa-circle-rose-opacity) * 300%),
        transparent
      )
      49%,
    color-mix(
        in srgb,
        var(--runa-gradient-primary-start)
          calc(var(--runa-circle-violet-opacity) * 240%),
        transparent
      )
      66%,
    color-mix(
        in srgb,
        var(--runa-circle-violet) calc(var(--runa-circle-violet-opacity) * 80%),
        transparent
      )
      84%,
    color-mix(
      in srgb,
      var(--runa-circle-violet) calc(var(--runa-circle-violet-opacity) * 260%),
      transparent
    )
  );
}
.app-backdrop::after {
  width: min(75vw, 850px);
  right: -20%;
  bottom: -35%;
  background: conic-gradient(
    from 200deg,
    color-mix(
      in srgb,
      var(--runa-circle-cyan) calc(var(--runa-circle-cyan-opacity) * 260%),
      transparent
    ),
    color-mix(
        in srgb,
        var(--runa-gradient-cool-end)
          calc(var(--runa-circle-cyan-opacity) * 230%),
        transparent
      )
      30%,
    color-mix(
        in srgb,
        var(--runa-circle-violet) calc(var(--runa-circle-violet-opacity) * 150%),
        transparent
      )
      52%,
    color-mix(
        in srgb,
        var(--runa-circle-cyan) calc(var(--runa-circle-cyan-opacity) * 65%),
        transparent
      )
      76%,
    color-mix(
      in srgb,
      var(--runa-circle-cyan) calc(var(--runa-circle-cyan-opacity) * 260%),
      transparent
    )
  );
}
</style>
