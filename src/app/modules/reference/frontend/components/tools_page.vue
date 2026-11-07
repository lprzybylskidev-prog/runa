<script setup lang="ts">
import { computed, ref } from 'vue';
import { useAdminLocale, useAdminPageContext } from '@/runa/admin';
import type { AdminPageProps } from '@/runa/admin';
import type { ReferenceAccess, ReferenceView } from '../contracts';
import Workspace from './workspace.vue';
const props = defineProps<AdminPageProps>();
const workspace = ref<InstanceType<typeof Workspace>>();
const context = useAdminPageContext(props.controller, {
  beforeLeave: () => workspace.value?.beforeLeave() ?? Promise.resolve(true),
  dirty: () => workspace.value?.blocksNavigation ?? false,
  settled: () => workspace.value?.initialSettled ?? false,
});
const { locale } = useAdminLocale();
const view = computed<ReferenceView>(() => {
  const key = props.controller.page.value?.key;
  return key === 'samples' || key === 'history' || key === 'work'
    ? key
    : 'settings';
});
const access = computed<ReferenceAccess>(() => {
  const section = props.controller.page.value?.sections.find(
    (section) => section.key === view.value,
  );
  const field = (key: string) =>
    section?.fields.find((field) => field.key === key);
  return {
    identity: context.identity.value,
    read:
      !!section &&
      !section.diagnostic &&
      props.controller.state.value === 'ready',
    write: !!section && !section.readonly,
    codeRead: !!field('code'),
    codeWrite: !!field('code') && !field('code')?.readonly,
    credentialRead: !!field('credential'),
    credentialWrite: !!field('credential') && !field('credential')?.readonly,
  };
});
</script>
<template>
  <Workspace ref="workspace" :access="access" :view="view" :locale="locale" />
</template>
