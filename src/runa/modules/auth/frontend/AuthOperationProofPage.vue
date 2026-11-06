<script setup lang="ts">
import { computed } from 'vue';
import type {
  AuthOperationProofPageProps,
  AuthOperationProofPageSlots,
  AuthOperationProofPageEmits,
} from './contracts/presentation';
import { useAuthPresentation } from './presentation';
import AuthOperationProofPageDefault from './AuthOperationProofPageDefault.vue';
defineProps<AuthOperationProofPageProps>();
defineSlots<AuthOperationProofPageSlots>();
const emit = defineEmits<AuthOperationProofPageEmits>();
const presentation = useAuthPresentation();
const view = computed(
  () => presentation.value.operationProofPage ?? AuthOperationProofPageDefault,
);
</script>
<template>
  <component
    :is="view"
    :controller="controller"
    :locale="locale"
    :title="title"
    :description="description"
    :home-href="homeHref"
    @confirmed="emit('confirmed')"
    @cancel="emit('cancel')"
  >
    <template v-if="$slots.default" #default="scope"
      ><slot v-bind="scope"
    /></template>
  </component>
</template>
