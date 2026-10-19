<script setup lang="ts">
import ReviewAdditionalInputs from './ReviewAdditionalInputs.vue';
import ReviewForms from './ReviewForms.vue';
import ReviewLoading from './ReviewLoading.vue';
import ReviewContainers from './ReviewContainers.vue';
import ReviewFeedback from './ReviewFeedback.vue';
import ReviewControls from './ReviewControls.vue';
import { ref } from 'vue';
import { Button, Select, MinimalLayout, IdentityProvider } from '@/runa/ui';

const preview = new window.URLSearchParams(window.location.search).get(
  'preview',
);
const longContent = ref(false);
const showIllustration = ref(true);
const showFooter = ref(true);
const requestedCategory = new window.URLSearchParams(
  window.location.search,
).get('category');
const category = ref(
  [
    'controls',
    'feedback',
    'containers',
    'loading',
    'forms',
    'additional',
    'layouts',
    'data',
  ].includes(requestedCategory ?? '')
    ? requestedCategory!
    : 'controls',
);
const categories = [
  {
    value: 'controls',
    label: 'Przyciski / Buttons',
  },
  { value: 'feedback', label: 'Komunikaty i podpowiedzi / Feedback' },
  {
    value: 'containers',
    label: 'Kontenery i nawigacja / Containers and navigation',
  },
  {
    value: 'loading',
    label: 'Ładowanie i statystyki / Loading and statistics',
  },
  { value: 'forms', label: 'Formularze i pola / Forms and fields' },
  { value: 'additional', label: 'Dodatkowe pola / Additional inputs' },
  { value: 'layouts', label: 'Układy / Layouts' },
  { value: 'data', label: 'Dane i eksport / Data and export' },
];
</script>

<template>
  <IdentityProvider
    v-if="preview === 'minimal'"
    :identity="{
      applicationId: 'review',
      applicationName: 'Atlas',
      applicationVersion: '2.4.1',
    }"
  >
    <MinimalLayout
      :footer="{ visible: showFooter }"
      :illustration="showIllustration"
    >
      <template #default="{ t }">
        <h1 class="text-2xl font-semibold">
          {{ t('language') === 'Język' ? 'Układ minimalny' : 'Minimal layout' }}
        </h1>
        <p class="mt-4 text-sm text-muted">
          {{
            t('language') === 'Język'
              ? 'Niezależna aplikacja Atlas. Wersja platformy pochodzi z pliku VERSION.'
              : 'Independent Atlas application. Platform version comes from VERSION.'
          }}
        </p>
        <div class="mt-6 flex flex-wrap gap-3">
          <Button @click="longContent = !longContent">{{
            t('language') === 'Język'
              ? 'Krótka / długa treść'
              : 'Short / long content'
          }}</Button>
          <Button @click="showIllustration = !showIllustration">{{
            t('language') === 'Język' ? 'Ilustracja' : 'Illustration'
          }}</Button>
          <Button @click="showFooter = !showFooter">{{
            t('language') === 'Język' ? 'Stopka' : 'Footer'
          }}</Button>
        </div>
        <div v-if="longContent" class="mt-6 space-y-6">
          <p v-for="n in 24" :key="n" class="text-sm text-muted">
            {{ n }} ·
            {{
              t('language') === 'Język'
                ? 'Treść dokumentu pozostaje w normalnym przepływie strony. Stopka znajduje się za całą treścią.'
                : 'Document content stays in normal page flow. The footer follows all content.'
            }}
          </p>
        </div>
      </template>
      <template #actions="{ t }"
        ><a href="/__review/ui" class="text-sm underline">{{
          t('language') === 'Język' ? 'Powrót do przeglądu' : 'Back to review'
        }}</a></template
      >
    </MinimalLayout>
  </IdentityProvider>
  <MinimalLayout v-else :illustration="false">
    <template #default="{ t }">
      <h1 class="text-2xl font-semibold">Przegląd UI / UI review</h1>
      <p class="mt-4 text-sm text-muted">
        RM-2026-004 · 8–13 · Komponenty / Components
      </p>
      <div class="mt-6">
        <Select
          v-model="category"
          label="Kategoria / Category"
          :options="categories"
        />
      </div>
      <section class="mt-6">
        <template v-if="category === 'controls'">
          <ReviewControls :english="t('language') !== 'Język'" />
        </template>
        <ReviewAdditionalInputs
          v-else-if="category === 'additional'"
          :english="t('language') !== 'Język'"
        />
        <ReviewForms
          v-else-if="category === 'forms'"
          :english="t('language') !== 'Język'"
        />
        <ReviewLoading
          v-else-if="category === 'loading'"
          :english="t('language') !== 'Język'"
        />
        <ReviewContainers
          v-else-if="category === 'containers'"
          :english="t('language') !== 'Język'"
        />
        <ReviewFeedback
          v-else-if="category === 'feedback'"
          :english="t('language') !== 'Język'"
        />
        <a
          v-else-if="category === 'layouts'"
          href="/__review/ui?preview=minimal"
          class="text-sm underline"
          >Układ minimalny / Minimal layout</a
        >
        <p v-else class="text-sm text-muted">
          Podgląd zostanie dodany w odpowiednim punkcie roadmapy. / Preview
          follows in its roadmap point.
        </p>
      </section>
      <nav class="mt-6 flex flex-wrap gap-4 text-sm" aria-label="Review links">
        <a href="/">/</a>
        <a href="/__review/application/overview"
          >Układ aplikacyjny / Application layout</a
        >
        <a href="/__review/email">E-mail</a>
        <a href="/__review/email?format=text">TXT</a>
        <a href="/__review/email?format=eml">EML</a>
        <a href="/__review/not-found">404</a>
        <a
          v-for="status in [403, 419, 429, 500, 503]"
          :key="status"
          :href="`/__review/errors/${status}`"
          >{{ status }}</a
        >
        <a href="/__review/errors/emergency">Awaryjne 503 / Emergency 503</a>
      </nav>
    </template>
  </MinimalLayout>
</template>
