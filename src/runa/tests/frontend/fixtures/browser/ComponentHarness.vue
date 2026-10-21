<script setup lang="ts">
import { MinimalLayout } from '@/runa/ui';
import Controls from './ScenarioControls.vue';
import Feedback from './ScenarioFeedback.vue';
import Containers from './ScenarioContainers.vue';
import Loading from './ScenarioLoading.vue';
import Forms from './ScenarioForms.vue';
import AdditionalInputs from './ScenarioAdditionalInputs.vue';
import Selection from './ScenarioSelection.vue';
import AdvancedForms from './ScenarioAdvancedForms.vue';
import Dialogs from './ScenarioDialogs.vue';
import Tables from './ScenarioTables.vue';
const scenarios = {
  controls: Controls,
  feedback: Feedback,
  containers: Containers,
  loading: Loading,
  forms: Forms,
  additional: AdditionalInputs,
  selection: Selection,
  advanced: AdvancedForms,
  dialogs: Dialogs,
  data: Tables,
};
const category =
  new window.URLSearchParams(window.location.search).get('category') ??
  'controls';
const scenario = scenarios[category as keyof typeof scenarios];
</script>
<template>
  <MinimalLayout :illustration="false"
    ><template #default="{ t }"
      ><h1 class="text-2xl font-semibold">Component test fixture</h1>
      <section class="mt-6">
        <component
          :is="scenario"
          v-if="scenario"
          :english="t('language') !== 'Język'"
        /></section></template
  ></MinimalLayout>
</template>
