<script setup lang="ts">
import AppButton from './AppButton.vue';
import AppIcon from './AppIcon.vue';
import AppTooltip from './AppTooltip.vue';
import type { Locale, MessageKey } from '../locales/messages';
import type { Theme } from '../composables/useTheme';
defineProps<{
  locale: Locale;
  theme: Theme;
  themeLabel: string;
  languageLabel: string;
  t: (key: MessageKey) => string;
}>();
const emit = defineEmits<{
  change: [preference: 'language' | 'theme', event: Event];
}>();
</script>
<template>
  <div :aria-label="t('preferences')" class="flex gap-2" role="group">
    <AppTooltip :text="t('changeLanguage')">
      <template #default="{ descriptionId }">
        <AppButton
          compact
          :aria-label="languageLabel"
          :aria-describedby="descriptionId"
          @click="emit('change', 'language', $event)"
        >
          {{ locale.toUpperCase() }}
        </AppButton>
      </template>
    </AppTooltip>
    <AppTooltip :text="t('changeTheme')">
      <template #default="{ descriptionId }">
        <AppButton
          compact
          :aria-label="themeLabel"
          :aria-describedby="descriptionId"
          @click="emit('change', 'theme', $event)"
        >
          <AppIcon
            :name="
              theme === 'system'
                ? 'device-desktop'
                : theme === 'light'
                  ? 'sun'
                  : 'moon'
            "
          />
        </AppButton>
      </template>
    </AppTooltip>
  </div>
</template>
