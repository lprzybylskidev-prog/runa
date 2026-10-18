<script setup lang="ts">
import { ref } from 'vue';
import { Button, Form, Select } from '@/runa/ui';
import AuthLayout from '../js/layouts/AuthLayout.vue';

const category = ref('controls');
const activations = ref(0);
const categories = [
  { value: 'controls', label: 'Przyciski i formularze / Buttons and forms' },
  { value: 'layouts', label: 'Układy / Layouts' },
  { value: 'data', label: 'Dane i eksport / Data and export' },
];
</script>

<template>
  <AuthLayout>
    <template #default>
      <h1 class="text-2xl font-semibold">Przegląd UI / UI review</h1>
      <p class="mt-4 text-sm text-muted">
        RM-2026-004 · 1 · Szkielet katalogu / Catalog skeleton
      </p>
      <div class="mt-6">
        <Select
          v-model="category"
          label="Kategoria / Category"
          :options="categories"
        />
      </div>
      <section class="mt-6" aria-live="polite" aria-atomic="true">
        <template v-if="category === 'controls'">
          <h2 class="text-lg">Istniejące elementy / Existing controls</h2>
          <Form class="mt-4" @submit="activations++">
            <Button type="submit">Test</Button>
            <output class="ml-4">{{ activations }}</output>
          </Form>
        </template>
        <p v-else class="text-sm text-muted">
          Podgląd zostanie dodany w odpowiednim punkcie roadmapy. / Preview
          follows in its roadmap point.
        </p>
      </section>
      <nav class="mt-6 flex flex-wrap gap-4 text-sm" aria-label="Review links">
        <a href="/">/</a>
        <a href="/__review/not-found">404</a>
      </nav>
    </template>
  </AuthLayout>
</template>
