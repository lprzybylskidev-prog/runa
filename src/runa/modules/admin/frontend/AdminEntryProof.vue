<script setup lang="ts">
import { Form, FormNotifications, TextInput, Button } from '@/runa/ui';
import type { AdminController } from './contracts';
const props = defineProps<{ controller: AdminController }>();
const { password, busy } = props.controller.entryProof;
</script>
<template>
  <Form
    class="max-w-lg space-y-6"
    novalidate
    @submit.prevent="controller.confirmEntry"
  >
    <p class="text-sm text-muted">
      Confirm your password to enter administration.
    </p>
    <TextInput
      :model-value="password"
      label="Current password"
      type="password"
      name="current_password"
      autocomplete="current-password"
      :disabled="busy"
      required
      @update:model-value="password = $event ?? ''"
    />
    <Button type="submit" :busy="busy" :disabled="!password"
      >Confirm password</Button
    >
    <FormNotifications
      :error="controller.error.value?.message ?? ''"
      locale="en"
    />
  </Form>
</template>
