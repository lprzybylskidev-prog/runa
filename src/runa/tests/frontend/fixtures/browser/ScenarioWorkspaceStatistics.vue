<script setup lang="ts">
import { Statistic, Statistics, useStatisticsMode } from '@/runa/ui';
import type { PreferenceScope } from '@/runa/ui';
const props = defineProps<{
  english: boolean;
  density: string;
  scope: PreferenceScope;
}>();
const text = (pl: string, en: string) => (props.english ? en : pl);
const mode = useStatisticsMode(props.scope);
</script>
<template>
  <Statistics
    v-model="mode"
    :label="text('Statystyki', 'Statistics')"
    :global-label="text('Globalne', 'Global')"
    :filtered-label="text('Filtrowane', 'Filtered')"
    ><template #default="{ mode: active }">
      <Statistic
        :label="text('Otwarte tematy', 'Open topics')"
        :value="active === 'filtered' && density === 'current' ? 6 : 12"
        :description="text('w tej przestrzeni', 'in this workspace')"
        icon="layers-intersect"
      />
      <Statistic
        :label="text('W trakcie', 'In progress')"
        :value="active === 'filtered' && density === 'current' ? '02' : '04'"
        :description="text('na bieżącym etapie', 'in the current stage')"
      />
      <Statistic
        :label="text('Członkowie zespołu', 'Team members')"
        value="08"
        :description="text('we wspólnym obszarze', 'in the shared area')"
      /> </template
  ></Statistics>
</template>
