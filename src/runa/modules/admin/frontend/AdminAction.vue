<script setup lang="ts">
import { useAdminLocale } from './localization';
const { t: tr } = useAdminLocale();
import { actionIcons, Button } from '@/runa/ui';
import type { AdminActionEmits, AdminActionProps } from './contracts';
defineProps<AdminActionProps>();
const emit = defineEmits<AdminActionEmits>();
</script>
<template>
  <Button
    :icon="
      command.destructive
        ? actionIcons.delete
        : command.intent === 'create'
          ? actionIcons.create
          : command.intent === 'save'
            ? actionIcons.save
            : actionIcons.action
    "
    :intent="
      command.destructive
        ? 'danger'
        : ['save', 'create'].includes(command.intent)
          ? 'commit'
          : 'neutral'
    "
    :busy="busy"
    :disabled="disabled || !command.available"
    variant="outline"
    @click="emit('invoke')"
    >{{ tr(command.title) }}</Button
  >
</template>
