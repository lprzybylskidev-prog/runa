<template>
  <span class="runa-logo" aria-hidden="true" />
</template>

<style scoped>
.runa-logo {
  display: inline-block;
  flex-shrink: 0;
  height: 28px;
  aspect-ratio: 416 / 903;
  background: currentColor;
  /* Original 1254px square; visible mark: x=435..850, y=195..1097.
     Crop transparent padding via mask sizing/position, preserving source bytes. */
  mask-image: url('../../images/runa-logo.png');
  mask-mode: alpha;
  mask-repeat: no-repeat;
  mask-size: 301.4423% 138.8704%;
  mask-position: 51.9093% 55.5556%;
}
</style>
