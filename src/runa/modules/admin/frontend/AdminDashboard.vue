<script setup lang="ts">
import { useAdminLocale } from './localization';
const { locale, t: tr } = useAdminLocale();
import { Card, formatApplicationInstant } from '@/runa/ui';
import AdminOperations from './AdminOperations.vue';
import type { AdminController } from './contracts';
import type { AdminBootstrap, AdminComponentRegistry } from './contracts';
import { resolveAdminComponent } from './components';
import { useAdminPresentation } from './presentation';
import AdminComponentBoundary from './AdminComponentBoundary.vue';
const props = defineProps<{
  bootstrap: AdminBootstrap;
  admin?: AdminController | undefined;
  components?: AdminComponentRegistry | undefined;
}>();
const presentation = useAdminPresentation();
</script>
<template>
  <div class="space-y-6">
    <component
      :is="presentation.operations ?? AdminOperations"
      v-if="admin"
      :admin="admin"
    />
    <div
      v-if="props.bootstrap.dashboard.widgets.length"
      class="grid min-w-0 gap-6 lg:grid-cols-2"
    >
      <AdminComponentBoundary
        v-for="widget in bootstrap.dashboard.widgets"
        :key="widget.id"
        :identity="bootstrap.revision + ':' + widget.id"
      >
        <component
          :is="
            resolveAdminComponent(widget.component, components).component ??
            presentation.widget
          "
          v-if="widget.component || presentation.widget"
          :widget="widget"
        />
        <Card v-else>
          <template #header
            ><h2>{{ tr(widget.title) }}</h2></template
          >
          <p v-if="widget.diagnostic" class="text-sm text-muted">
            {{ tr('Widget information is unavailable or stale.') }}
          </p>
          <p v-else-if="!widget.observed_at" class="text-sm text-muted">
            {{ tr('Observation time is unknown.') }}
          </p>
          <dl class="space-y-2 text-sm">
            <div
              v-for="(value, key) in widget.values"
              :key="key"
              class="flex flex-wrap gap-3"
            >
              <dt class="text-muted">{{ tr(String(key)) }}</dt>
              <dd class="min-w-0 break-words">
                {{ typeof value === 'object' ? JSON.stringify(value) : value }}
              </dd>
            </div>
          </dl>
          <p v-if="widget.observed_at" class="mt-4 text-xs text-muted">
            {{ tr('Observed') }}
            <time :datetime="widget.observed_at">{{
              formatApplicationInstant(widget.observed_at, locale)
            }}</time>
          </p>
        </Card>
      </AdminComponentBoundary>
    </div>
  </div>
</template>
