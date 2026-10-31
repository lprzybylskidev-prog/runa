<script setup lang="ts">
import { AdminApplication } from '@/runa/admin';
import type {
  AdminApplicationProps,
  AdminApplicationSlots,
} from '@/runa/admin';
import CustomerAdminPage from './CustomerAdminPage.vue';
const props = defineProps<AdminApplicationProps>();
defineSlots<AdminApplicationSlots>();
</script>
<template>
  <AdminApplication v-slot="{ controller }" v-bind="props">
    <main data-customer-admin>
      <h1>Customer administration</h1>
      <ul>
        <li v-for="owner in controller.bootstrap.value?.owners" :key="owner.id">
          <a
            :href="`/admin/${owner.id}/${owner.default_page}`"
            @click.prevent="
              controller.navigate({ owner: owner.id, page: owner.default_page })
            "
            >{{ owner.title }}</a
          >
        </li>
      </ul>
      <CustomerAdminPage
        v-if="controller.location.value"
        :admin="controller"
        :location="controller.location.value"
        :components="props.components"
      />
    </main>
  </AdminApplication>
</template>
