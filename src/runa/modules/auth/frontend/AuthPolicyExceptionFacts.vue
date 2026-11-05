<script setup lang="ts">
import { useAdminLocale } from '@/runa/admin';
import { formatApplicationInstant } from '@/runa/ui';
import type { PolicyException } from './policyExceptions';
defineProps<{ exception: PolicyException }>();
const { locale, t } = useAdminLocale();
</script>
<template>
  <dl class="grid min-w-0 gap-x-6 gap-y-3 text-sm sm:grid-cols-2">
    <div>
      <dt class="text-xs text-muted">
        {{ t({ pl: 'Własna wartość', en: 'Custom value' }) }}
      </dt>
      <dd class="mt-1 font-medium">
        {{ exception.value }}
        {{
          exception.key === 'password_expiry_days'
            ? t({ pl: 'dni', en: 'days' })
            : t({ pl: 'min', en: 'min' })
        }}
      </dd>
    </div>
    <div>
      <dt class="text-xs text-muted">
        {{ t({ pl: 'Obowiązuje do', en: 'Applies until' }) }}
      </dt>
      <dd class="mt-1">
        {{
          exception.expires_at === null
            ? t({ pl: 'Bezterminowo', en: 'Indefinitely' })
            : formatApplicationInstant(exception.expires_at, locale)
        }}
      </dd>
    </div>
    <div class="min-w-0 sm:col-span-2">
      <dt class="text-xs text-muted">{{ t({ pl: 'Powód', en: 'Reason' }) }}</dt>
      <dd class="mt-1 whitespace-pre-wrap break-words">
        {{ exception.reason }}
      </dd>
    </div>
  </dl>
</template>
