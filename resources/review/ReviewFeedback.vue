<script setup lang="ts">
import { ref } from 'vue';
import {
  Alert,
  Badge,
  Status,
  Button,
  Tooltip,
  Popover,
  ToastViewport,
  useToasts,
} from '@/runa/ui';
import type { FeedbackTone, BadgeTone } from '@/runa/ui';
defineProps<{ english: boolean }>();
const tones: FeedbackTone[] = ['success', 'info', 'warning', 'danger'];
const badgeTones: BadgeTone[] = ['neutral', ...tones];
const titles = {
  pl: {
    success: 'Zapisano zmiany',
    info: 'Informacja',
    warning: 'Sprawdź dane',
    danger: 'Nie zapisano zmian',
  },
  en: {
    success: 'Changes saved',
    info: 'Information',
    warning: 'Check the data',
    danger: 'Changes could not be saved',
  },
};
const labels = {
  pl: {
    neutral: 'Szkic',
    success: 'Gotowe',
    info: 'W toku',
    warning: 'Do sprawdzenia',
    danger: 'Błąd',
  },
  en: {
    neutral: 'Draft',
    success: 'Ready',
    info: 'In progress',
    warning: 'Review needed',
    danger: 'Error',
  },
};
const dismissed = ref(false);
const retry = ref(0);
const persistentError = ref(false);
const { messages, show, dismiss } = useToasts();
function notify(tone: FeedbackTone, english: boolean) {
  if (tone === 'danger') persistentError.value = true;
  show({
    title: titles[english ? 'en' : 'pl'][tone],
    tone,
    description: english
      ? 'Hover or focus to pause. Close with ×.'
      : 'Najedź lub ustaw fokus, aby wstrzymać czas. Zamknij przyciskiem ×.',
  });
}
</script>
<template>
  <div class="space-y-10">
    <section class="space-y-4">
      <h2 class="text-lg font-semibold">
        {{ english ? 'Inline messages' : 'Komunikaty w treści' }}
      </h2>
      <Alert
        v-for="tone in tones"
        :key="tone"
        :tone="tone"
        :title="titles[english ? 'en' : 'pl'][tone]"
        :announce="false"
        >{{
          english
            ? 'A short message with the next step. Meaning is conveyed by text and symbol as well as color.'
            : 'Krótki komunikat z następnym krokiem. Znaczenie przekazuje tekst i symbol, nie tylko kolor.'
        }}</Alert
      >
      <Alert
        v-if="!dismissed"
        dismissible
        :title="english ? 'Dismissible message' : 'Komunikat z zamykaniem'"
        :close-label="english ? 'Close message' : 'Zamknij komunikat'"
        @close="dismissed = true"
        >{{
          english
            ? 'Closing only affects this example.'
            : 'Zamknięcie dotyczy tylko tego przykładu.'
        }}<template #actions
          ><Button size="sm" @click="retry++"
            >{{ english ? 'Try again' : 'Spróbuj ponownie' }} ·
            {{ retry }}</Button
          ></template
        ></Alert
      >
      <Button v-else @click="dismissed = false">{{
        english ? 'Show the message again' : 'Pokaż komunikat ponownie'
      }}</Button>
    </section>
    <section class="space-y-4">
      <h2 class="text-lg font-semibold">
        {{ english ? 'Badges and statuses' : 'Etykiety i statusy' }}
      </h2>
      <p class="text-sm text-muted">
        {{
          english
            ? 'Badges label records or counts; statuses pair a dot with readable text.'
            : 'Etykiety oznaczają rekordy lub liczniki; status łączy kropkę z czytelnym opisem.'
        }}
      </p>
      <div
        v-for="size in ['sm', 'md'] as const"
        :key="size"
        class="flex flex-wrap items-center gap-3"
      >
        <Badge
          v-for="tone in badgeTones"
          :key="tone"
          :size="size"
          :tone="tone"
          >{{ labels[english ? 'en' : 'pl'][tone] }}</Badge
        ><Badge :size="size">12</Badge>
      </div>
      <div class="flex flex-wrap gap-5">
        <Status v-for="tone in badgeTones" :key="tone" :tone="tone">{{
          labels[english ? 'en' : 'pl'][tone]
        }}</Status>
      </div>
    </section>
    <section class="space-y-4">
      <h2 class="text-lg font-semibold">
        {{ english ? 'Temporary notifications' : 'Powiadomienia chwilowe' }}
      </h2>
      <p class="text-sm text-muted">
        {{
          english
            ? 'Shown at the top right. Ordinary notifications close after 5 seconds; errors stay until closed, with their message kept inline.'
            : 'Pojawiają się w prawym górnym rogu. Zwykłe znikają po 5 sekundach; błędy czekają na zamknięcie, a ich treść pozostaje też na stronie.'
        }}
      </p>
      <div class="flex flex-wrap gap-3">
        <Button
          v-for="tone in tones"
          :key="tone"
          :tone="tone"
          variant="outline"
          @click="notify(tone, english)"
          >{{ titles[english ? 'en' : 'pl'][tone] }}</Button
        >
      </div>
      <Alert
        v-if="persistentError"
        tone="danger"
        :title="titles[english ? 'en' : 'pl'].danger"
        >{{
          english
            ? 'Your changes are still available. Retry after checking the connection.'
            : 'Twoje zmiany są nadal dostępne. Sprawdź połączenie i spróbuj ponownie.'
        }}</Alert
      >
    </section>
    <section class="space-y-4">
      <h2 class="text-lg font-semibold">
        {{ english ? 'Tooltips and popovers' : 'Tooltipy i popovery' }}
      </h2>
      <p class="text-sm text-muted">
        {{
          english
            ? 'A tooltip gives a short description on hover or keyboard focus. A popover opens on click and holds extra content. Escape closes the top layer.'
            : 'Tooltip to krótki opis po najechaniu lub fokusie klawiatury. Popover otwiera dodatkową treść po kliknięciu. Escape zamyka wierzchnią warstwę.'
        }}
      </p>
      <div class="flex flex-wrap items-start gap-4">
        <Tooltip
          :text="
            english
              ? 'Short description of the action'
              : 'Krótki opis działania'
          "
          ><template #default="{ descriptionId }"
            ><Button :aria-describedby="descriptionId">{{
              english ? 'Hover or focus' : 'Najedź lub ustaw fokus'
            }}</Button></template
          ></Tooltip
        >
        <Popover
          :label="english ? 'Additional information' : 'Dodatkowe informacje'"
          ><template #trigger="{ toggle, open, panelId }"
            ><Button
              :aria-expanded="open"
              :aria-controls="panelId"
              aria-haspopup="dialog"
              @click="toggle"
              >{{ english ? 'Open popover' : 'Otwórz popover' }}</Button
            ></template
          ><template #default="{ close }"
            ><h3 class="font-semibold">
              {{ english ? 'Additional information' : 'Dodatkowe informacje' }}
            </h3>
            <p class="my-3">
              {{
                english
                  ? 'Useful context for this place on the page.'
                  : 'Przydatny kontekst dla tego miejsca na stronie.'
              }}
            </p>
            <Button size="sm" @click="close()">{{
              english ? 'Close' : 'Zamknij'
            }}</Button></template
          ></Popover
        >
      </div>
    </section>
    <ToastViewport
      :messages="messages"
      :label="english ? 'Notifications' : 'Powiadomienia'"
      :close-label="english ? 'Close notification' : 'Zamknij powiadomienie'"
      @dismiss="dismiss"
    />
  </div>
</template>
