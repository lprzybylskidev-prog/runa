<script setup lang="ts">
import { formatApplicationInstant } from '@/runa/ui';
import { computed } from 'vue';
import { Badge, Icon, getModulePresentation } from '@/runa/ui';
import type { BadgeTone } from '@/runa/ui';
import type { AdminModuleCardProps, AdminJson } from './contracts';
import { useAdminLocale } from './localization';
import { adminReasonNames } from './operationMessages';
const props = withDefaults(defineProps<AdminModuleCardProps>(), {
  interactive: true,
  details: false,
  disabled: false,
});
const emit = defineEmits<{ select: [moduleId: string] }>();
const { locale, t: tr } = useAdminLocale();
const id = computed(() => String(props.module.id ?? ''));
const presentation = computed(() => getModulePresentation(id.value));
const tone = computed<BadgeTone>(() => {
  if (props.module.enabled === false || props.module.registered === false)
    return 'neutral';
  if (props.module.health === 'unhealthy') return 'danger';
  if (!props.module.available || props.module.health !== 'healthy')
    return 'warning';
  return 'success';
});
const status = computed(() =>
  props.module.enabled === false
    ? 'Disabled'
    : props.module.registered === false
      ? 'Not registered'
      : props.module.health === 'unhealthy'
        ? 'Needs attention'
        : props.module.available && props.module.health === 'healthy'
          ? 'Healthy'
          : 'Check required',
);
const reasons = computed(() =>
  Array.isArray(props.module.reasons)
    ? [
        ...new Set(
          props.module.reasons
            .filter((reason): reason is string => typeof reason === 'string')
            .map((reason) =>
              tr(
                adminReasonNames[reason] ?? 'The module requires verification.',
              ),
            ),
        ),
      ]
    : [],
);
function dependencies(value: AdminJson | undefined) {
  if (!value || typeof value !== 'object' || Array.isArray(value)) return [];
  return Object.entries(value).map(([id, version]) => ({
    id,
    name: getModulePresentation(id).name[locale.value],
    version: typeof version === 'string' ? version : '',
  }));
}
const observations = computed(() =>
  Array.isArray(props.module.observations)
    ? props.module.observations.filter(
        (value): value is Record<string, AdminJson> =>
          value !== null && typeof value === 'object' && !Array.isArray(value),
      )
    : [],
);
function date(value: AdminJson | undefined) {
  if (typeof value !== 'string') return '—';
  const date = new Date(value);
  return Number.isNaN(date.getTime())
    ? '—'
    : formatApplicationInstant(date, locale.value);
}
</script>
<template>
  <article
    class="admin-module-card"
    :class="{ 'is-interactive': interactive }"
    :data-tone="tone"
    :data-module="id"
  >
    <div class="flex min-w-0 items-start gap-4">
      <span class="module-symbol"
        ><Icon :name="presentation.icon" :size="24"
      /></span>
      <div class="min-w-0 flex-1 space-y-1">
        <div class="flex flex-wrap items-center gap-2">
          <h3 class="font-semibold">
            <button
              v-if="interactive"
              type="button"
              class="module-open"
              :disabled="disabled"
              @click="emit('select', id)"
            >
              {{ presentation.name[locale] }}</button
            ><template v-else>{{ presentation.name[locale] }}</template>
          </h3>
          <Badge :tone="tone" size="sm">{{ tr(status) }}</Badge>
        </div>
        <p class="break-all text-xs text-muted">{{ id }}</p>
      </div>
    </div>
    <p
      v-if="presentation.description[locale]"
      class="text-xs leading-relaxed text-muted"
    >
      {{ presentation.description[locale] }}
    </p>
    <dl class="grid grid-cols-2 gap-4 text-xs">
      <div class="space-y-1">
        <dt class="text-muted">{{ tr('Source version') }}</dt>
        <dd>{{ module.source_version ?? tr('Not available') }}</dd>
      </div>
      <div class="space-y-1">
        <dt class="text-muted">{{ tr('Installed version') }}</dt>
        <dd>{{ module.installed_version ?? tr('Not installed') }}</dd>
      </div>
    </dl>
    <ul
      v-if="reasons.length"
      class="module-reasons space-y-1 text-xs leading-relaxed"
    >
      <li
        v-for="reason in details ? reasons : reasons.slice(0, 2)"
        :key="reason"
      >
        {{ reason }}
      </li>
    </ul>
    <template v-if="details">
      <div class="flex flex-wrap gap-2">
        <Badge tone="info">{{
          module.kind === 'core' ? tr('Core module') : tr('Optional module')
        }}</Badge
        ><Badge :tone="module.registered ? 'success' : 'warning'">{{
          module.registered ? tr('Registered') : tr('Not registered')
        }}</Badge
        ><Badge :tone="module.enabled ? 'success' : 'neutral'">{{
          module.enabled ? tr('Enabled') : tr('Disabled')
        }}</Badge>
      </div>
      <dl
        v-for="[key, title] in [
          ['required', 'Required dependencies'],
          ['optional', 'Optional dependencies'],
        ] as const"
        :key="key"
        class="space-y-2 border-t border-line pt-4 text-xs"
      >
        <dt class="font-semibold">{{ tr(title) }}</dt>
        <dd v-if="!dependencies(module[key]).length" class="text-muted">
          {{ tr('None') }}
        </dd>
        <dd
          v-for="dependency in dependencies(module[key])"
          :key="dependency.id"
          class="flex flex-wrap justify-between gap-2"
        >
          <span>{{ dependency.name }}</span
          ><span class="text-muted">{{ dependency.version }}</span>
        </dd>
      </dl>
      <div
        v-if="observations.length"
        class="space-y-3 border-t border-line pt-4 text-xs"
      >
        <h4 class="font-semibold">{{ tr('Health observations') }}</h4>
        <div
          v-for="(observation, index) in observations"
          :key="index"
          class="flex flex-wrap items-center justify-between gap-2"
        >
          <Badge :tone="observation.passed ? 'success' : 'danger'">{{
            observation.passed ? tr('Passed') : tr('Failed')
          }}</Badge
          ><span class="text-muted"
            >{{ tr('Observed') }}: {{ date(observation.observed_at) }}</span
          ><span class="text-muted"
            >{{ tr('Valid until') }}: {{ date(observation.expires_at) }}</span
          >
        </div>
      </div>
    </template>
  </article>
</template>
<style scoped>
.admin-module-card {
  position: relative;
  --module-accent: var(--runa-text-secondary);
  --module-tint: var(--runa-surface-raised);
  display: flex;
  flex-direction: column;
  gap: 16px;
  width: 100%;
  min-width: 0;
  border: 1px solid var(--runa-border);
  border-top: 3px solid var(--module-accent);
  border-radius: 4px;
  padding: 20px;
  color: var(--runa-text);
  background: var(--runa-container-body-background);
  text-align: left;
}
.admin-module-card[data-tone='success'] {
  --module-accent: var(--runa-success-text);
  --module-tint: var(--runa-success-subtle);
}
.admin-module-card[data-tone='warning'] {
  --module-accent: var(--runa-warning-text);
  --module-tint: var(--runa-warning-subtle);
}
.admin-module-card[data-tone='danger'] {
  --module-accent: var(--runa-danger-text);
  --module-tint: var(--runa-danger-subtle);
}
.is-interactive {
  cursor: pointer;
  transition:
    border-color 160ms ease-out,
    background-color 160ms ease-out;
}
.is-interactive:hover {
  border-color: var(--module-accent);
  background: color-mix(
    in srgb,
    var(--module-tint) 20%,
    var(--runa-container-body-background)
  );
}
.is-interactive:focus-within {
  outline: 2px solid var(--runa-focus-ring);
  outline-offset: 3px;
}
.is-interactive:has(button:disabled) {
  opacity: 0.6;
  cursor: wait;
}
.module-open {
  text-align: left;
  cursor: pointer;
}
.module-open::after {
  content: '';
  position: absolute;
  inset: 0;
}
.module-open:focus {
  outline: none;
}
.module-open:disabled {
  cursor: wait;
}
.module-symbol {
  display: grid;
  place-items: center;
  width: 44px;
  height: 44px;
  flex-shrink: 0;
  border-radius: 4px;
  background: var(--module-tint);
  color: var(--module-accent);
}
.admin-module-card[data-tone='neutral'] .module-symbol {
  color: var(--runa-text-secondary);
}
.module-reasons {
  color: var(--module-accent);
}
.admin-module-card[data-tone='neutral'] .module-reasons {
  color: var(--runa-text-secondary);
}
@media (prefers-reduced-motion: reduce) {
  .is-interactive {
    transition: none;
  }
}
</style>
