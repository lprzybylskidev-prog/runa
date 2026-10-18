<script setup lang="ts">
import AppButton from './AppButton.vue';
import AppIcon from './AppIcon.vue';
import AppTooltip from './AppTooltip.vue';
import type { Locale, MessageKey } from '../locales/messages';
import type { Theme } from '../composables/useTheme';
defineProps<{
  navigation?: boolean;
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
    <template v-if="navigation">
      <form method="get">
        <input type="hidden" name="theme" :value="theme" />
        <AppButton
          type="submit"
          compact
          name="lang"
          :value="locale === 'pl' ? 'en' : 'pl'"
          :aria-label="languageLabel"
          >{{ locale.toUpperCase() }}</AppButton
        >
      </form>
      <form method="get">
        <input type="hidden" name="lang" :value="locale" />
        <AppButton
          type="submit"
          compact
          name="theme"
          :value="
            theme === 'system' ? 'light' : theme === 'light' ? 'dark' : 'system'
          "
          :aria-label="themeLabel"
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
      </form>
    </template>
    <template v-else>
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
    </template>
  </div>
</template>
