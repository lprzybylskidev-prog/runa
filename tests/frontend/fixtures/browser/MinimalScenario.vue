<script setup lang="ts">
import { ref } from 'vue';
import { Button, MinimalLayout, IdentityProvider } from '@/runa/ui';
const longContent = ref(false),
  showIllustration = ref(true),
  showFooter = ref(true);
</script>
<template>
  <IdentityProvider
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
    </MinimalLayout>
  </IdentityProvider>
</template>
