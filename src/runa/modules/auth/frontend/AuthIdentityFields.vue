<script setup lang="ts">
import { RadioGroup, TextInput } from '@/runa/ui';
import type {
  AuthIdentityField,
  AuthIdentityFieldsProps,
} from './contracts/fields';
withDefaults(defineProps<AuthIdentityFieldsProps>(), {
  columns: 2,
  showName: true,
  showLogin: true,
  readonly: false,
  disabled: false,
  readonlyFields: () => [],
});
const emit = defineEmits<{
  'update:naming': [value: string];
  field: [key: AuthIdentityField, value: string | null];
  blur: [key: AuthIdentityField];
}>();
const labels = {
  name: { pl: 'Nazwa wyświetlana', en: 'Display name' },
  first_name: { pl: 'Imię', en: 'First name' },
  surname: { pl: 'Nazwisko', en: 'Surname' },
  login: { pl: 'Login', en: 'Login' },
};
const autocomplete = {
  name: 'name',
  first_name: 'given-name',
  surname: 'family-name',
  login: 'username',
};
</script>
<template>
  <div class="min-w-0 space-y-5" data-auth-identity-fields>
    <RadioGroup
      v-if="showName"
      :model-value="naming"
      :label="locale === 'pl' ? 'Sposób prezentacji nazwy' : 'Name format'"
      :disabled="
        disabled ||
        readonly ||
        readonlyFields.some((key) =>
          ['name', 'first_name', 'surname'].includes(key),
        )
      "
      :options="[
        {
          value: 'full',
          label: locale === 'pl' ? 'Imię i nazwisko' : 'First name and surname',
        },
        {
          value: 'single',
          label: locale === 'pl' ? 'Nazwa wyświetlana' : 'Display name',
        },
      ]"
      @update:model-value="emit('update:naming', $event)"
    />
    <div
      class="grid min-w-0 gap-5"
      :class="columns === 2 ? 'md:grid-cols-2' : ''"
    >
      <template
        v-for="key in (showName
          ? naming === 'full'
            ? ['first_name', 'surname']
            : ['name']
          : []) as AuthIdentityField[]"
        :key="key"
      >
        <slot :name="key">
          <TextInput
            :model-value="values[key] ?? ''"
            :name="key"
            :label="labels[key][locale]"
            :autocomplete="autocomplete[key]"
            required
            :maxlength="160"
            :readonly="!!(readonly || readonlyFields?.includes(key))"
            :disabled="disabled"
            :error="errors?.[key] ?? []"
            @blur="emit('blur', key)"
            @update:model-value="emit('field', key, $event)"
          />
        </slot>
      </template>
      <slot />
      <slot v-if="showLogin" name="login">
        <TextInput
          :model-value="values.login ?? ''"
          name="login"
          autocomplete="username"
          label="Login"
          :required="loginRequired"
          :maxlength="64"
          :readonly="!!(readonly || readonlyFields?.includes('login'))"
          :disabled="disabled"
          :error="errors?.login ?? []"
          @blur="emit('blur', 'login')"
          @update:model-value="emit('field', 'login', $event)"
        />
      </slot>
    </div>
  </div>
</template>
