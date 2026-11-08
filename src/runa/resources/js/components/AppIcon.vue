<script setup lang="ts">
import type { IconName } from '../runa/ui/icons';
withDefaults(
  defineProps<{ name: IconName; size?: number | string; color?: string }>(),
  { size: 24, color: 'currentColor' },
);
</script>
<template>
  <svg
    aria-hidden="true"
    focusable="false"
    :width="size"
    :height="size"
    viewBox="0 0 24 24"
    fill="none"
    :stroke="color"
    stroke-width="1.6"
    stroke-linecap="round"
    stroke-linejoin="round"
  >
    <g v-if="name === 'layers-intersect'">
      <path
        d="M8 6a2 2 0 0 1 2 -2h8a2 2 0 0 1 2 2v8a2 2 0 0 1 -2 2h-8a2 2 0 0 1 -2 -2l0 -8"
      />
      <path
        d="M4 10a2 2 0 0 1 2 -2h8a2 2 0 0 1 2 2v8a2 2 0 0 1 -2 2h-8a2 2 0 0 1 -2 -2l0 -8"
      />
    </g>
    <g v-if="name === 'clipboard-list'">
      <path
        d="M9 5H6a2 2 0 0 0 -2 2v13a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2V7a2 2 0 0 0 -2 -2h-3"
      />
      <rect x="9" y="3" width="6" height="4" rx="1" />
      <path d="M9 12h6M9 16h6" />
    </g>
    <g v-if="name === 'key'">
      <circle cx="8" cy="8" r="5" />
      <path d="m11.5 11.5 9.5 9.5M16 16l3 -3M19 19l3 -3" />
    </g>
    <g v-if="name === 'shield-lock'">
      <path d="M12 3 4 6v6c0 5 8 9 8 9s8 -4 8 -9V6l-8 -3" />
      <rect x="9" y="10" width="6" height="5" rx="1" />
      <path d="M10 10V8a2 2 0 0 1 4 0v2" />
    </g>
    <g v-if="name === 'refresh'">
      <path d="M20 7v5h-5M4 17v-5h5" />
      <path d="M6.1 7a7 7 0 0 1 11.6-1L20 9M4 15l2.3 3A7 7 0 0 0 17.9 17" />
    </g>
    <g v-if="name === 'filter-off'">
      <path d="m3 3 18 18M10 3h11l-7 8v3M6 6l4 5v8l4 2v-3" />
    </g>
    <g v-if="name === 'logout'">
      <path d="M9 5H5v14h4M13 8l4 4-4 4M9 12h11" />
    </g>
    <g v-if="name === 'sun'">
      <path d="M8 12a4 4 0 1 0 8 0a4 4 0 1 0 -8 0" />
      <path
        d="M3 12h1m8 -9v1m8 8h1m-9 8v1m-6.4 -15.4l.7 .7m12.1 -.7l-.7 .7m0 11.4l.7 .7m-12.1 -.7l-.7 .7"
      />
    </g>
    <g v-if="name === 'moon'">
      <path
        d="M12 3c.132 0 .263 0 .393 0a7.5 7.5 0 0 0 7.92 12.446a9 9 0 1 1 -8.313 -12.454l0 .008"
      />
    </g>
    <g v-if="name === 'device-desktop'">
      <path
        d="M3 5a1 1 0 0 1 1 -1h16a1 1 0 0 1 1 1v10a1 1 0 0 1 -1 1h-16a1 1 0 0 1 -1 -1v-10"
      />
      <path d="M7 20h10" />
      <path d="M9 16v4" />
      <path d="M15 16v4" />
    </g>
    <g v-if="name === 'file-type-csv'">
      <path d="M14 3v4a1 1 0 0 0 1 1h4" />
      <path d="M5 12v-7a2 2 0 0 1 2 -2h7l5 5v4" />
      <path d="M7 16.5a1.5 1.5 0 0 0 -3 0v3a1.5 1.5 0 0 0 3 0" />
      <path
        d="M10 20.25c0 .414 .336 .75 .75 .75h1.25a1 1 0 0 0 1 -1v-1a1 1 0 0 0 -1 -1h-1a1 1 0 0 1 -1 -1v-1a1 1 0 0 1 1 -1h1.25a.75 .75 0 0 1 .75 .75"
      />
      <path d="M16 15l2 6l2 -6" />
    </g>
    <g v-if="name === 'file-type-xls'">
      <path d="M14 3v4a1 1 0 0 0 1 1h4" />
      <path d="M5 12v-7a2 2 0 0 1 2 -2h7l5 5v4" />
      <path d="M4 15l4 6" />
      <path d="M4 21l4 -6" />
      <path
        d="M17 20.25c0 .414 .336 .75 .75 .75h1.25a1 1 0 0 0 1 -1v-1a1 1 0 0 0 -1 -1h-1a1 1 0 0 1 -1 -1v-1a1 1 0 0 1 1 -1h1.25a.75 .75 0 0 1 .75 .75"
      />
      <path d="M11 15v6h3" />
    </g>
    <g v-if="name === 'file-type-pdf'">
      <path d="M14 3v4a1 1 0 0 0 1 1h4" />
      <path d="M5 12v-7a2 2 0 0 1 2 -2h7l5 5v4" />
      <path d="M5 18h1.5a1.5 1.5 0 0 0 0 -3h-1.5v6" />
      <path d="M17 18h2" />
      <path d="M20 15h-3v6" />
      <path d="M11 15v6h1a2 2 0 0 0 2 -2v-2a2 2 0 0 0 -2 -2h-1" />
    </g>
    <g v-if="name === 'printer'">
      <path
        d="M17 17h2a2 2 0 0 0 2 -2v-4a2 2 0 0 0 -2 -2h-14a2 2 0 0 0 -2 2v4a2 2 0 0 0 2 2h2"
      />
      <path d="M17 9v-4a2 2 0 0 0 -2 -2h-6a2 2 0 0 0 -2 2v4" />
      <path
        d="M7 15a2 2 0 0 1 2 -2h6a2 2 0 0 1 2 2v4a2 2 0 0 1 -2 2h-6a2 2 0 0 1 -2 -2l0 -4"
      />
    </g>
    <g v-if="name === 'adjustments-horizontal'">
      <path d="M12 6a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" />
      <path d="M4 6l8 0" />
      <path d="M16 6l4 0" />
      <path d="M6 12a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" />
      <path d="M4 12l2 0" />
      <path d="M10 12l10 0" />
      <path d="M15 18a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" />
      <path d="M4 18l11 0" />
      <path d="M19 18l1 0" />
    </g>
    <g v-if="name === 'plus'">
      <path d="M12 5l0 14" />
      <path d="M5 12l14 0" />
    </g>
    <g v-if="name === 'pencil'">
      <path d="M4 20h4l10.5 -10.5a2.828 2.828 0 1 0 -4 -4l-10.5 10.5v4" />
      <path d="M13.5 6.5l4 4" />
    </g>
    <g v-if="name === 'device-floppy'">
      <path
        d="M6 4h10l4 4v10a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2"
      />
      <path d="M10 14a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" />
      <path d="M14 4l0 4l-6 0l0 -4" />
    </g>
    <g v-if="name === 'check'">
      <path d="M5 12l5 5l10 -10" />
    </g>
    <g v-if="name === 'x'">
      <path d="M18 6l-12 12" />
      <path d="M6 6l12 12" />
    </g>
    <g v-if="name === 'trash'">
      <path d="M4 7l16 0" />
      <path d="M10 11l0 6" />
      <path d="M14 11l0 6" />
      <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" />
      <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" />
    </g>
    <g v-if="name === 'eye'">
      <path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" />
      <path
        d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6"
      />
    </g>
    <g v-if="name === 'copy'">
      <path
        d="M7 9.667a2.667 2.667 0 0 1 2.667 -2.667h8.666a2.667 2.667 0 0 1 2.667 2.667v8.666a2.667 2.667 0 0 1 -2.667 2.667h-8.666a2.667 2.667 0 0 1 -2.667 -2.667l0 -8.666"
      />
      <path
        d="M4.012 16.737a2.005 2.005 0 0 1 -1.012 -1.737v-10c0 -1.1 .9 -2 2 -2h10c.75 0 1.158 .385 1.5 1"
      />
    </g>
    <g v-if="name === 'arrow-back-up'">
      <path d="M9 14l-4 -4l4 -4" />
      <path d="M5 10h11a4 4 0 1 1 0 8h-1" />
    </g>
    <g v-if="name === 'rotate-2'">
      <path d="M15 4.55a8 8 0 0 0 -6 14.9m0 -4.45v5h-5" />
      <path d="M18.37 7.16l0 .01" />
      <path d="M13 19.94l0 .01" />
      <path d="M16.84 18.37l0 .01" />
      <path d="M19.37 15.1l0 .01" />
      <path d="M19.94 11l0 .01" />
    </g>
    <g v-if="name === 'player-play'">
      <path d="M7 4v16l13 -8l-13 -8" />
    </g>
    <g v-if="name === 'send'">
      <path d="M10 14l11 -11" />
      <path
        d="M21 3l-6.5 18a.55 .55 0 0 1 -1 0l-3.5 -7l-7 -3.5a.55 .55 0 0 1 0 -1l18 -6.5"
      />
    </g>
    <g v-if="name === 'login'">
      <path
        d="M15 8v-2a2 2 0 0 0 -2 -2h-7a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h7a2 2 0 0 0 2 -2v-2"
      />
      <path d="M21 12h-13l3 -3" />
      <path d="M11 15l-3 -3" />
    </g>
    <g v-if="name === 'user-plus'">
      <path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" />
      <path d="M16 19h6" />
      <path d="M19 16v6" />
      <path d="M6 21v-2a4 4 0 0 1 4 -4h4" />
    </g>
    <g v-if="name === 'lock'">
      <path
        d="M5 13a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v6a2 2 0 0 1 -2 2h-10a2 2 0 0 1 -2 -2v-6"
      />
      <path d="M11 16a1 1 0 1 0 2 0a1 1 0 0 0 -2 0" />
      <path d="M8 11v-4a4 4 0 1 1 8 0v4" />
    </g>
    <g v-if="name === 'shield-check'">
      <path
        d="M11.46 20.846a12 12 0 0 1 -7.96 -14.846a12 12 0 0 0 8.5 -3a12 12 0 0 0 8.5 3a12 12 0 0 1 -.09 7.06"
      />
      <path d="M15 19l2 2l4 -4" />
    </g>
    <g v-if="name === 'shield-x'">
      <path
        d="M13.252 20.601c-.408 .155 -.826 .288 -1.252 .399a12 12 0 0 1 -8.5 -15a12 12 0 0 0 8.5 -3a12 12 0 0 0 8.5 3a12 12 0 0 1 -.19 7.357"
      />
      <path d="M22 22l-5 -5" />
      <path d="M17 22l5 -5" />
    </g>
    <g v-if="name === 'mail'">
      <path
        d="M3 7a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v10a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-10"
      />
      <path d="M3 7l9 6l9 -6" />
    </g>
    <g v-if="name === 'download'">
      <path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2" />
      <path d="M7 11l5 5l5 -5" />
      <path d="M12 4l0 12" />
    </g>
    <g v-if="name === 'info-circle'">
      <path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0" />
      <path d="M12 9h.01" />
      <path d="M11 12h1v4h1" />
    </g>
    <g v-if="name === 'circle-check'">
      <path d="M3 12a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" />
      <path d="M9 12l2 2l4 -4" />
    </g>
    <g v-if="name === 'alert-triangle'">
      <path d="M12 9v4" />
      <path
        d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0"
      />
      <path d="M12 16h.01" />
    </g>
    <g v-if="name === 'alert-circle'">
      <path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0" />
      <path d="M12 8v4" />
      <path d="M12 16h.01" />
    </g>
    <g v-if="name === 'inbox'">
      <path
        d="M4 6a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2l0 -12"
      />
      <path d="M4 13h3l3 3h4l3 -3h3" />
    </g>
    <g v-if="name === 'flask'">
      <path d="M9 3l6 0" />
      <path d="M10 9l4 0" />
      <path d="M10 3v6l-4 11a.7 .7 0 0 0 .5 1h11a.7 .7 0 0 0 .5 -1l-4 -11v-6" />
    </g>
    <g v-if="name === 'history'">
      <path d="M12 8l0 4l2 2" />
      <path d="M3.05 11a9 9 0 1 1 .5 4m-.5 5v-5h5" />
    </g>
    <g v-if="name === 'power'">
      <path d="M7 6a7.75 7.75 0 1 0 10 0" />
      <path d="M12 4l0 8" />
    </g>
    <g v-if="name === 'crop'">
      <path d="M8 5v10a1 1 0 0 0 1 1h10" />
      <path d="M5 8h10a1 1 0 0 1 1 1v10" />
    </g>
    <g v-if="name === 'arrows-exchange'">
      <path d="M7 10h14l-4 -4" />
      <path d="M17 14h-14l4 4" />
    </g>
    <g v-if="name === 'arrow-up'">
      <path d="M12 5l0 14" />
      <path d="M18 11l-6 -6" />
      <path d="M6 11l6 -6" />
    </g>
    <g v-if="name === 'arrow-down'">
      <path d="M12 5l0 14" />
      <path d="M18 13l-6 6" />
      <path d="M6 13l6 6" />
    </g>
    <g v-if="name === 'chevron-left'">
      <path d="M15 6l-6 6l6 6" />
    </g>
    <g v-if="name === 'chevron-right'">
      <path d="M9 6l6 6l-6 6" />
    </g>
    <g v-if="name === 'home'">
      <path d="M5 12l-2 0l9 -9l9 9l-2 0" />
      <path d="M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-7" />
      <path d="M9 21v-6a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v6" />
    </g>
    <g v-if="name === 'calendar-clock'">
      <path
        d="M10.5 21h-4.5a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v3"
      />
      <path d="M16 3v4" />
      <path d="M8 3v4" />
      <path d="M4 11h10" />
      <path d="M14 18a4 4 0 1 0 8 0a4 4 0 1 0 -8 0" />
      <path d="M18 16.5v1.5l.5 .5" />
    </g>
    <g v-if="name === 'calendar-plus'">
      <path
        d="M12.5 21h-6.5a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v5"
      />
      <path d="M16 3v4" />
      <path d="M8 3v4" />
      <path d="M4 11h16" />
      <path d="M16 19h6" />
      <path d="M19 16v6" />
    </g>
    <g v-if="name === 'calendar-minus'">
      <path
        d="M12.5 21h-6.5a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v8"
      />
      <path d="M16 3v4" />
      <path d="M8 3v4" />
      <path d="M4 11h16" />
      <path d="M16 19h6" />
    </g>
    <g v-if="name === 'lock-access'">
      <path d="M4 8v-2a2 2 0 0 1 2 -2h2" />
      <path d="M4 16v2a2 2 0 0 0 2 2h2" />
      <path d="M16 4h2a2 2 0 0 1 2 2v2" />
      <path d="M16 20h2a2 2 0 0 0 2 -2v-2" />
      <path
        d="M8 12a1 1 0 0 1 1 -1h6a1 1 0 0 1 1 1v3a1 1 0 0 1 -1 1h-6a1 1 0 0 1 -1 -1l0 -3"
      />
      <path d="M10 11v-2a2 2 0 1 1 4 0v2" />
    </g>
    <g v-if="name === 'lock-open'">
      <path
        d="M5 13a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v6a2 2 0 0 1 -2 2h-10a2 2 0 0 1 -2 -2l0 -6"
      />
      <path d="M11 16a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" />
      <path d="M8 11v-5a4 4 0 0 1 8 0" />
    </g>
    <g v-if="name === 'key-off'">
      <path
        d="M10.17 6.159l2.316 -2.316a2.877 2.877 0 0 1 4.069 0l3.602 3.602a2.877 2.877 0 0 1 0 4.069l-2.33 2.33"
      />
      <path
        d="M14.931 14.948a2.863 2.863 0 0 1 -1.486 -.79l-.301 -.302l-6.558 6.558a2 2 0 0 1 -1.239 .578l-.175 .008h-1.172a1 1 0 0 1 -.993 -.883l-.007 -.117v-1.172a2 2 0 0 1 .467 -1.284l.119 -.13l.414 -.414h2v-2h2v-2l2.144 -2.144l-.301 -.301a2.863 2.863 0 0 1 -.794 -1.504"
      />
      <path d="M15 9h.01" />
      <path d="M3 3l18 18" />
    </g>
    <g v-if="name === 'device-desktop-off'">
      <path
        d="M8 4h12a1 1 0 0 1 1 1v10a1 1 0 0 1 -1 1m-4 0h-12a1 1 0 0 1 -1 -1v-10a1 1 0 0 1 1 -1"
      />
      <path d="M7 20h10" />
      <path d="M9 16v4" />
      <path d="M15 16v4" />
      <path d="M3 3l18 18" />
    </g>
    <g v-if="name === 'photo-off'">
      <path d="M15 8h.01" />
      <path
        d="M7 3h11a3 3 0 0 1 3 3v11m-.856 3.099a2.991 2.991 0 0 1 -2.144 .901h-12a3 3 0 0 1 -3 -3v-12c0 -.845 .349 -1.608 .91 -2.153"
      />
      <path d="M3 16l5 -5c.928 -.893 2.072 -.893 3 0l5 5" />
      <path d="M16.33 12.338c.574 -.054 1.155 .166 1.67 .662l3 3" />
      <path d="M3 3l18 18" />
    </g>
    <g v-if="name === 'clipboard-x'">
      <path
        d="M9 5h-2a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-12a2 2 0 0 0 -2 -2h-2"
      />
      <path
        d="M9 5a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2a2 2 0 0 1 -2 2h-2a2 2 0 0 1 -2 -2"
      />
      <path d="M10 12l4 4m0 -4l-4 4" />
    </g>
    <g v-if="name === 'shield-star'">
      <path
        d="M11.143 20.743a12 12 0 0 1 -7.643 -14.743a12 12 0 0 0 8.5 -3a12 12 0 0 0 8.5 3c.504 1.716 .614 3.505 .343 5.237"
      />
      <path
        d="M17.8 20.817l-2.172 1.138a.392 .392 0 0 1 -.568 -.41l.415 -2.411l-1.757 -1.707a.389 .389 0 0 1 .217 -.665l2.428 -.352l1.086 -2.193a.392 .392 0 0 1 .702 0l1.086 2.193l2.428 .352a.39 .39 0 0 1 .217 .665l-1.757 1.707l.414 2.41a.39 .39 0 0 1 -.567 .411l-2.172 -1.138"
      />
    </g>
    <g v-if="name === 'shield-off'">
      <path
        d="M17.67 17.667a12 12 0 0 1 -5.67 3.333a12 12 0 0 1 -8.5 -15c.794 .036 1.583 -.006 2.357 -.124m3.128 -.926a11.997 11.997 0 0 0 3.015 -1.95a12 12 0 0 0 8.5 3a12 12 0 0 1 -1.116 9.376"
      />
      <path d="M3 3l18 18" />
    </g>
    <g v-if="name === 'mail-plus'">
      <path
        d="M12 19h-7a2 2 0 0 1 -2 -2v-10a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v5.5"
      />
      <path d="M16 19h6" />
      <path d="M19 16v6" />
      <path d="M3 7l9 6l9 -6" />
    </g>
    <g v-if="name === 'mail-x'">
      <path
        d="M13.5 19h-8.5a2 2 0 0 1 -2 -2v-10a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v6"
      />
      <path d="M3 7l9 6l9 -6" />
      <path d="M22 22l-5 -5" />
      <path d="M17 22l5 -5" />
    </g>
    <g v-if="name === 'mail-check'">
      <path
        d="M11 19h-6a2 2 0 0 1 -2 -2v-10a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v6"
      />
      <path d="M3 7l9 6l9 -6" />
      <path d="M15 19l2 2l4 -4" />
    </g>
    <g v-if="name === 'user-check'">
      <path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" />
      <path d="M6 21v-2a4 4 0 0 1 4 -4h4" />
      <path d="M15 19l2 2l4 -4" />
    </g>
    <g v-if="name === 'user-off'">
      <path
        d="M8.18 8.189a4.01 4.01 0 0 0 2.616 2.627m3.507 -.545a4 4 0 1 0 -5.59 -5.552"
      />
      <path
        d="M6 21v-2a4 4 0 0 1 4 -4h4c.412 0 .81 .062 1.183 .178m2.633 2.618c.12 .38 .184 .785 .184 1.204v2"
      />
      <path d="M3 3l18 18" />
    </g>
  </svg>
</template>
