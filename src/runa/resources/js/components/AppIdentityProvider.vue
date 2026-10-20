<script setup lang="ts">
import { computed, provide } from 'vue';
import { identityKey, resolveIdentity } from '../runa/ui/identity';
import type { ApplicationIdentity } from '../runa/ui/identity';
const props = defineProps<{ identity: Partial<ApplicationIdentity> }>();
const identity = computed(() => resolveIdentity(props.identity));
provide(identityKey, identity);
</script>
<template><slot :identity="identity" /></template>
