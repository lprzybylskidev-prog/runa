<script setup lang="ts">
import { computed, ref } from 'vue';
import ReviewWorkspaceStatistics from './ReviewWorkspaceStatistics.vue';
import {
  ApplicationLayout,
  Breadcrumbs,
  Button,
  IdentityProvider,
  Select,
  usePageResolver,
} from '@/runa/ui';
import type { Locale, NavigationEntry, PageDescriptor } from '@/runa/ui';
import { readPreference } from '../js/lib/preferences';
const locale = ref<Locale>(
  readPreference('runa.locale') === 'en' ? 'en' : 'pl',
);
const text = (pl: string, en: string) => (locale.value === 'pl' ? pl : en);
const pages: readonly PageDescriptor[] = [
  {
    routeKey: 'overview',
    moduleKey: 'overview',
    href: '/__review/application/overview',
    serverRouteName: 'review.application.overview',
    title: 'Overview',
  },
  {
    routeKey: 'projects',
    moduleKey: 'projects',
    areaKey: 'list',
    href: '/__review/application/projects',
    serverRouteName: 'review.application.projects',
    title: 'Projects',
  },
  {
    routeKey: 'activity',
    moduleKey: 'projects',
    areaKey: 'activity',
    href: '/__review/application/activity',
    serverRouteName: 'review.application.activity',
    title: 'Activity',
  },
  {
    routeKey: 'archive',
    moduleKey: 'projects',
    areaKey: 'archive',
    href: '/__review/application/archive',
    serverRouteName: 'review.application.archive',
    title: 'Archive',
  },
  {
    routeKey: 'team',
    moduleKey: 'team',
    href: '/__review/application/team',
    serverRouteName: 'review.application.team',
    title: 'Team',
  },
];
const { current, navigate } = usePageResolver(pages);
const labels = computed(() => ({
  overview: text('Przegląd', 'Overview'),
  projects: text('Projekty', 'Projects'),
  activity: text('Aktywność', 'Activity'),
  archive: text('Archiwum', 'Archive'),
  team: text('Zespół', 'Team'),
}));
function entry(key: keyof typeof labels.value): NavigationEntry {
  const page = pages.find((page) => page.routeKey === key)!;
  return {
    ...page,
    key,
    label: labels.value[key],
    icon: key === 'overview' ? 'device-desktop' : 'layers-intersect',
    availability: 'available',
  };
}
const longNavigation = ref(true);
const extraEntries = computed<NavigationEntry[]>(() =>
  longNavigation.value
    ? Array.from({ length: 16 }, (_, index) => ({
        ...entry(index % 2 === 0 ? 'projects' : 'team'),
        key: `scroll-example-${index}`,
        routeKey: `scroll-example-${index}`,
        moduleKey: `scroll-example-${index}`,
        label: `${text('Obszar testowy', 'Test area')} ${String(index + 1).padStart(2, '0')}`,
      }))
    : [],
);
const navigation = computed<NavigationEntry[]>(() => [
  entry('overview'),
  { ...entry('projects'), children: [entry('activity'), entry('archive')] },
  entry('team'),
  ...extraEntries.value,
  {
    key: 'reports',
    routeKey: 'reports',
    moduleKey: 'reports',
    href: '/__review/not-found',
    label: text('Raporty — wkrótce', 'Reports — soon'),
    icon: 'device-desktop',
    availability: 'disabled',
  },
]);
const topNavigation = computed<NavigationEntry[]>(() =>
  current.value?.moduleKey === 'projects'
    ? [
        entry('projects'),
        entry('activity'),
        {
          ...entry('archive'),
          key: 'more',
          label: text('Więcej', 'More'),
          children: [entry('archive')],
        },
        ...extraEntries.value,
      ]
    : [
        entry(current.value?.moduleKey === 'team' ? 'team' : 'overview'),
        ...extraEntries.value,
      ],
);
const title = computed(() =>
  current.value
    ? labels.value[current.value.routeKey as keyof typeof labels.value]
    : text('Nieznana strona', 'Unknown page'),
);
const sidebar = ref(true);
const topbar = ref(true);
const statistics = ref(true);
const toolbar = ref(true);
const supplementary = ref(true);
const longContent = ref(false);
const width = ref<'contained' | 'fluid'>('contained');
const density = ref('all');
const rows = computed(() => [
  {
    name: text(
      'Przygotowanie przestrzeni projektu',
      'Prepare the project workspace',
    ),
    area: text('Organizacja', 'Organization'),
    note: text(
      'Opis i materiały dla zespołu',
      'Description and materials for the team',
    ),
  },
  {
    name: text('Ustalenie kolejnych kroków', 'Define the next steps'),
    area: text('Planowanie', 'Planning'),
    note: text(
      'Zakres, terminy i odpowiedzialność',
      'Scope, dates and responsibilities',
    ),
  },
  {
    name: text('Przegląd materiałów', 'Review materials'),
    area: text('Dokumentacja', 'Documentation'),
    note: text(
      'Zebrane informacje w jednym miejscu',
      'Collected information in one place',
    ),
  },
  {
    name: text('Podsumowanie tygodnia', 'Weekly summary'),
    area: text('Współpraca', 'Collaboration'),
    note: text('Wnioski do kolejnego etapu', 'Notes for the next stage'),
  },
]);
function setWidth(value: string) {
  if (value === 'contained' || value === 'fluid') width.value = value;
}
</script>
<template>
  <IdentityProvider
    :identity="{
      applicationId: 'atlas-review',
      applicationName: 'Atlas',
      applicationVersion: '2.4.1',
    }"
  >
    <ApplicationLayout
      :navigation="navigation"
      :top-navigation="topNavigation"
      :current="current"
      :title="title"
      :description="
        text(
          'Twoja przestrzeń do porządkowania pracy i informacji.',
          'Your workspace for organizing work and information.',
        )
      "
      :sidebar="sidebar"
      :topbar="topbar"
      :width="width"
      user-name="Anna Kowalska"
      user-initials="AK"
      @navigate="navigate"
      @locale-change="locale = $event"
    >
      <template #actions
        ><a class="review-primary-link" href="/__review/ui"
          >{{ text('Przegląd interfejsu', 'UI review') }}
          <span aria-hidden="true">↗</span></a
        ></template
      >
      <template #breadcrumbs
        ><Breadcrumbs
          :label="text('Ścieżka nawigacji', 'Breadcrumbs')"
          :items="[
            { label: 'Atlas', href: '/__review/application/overview' },
            { label: title },
          ]"
          @navigate="navigate"
      /></template>
      <template v-if="statistics" #statistics
        ><ReviewWorkspaceStatistics
          :key="current?.routeKey ?? 'overview'"
          :english="locale === 'en'"
          :density="density"
          :scope="{
            applicationId: 'atlas-review',
            moduleKey: current?.moduleKey ?? 'workspace',
            viewKey: current?.routeKey ?? 'overview',
          }"
      /></template>
      <template v-if="toolbar" #toolbar
        ><Select
          size="sm"
          :model-value="density"
          :label="text('Widok', 'View')"
          :options="[
            { value: 'all', label: text('Wszystkie tematy', 'All topics') },
            { value: 'current', label: text('Bieżący etap', 'Current stage') },
          ]"
          @update:model-value="density = $event"
        /><span class="text-xs text-muted"
          >{{ text('Dane przykładowe', 'Sample data') }} ·
          {{ density === 'all' ? '4' : '2' }}
          {{ text('pozycje', 'items') }}</span
        ></template
      >
      <template #default>
        <section class="review-work-panel">
          <div class="review-panel-heading">
            <div>
              <p class="review-kicker">
                {{ text('Obszar roboczy', 'Workspace') }}
              </p>
              <h2>
                {{
                  title === labels.overview
                    ? text('Na najbliższy czas', 'Coming up next')
                    : title
                }}
              </h2>
            </div>
            <span class="review-preview-tag">{{
              text('PODGLĄD', 'PREVIEW')
            }}</span>
          </div>
          <ol class="review-topic-list">
            <li
              v-for="(row, index) in density === 'all'
                ? rows
                : rows.slice(0, 2)"
              :key="row.name"
            >
              <span class="review-topic-number">0{{ index + 1 }}</span>
              <div>
                <h3>{{ row.name }}</h3>
                <p>{{ row.note }}</p>
              </div>
              <span class="review-topic-area">{{ row.area }}</span>
            </li>
          </ol>
          <div v-if="longContent" class="review-long-content">
            <p v-for="n in 25" :key="n">
              {{ n }} ·
              {{
                text(
                  'Dłuższa treść robocza. Nawigacja boczna pozostaje na miejscu, a górna nawigacja i pasek narzędzi przewijają się razem z dokumentem.',
                  'Long workspace content. The sidebar stays in place while top navigation and toolbar scroll with the document.',
                )
              }}
            </p>
          </div>
        </section>
        <details class="review-options" data-print-hide open>
          <summary>
            {{ text('Ustawienia podglądu', 'Preview settings') }}
          </summary>
          <p>
            {{
              text(
                'Układ aplikacyjny · RM-2026-004 / 3. To widok demonstracyjny, bez operacji biznesowych.',
                'Application layout · RM-2026-004 / 3. This is a demonstration without business operations.',
              )
            }}
          </p>
          <div class="review-option-buttons">
            <Button
              :aria-pressed="longNavigation"
              @click="longNavigation = !longNavigation"
              >{{ text('Długie menu', 'Long menus') }}</Button
            >
            <Button :aria-pressed="sidebar" @click="sidebar = !sidebar">{{
              text('Menu boczne', 'Sidebar')
            }}</Button>
            <Button :aria-pressed="topbar" @click="topbar = !topbar">{{
              text('Menu górne', 'Top navigation')
            }}</Button>
            <Button
              :aria-pressed="statistics"
              @click="statistics = !statistics"
              >{{ text('Statystyki', 'Statistics') }}</Button
            >
            <Button :aria-pressed="toolbar" @click="toolbar = !toolbar">{{
              text('Pasek narzędzi', 'Toolbar')
            }}</Button>
            <Button
              :aria-pressed="supplementary"
              @click="supplementary = !supplementary"
              >{{ text('Panel dodatkowy', 'Supplementary panel') }}</Button
            >
            <Button
              :aria-pressed="longContent"
              @click="longContent = !longContent"
              >{{ text('Długa treść', 'Long content') }}</Button
            >
            <Select
              size="sm"
              :model-value="width"
              :label="text('Szerokość obszaru', 'Workspace width')"
              :options="[
                { value: 'contained', label: text('Ograniczona', 'Contained') },
                { value: 'fluid', label: text('Pełna', 'Fluid') },
              ]"
              @update:model-value="setWidth"
            />
          </div>
        </details>
      </template>
      <template v-if="supplementary" #supplementary
        ><section class="review-aside-panel">
          <p class="review-kicker">{{ text('Kontekst', 'Context') }}</p>
          <h2>{{ text('Wspólna przestrzeń', 'Shared workspace') }}</h2>
          <p>
            {{
              text(
                'Najważniejsze informacje obok bieżącej pracy. Ten panel jest opcjonalny.',
                'Key information alongside current work. This panel is optional.',
              )
            }}
          </p>
          <div class="review-aside-detail">
            <span>{{ text('Obszar', 'Area') }}</span
            ><strong>{{ title }}</strong>
          </div>
          <div class="review-aside-detail">
            <span>{{ text('Zespół', 'Team') }}</span
            ><strong>Atlas Studio</strong>
          </div>
        </section></template
      >
      <template #sidebar-bottom="{ collapsed }"
        ><div v-if="!collapsed" class="review-sidebar-note">
          <span class="review-kicker">ATLAS STUDIO</span>
          <p>
            {{
              text('Materiały i tematy zespołu.', 'Team materials and topics.')
            }}
          </p>
        </div></template
      >
    </ApplicationLayout>
  </IdentityProvider>
</template>
