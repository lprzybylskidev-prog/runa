<script setup lang="ts">
import { Card, Skeleton } from '@/runa/ui';
import { useLocale } from '../../../resources/js/composables/useLocale';
const { locale } = useLocale();
const text = (pl: string, en: string) => (locale.value === 'pl' ? pl : en);
</script>
<template>
  <div
    class="auth-account-loading space-y-6"
    aria-busy="true"
    data-account-loading
  >
    <p role="status" class="sr-only">
      {{ text('Wczytywanie konta', 'Loading account') }}
    </p>
    <div aria-hidden="true" class="flex flex-wrap gap-2">
      <Skeleton
        v-for="width in ['w-64', 'w-32', 'w-40']"
        :key="width"
        :class="width"
        shape="block"
        class="account-tab-placeholder"
      />
    </div>
    <h2 class="text-xl font-semibold">
      {{ text('Konto i zabezpieczenia', 'Account and security') }}
    </h2>
    <div class="grid min-w-0 items-start gap-6 xl:grid-cols-2">
      <div class="min-w-0 space-y-6">
        <Card>
          <template #header
            ><h3>{{ text('Dane konta', 'Account details') }}</h3></template
          >
          <div class="space-y-6">
            <div v-for="field in 3" :key="field" class="space-y-3">
              <Skeleton class="w-40" :lines="1" />
              <Skeleton shape="block" class="account-field-placeholder" />
            </div>
            <Skeleton class="w-32 account-button-placeholder" shape="block" />
            <Skeleton :lines="2" />
          </div>
        </Card>
        <Card>
          <template #header
            ><h3>{{ text('Hasło', 'Password') }}</h3></template
          >
          <div class="space-y-6">
            <div v-for="field in 3" :key="field" class="space-y-3">
              <Skeleton class="w-40" :lines="1" />
              <Skeleton shape="block" class="account-field-placeholder" />
            </div>
            <Skeleton class="w-32 account-button-placeholder" shape="block" />
          </div>
        </Card>
      </div>
      <div class="min-w-0 space-y-6">
        <Card>
          <template #header
            ><h3>{{ text('Zdjęcie profilowe', 'Profile image') }}</h3></template
          >
          <div class="space-y-6">
            <Skeleton
              shape="circle"
              class="account-avatar-placeholder mx-auto"
            />
            <Skeleton :lines="2" />
            <Skeleton shape="block" class="account-field-placeholder" />
            <Skeleton class="w-32 account-button-placeholder" shape="block" />
          </div>
        </Card>
        <Card>
          <template #header
            ><h3>
              {{
                text('Uwierzytelnianie dwuetapowe', 'Two-factor authentication')
              }}
            </h3></template
          >
          <div class="space-y-6">
            <Skeleton v-for="row in 3" :key="row" :lines="2" />
          </div>
        </Card>
      </div>
    </div>
  </div>
</template>
<style scoped>
.auth-account-loading .account-tab-placeholder[data-shape='block'] :deep(span),
.auth-account-loading
  .account-field-placeholder[data-shape='block']
  :deep(span),
.auth-account-loading
  .account-button-placeholder[data-shape='block']
  :deep(span) {
  height: 44px;
}
.account-avatar-placeholder {
  width: 80px;
}
.auth-account-loading
  .account-avatar-placeholder[data-shape='circle']
  :deep(span) {
  width: 80px;
  height: 80px;
}
</style>
