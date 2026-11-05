<script setup lang="ts">
import { useAdminLocale } from './localization';
const { t: tr } = useAdminLocale();
import { Button } from '@/runa/ui';
import type { AdminActionEmits, AdminActionProps } from './contracts';
defineProps<AdminActionProps>();
const emit = defineEmits<AdminActionEmits>();
</script>
<template>
  <Button
    :intent="
      command.destructive
        ? 'danger'
        : ['save', 'create'].includes(command.intent)
          ? 'commit'
          : 'inspect'
    "
    :busy="busy"
    :disabled="disabled || !command.available"
    variant="outline"
    @click="emit('invoke')"
    >{{ tr(command.title) }}</Button
  >
</template>
