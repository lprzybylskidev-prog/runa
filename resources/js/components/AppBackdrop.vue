<template>
  <div class="app-backdrop" aria-hidden="true">
    <span class="backdrop-ring ring-distant-one" />
    <span class="backdrop-ring ring-distant-two" />
    <span class="backdrop-ring ring-distant-three" />
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
  /* Main rings keep clear edges; only distant rings are softened. */
  mask-image: radial-gradient(
    closest-side,
    transparent 73.8%,
    black 74%,
    black 99.8%,
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

.backdrop-ring {
  position: absolute;
  aspect-ratio: 1;
  border-radius: 50%;
  pointer-events: none;
  background: conic-gradient(
    from 40deg,
    var(--runa-circle-violet),
    var(--runa-circle-cyan),
    var(--runa-circle-rose),
    var(--runa-circle-violet)
  );
  mask-image: radial-gradient(
    closest-side,
    transparent 73%,
    black 78%,
    black 93%,
    transparent 100%
  );
  filter: blur(7px);
  opacity: 0.1;
}
.ring-distant-one {
  width: min(42vw, 570px);
  right: 6%;
  top: -18%;
}
.ring-distant-two {
  width: min(48vw, 650px);
  left: -8%;
  bottom: -23%;
  opacity: 0.08;
  filter: blur(11px);
}
.ring-distant-three {
  width: min(35vw, 480px);
  left: 38%;
  top: 8%;
  opacity: 0.055;
  filter: blur(14px);
}
</style>
