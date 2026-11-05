<script setup lang="ts">
import {
  AuthOperationProofPage,
  providePasswordConfirmation,
} from '@/runa/auth';
import { providePresentationVisibility } from '@/runa/ui';
const host = providePasswordConfirmation();
providePresentationVisibility(() => !host.active.value);
</script>
<template>
  <div>
    <slot />
    <AuthOperationProofPage
      v-if="host.active.value"
      :controller="host.active.value"
      locale="en"
      @confirmed="host.complete()"
      @cancel="host.cancel()"
    />
  </div>
</template>
