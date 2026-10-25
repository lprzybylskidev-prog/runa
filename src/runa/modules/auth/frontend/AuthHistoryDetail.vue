<script setup lang="ts">
import { Dialog, Button, Spinner, Alert } from '@/runa/ui';
import type { AccountHistoryDetail, HistoryValue } from './historyDetails';
import { historyEventNames } from './historyDetails';
const props = defineProps<{
  open: boolean;
  loading: boolean;
  failed: boolean;
  detail: AccountHistoryDetail | undefined;
  locale: 'pl' | 'en';
}>();
defineEmits<{ close: []; retry: [] }>();
const text = (pl: string, en: string) => (props.locale === 'pl' ? pl : en);
const fields: Record<string, readonly [string, string]> = {
  name: ['Nazwa', 'Name'],
  first_name: ['Imię', 'First name'],
  surname: ['Nazwisko', 'Surname'],
  login: ['Login', 'Login'],
  email: ['Adres e-mail', 'Email address'],
  verified: ['Adres potwierdzony', 'Email verified'],
  has_avatar: ['Zdjęcie profilowe', 'Profile image'],
  category: ['Powiadomienie', 'Notification'],
  enabled: ['Włączone', 'Enabled'],
  recipients: ['Odbiorcy', 'Recipients'],
  notification_overrides: [
    'Zmienione ustawienia powiadomień',
    'Notification overrides',
  ],
  active: ['Konto aktywne', 'Account active'],
  deleted_at: ['Usunięto', 'Deleted at'],
};
const categories: Record<string, readonly [string, string]> = {
  'runa.auth.new_browser': ['Nowa przeglądarka', 'New browser'],
  'runa.auth.failed_login': ['Nieudane logowanie', 'Failed login'],
  'runa.auth.locked': ['Blokada logowania', 'Login lock'],
  'runa.auth.password_changed': ['Zmiana hasła', 'Password changed'],
  'runa.auth.account_expiry': ['Wygaśnięcie konta', 'Account expiry'],
  'runa.auth.password_expiry': ['Wygaśnięcie hasła', 'Password expiry'],
};
function label(pair: readonly [string, string] | undefined, fallback: string) {
  return pair ? text(pair[0], pair[1]) : fallback;
}
function value(v: HistoryValue): string {
  if (v === null) return '—';
  if (typeof v === 'boolean') return v ? text('Tak', 'Yes') : text('Nie', 'No');
  if (Array.isArray(v))
    return v.length ? v.map((item) => value(item)).join(', ') : '—';
  return label(categories[String(v)], String(v));
}
</script>
<template>
  <Dialog
    :model-value="open"
    :title="text('Szczegóły zdarzenia', 'Event details')"
    :locale="locale"
    size="lg"
    @update:model-value="!$event && $emit('close')"
  >
    <Spinner
      v-if="loading"
      :label="text('Wczytywanie szczegółów', 'Loading details')"
    />
    <Alert v-else-if="failed" tone="danger">
      {{
        text(
          'Nie udało się pobrać szczegółów zdarzenia.',
          'Event details could not be loaded.',
        )
      }}
      <Button variant="outline" @click="$emit('retry')">{{
        text('Ponów', 'Retry')
      }}</Button>
    </Alert>
    <div v-else-if="detail" class="space-y-5" data-history-detail>
      <p class="font-semibold">
        {{
          label(
            historyEventNames[detail.action],
            text('Zdarzenie konta', 'Account event'),
          )
        }}
      </p>
      <dl class="grid grid-cols-[auto_1fr] gap-x-4 gap-y-2 text-sm">
        <dt>{{ text('Data', 'Date') }}</dt>
        <dd>
          {{
            new Date(detail.occurred_at).toLocaleString(
              locale === 'pl' ? 'pl-PL' : 'en-GB',
            )
          }}
        </dd>
        <dt>{{ text('Wynik', 'Outcome') }}</dt>
        <dd>
          {{
            detail.outcome === 'success'
              ? text('Powodzenie', 'Success')
              : detail.outcome === 'refused'
                ? text('Odmowa', 'Refused')
                : text('Zarejestrowano', 'Recorded')
          }}
        </dd>
      </dl>
      <div v-if="detail.changes.length" class="space-y-4">
        <dl
          v-for="change in detail.changes"
          :key="change.field"
          class="border-t border-line pt-4 text-sm"
        >
          <dt class="mb-3 font-semibold">
            {{ label(fields[change.field], change.field) }}
          </dt>
          <dd class="grid min-w-0 gap-3 sm:grid-cols-2">
            <div>
              <p class="mb-1 text-xs text-muted">
                {{ text('Przed', 'Before') }}
              </p>
              <p class="[overflow-wrap:anywhere]">{{ value(change.before) }}</p>
            </div>
            <div>
              <p class="mb-1 text-xs text-muted">{{ text('Po', 'After') }}</p>
              <p class="[overflow-wrap:anywhere]">{{ value(change.after) }}</p>
            </div>
          </dd>
        </dl>
      </div>
      <p
        v-else-if="
          !detail.action.startsWith('password.') &&
          detail.action !== 'two_factor.admin_reset'
        "
        class="text-sm leading-7 text-muted"
      >
        {{
          text(
            'To zdarzenie nie zawiera zapisanych wartości przed i po zmianie.',
            'This event has no recorded before and after values.',
          )
        }}
      </p>
      <p
        v-if="
          detail.action.startsWith('password.') ||
          detail.action === 'two_factor.admin_reset'
        "
        class="text-sm leading-7 text-muted"
      >
        {{
          text(
            'Zarejestrowano operację bezpieczeństwa. Hasła, kody i tokeny nie są zapisywane w szczegółach historii.',
            'The security operation was recorded. Passwords, codes and tokens are never stored in history details.',
          )
        }}
      </p>
    </div>
    <template #footer
      ><Button variant="outline" @click="$emit('close')">{{
        text('Zamknij', 'Close')
      }}</Button></template
    >
  </Dialog>
</template>
