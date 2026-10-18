<script setup lang="ts">
import ReviewControls from './ReviewControls.vue';
import { ref } from 'vue';
import { Button, Select, MinimalLayout, IdentityProvider } from '@/runa/ui';

const preview = new window.URLSearchParams(window.location.search).get(
  'preview',
);
const longContent = ref(false);
const showIllustration = ref(true);
const showFooter = ref(true);
const category = ref('controls');
const categories = [
  {
    value: 'controls',
    label: 'Przyciski, ikony i awatary / Buttons, icons and avatars',
  },
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
        RM-2026-004 · 8 · Przyciski, ikony i awatary / Buttons, icons and
        avatars
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
