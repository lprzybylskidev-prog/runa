<script setup lang="ts">
import { computed, nextTick, onScopeDispose, ref } from 'vue';
import {
  Alert,
  ConfirmDialog,
  Button,
  Card,
  Checkbox,
  Form,
  MaskedInput,
  Select,
  TextInput,
  Wizard,
  useDependentFields,
  useDirtyGuard,
  useForm,
  usePageResolver,
  useWizard,
  type WizardStep,
} from '@/runa/ui';
const props = defineProps<{ english: boolean }>();
const text = (pl: string, en: string) => (props.english ? en : pl);
const locale = computed(() =>
  props.english ? ('en' as const) : ('pl' as const),
);
const postal = ref<string | null>(null);
const phone = ref<string | null>(null);
const formatted = ref('');
const complete = ref(false);
const fail = ref(false);
const dependencyValues = ref<Record<string, string>>({
  country: '',
  city: '',
  office: '',
});
function delay(signal: globalThis.AbortSignal) {
  return new Promise<void>((resolve, reject) => {
    const timer = globalThis.setTimeout(resolve, 650);
    signal.addEventListener(
      'abort',
      () => {
        globalThis.clearTimeout(timer);
        reject(new Error('Aborted'));
      },
      { once: true },
    );
  });
}
const { state, retry, cancel } = useDependentFields(
  [
    {
      key: 'country',
      options: [
        { value: 'pl', label: 'Polska / Poland' },
        { value: 'de', label: 'Niemcy / Germany' },
      ],
    },
    {
      key: 'city',
      dependsOn: ['country'],
      load: async ({ parents, signal }) => {
        await delay(signal);
        if (fail.value) throw new Error('Scenario failure');
        return parents.country === 'pl'
          ? [
              { value: 'warsaw', label: 'Warszawa' },
              { value: 'krakow', label: 'Kraków' },
            ]
          : [{ value: 'berlin', label: 'Berlin' }];
      },
    },
    {
      key: 'office',
      dependsOn: ['country', 'city'],
      load: async ({ parents, signal }) => {
        await delay(signal);
        return [
          { value: `${parents.city}-central`, label: 'Centrum / Central' },
          { value: `${parents.city}-north`, label: 'Północ / North' },
        ];
      },
    },
  ],
  dependencyValues,
);
const { values, errors, dirty, setError, clearErrors, markClean, reset } =
  useForm({ name: '', phone: null as string | null, notes: '' });
const guardEnabled = ref(true);
const leavePending = ref(false);
const left = ref(false);
let answer: ((value: boolean) => void) | undefined;
const guard = useDirtyGuard({
  dirty,
  enabled: guardEnabled,
  confirmLeave: () =>
    new Promise<boolean>((resolve) => {
      answer = resolve;
      leavePending.value = true;
    }),
});
const route = usePageResolver(
  [
    {
      routeKey: 'advanced-review',
      moduleKey: 'review',
      href: '/__fixtures/ui',
      title: 'Advanced forms',
      serverRouteName: 'test.ui',
    },
  ],
  { beforeLeave: guard.beforeLeave },
);
function respond(allow: boolean) {
  leavePending.value = false;
  answer?.(allow);
  answer = undefined;
}
onScopeDispose(() => respond(false));
const submitFail = ref(false);
const wizardElement = ref<InstanceType<typeof Wizard>>();
const steps = computed<WizardStep[]>(() => [
  { key: 'identity', label: text('Dane', 'Details') },
  { key: 'contact', label: text('Kontakt', 'Contact') },
  { key: 'review', label: text('Podsumowanie', 'Summary') },
]);
const wizard = useWizard(steps.value, {
  validate: async (key) => {
    if (key === 'identity') {
      clearErrors('name');
      if (!values.value.name.trim())
        setError('name', text('Podaj nazwę.', 'Enter a name.'));
    }
    if (key === 'contact') {
      clearErrors('phone');
      if ((values.value.phone ?? '').length !== 9)
        setError(
          'phone',
          text('Podaj 9 cyfr telefonu.', 'Enter 9 phone digits.'),
        );
    }
    return key === 'identity'
      ? !errors.value.name?.length
      : key === 'contact'
        ? !errors.value.phone?.length
        : true;
  },
  submit: async () => {
    await new Promise((resolve) => globalThis.setTimeout(resolve, 500));
    if (submitFail.value) return false;
    markClean();
    return true;
  },
});
async function go(index: number) {
  if (!(await wizard.go(index))) {
    await nextTick();
    wizardElement.value?.focusInvalid();
  }
}
async function next() {
  await go(wizard.index.value + 1);
}
async function finish() {
  if (!(await wizard.finish())) {
    await nextTick();
    wizardElement.value?.focusInvalid();
  }
}
async function leave() {
  await guard.requestLeave(() => {
    left.value = true;
  });
}
</script>
<template>
  <div class="space-y-6">
    <Card
      ><template #header
        ><h2>
          {{
            text(
              'Maski · wartość surowa i prezentacja',
              'Masks · raw values and presentation',
            )
          }}
        </h2></template
      >
      <Form :columns="2" :label="text('Przykłady masek', 'Mask examples')">
        <MaskedInput
          v-model="postal"
          pattern="##-###"
          :label="text('Kod pocztowy', 'Postal code')"
          inputmode="numeric"
          autocomplete="postal-code"
          placeholder="00-000"
          :helper="
            text(
              'Formatowanie nie sprawdza istnienia kodu.',
              'Formatting does not validate whether the code exists.',
            )
          "
          @formatted="formatted = $event"
          @completeness="complete = $event"
        />
        <MaskedInput
          v-model="phone"
          pattern="### ### ###"
          :label="text('Telefon', 'Phone')"
          inputmode="tel"
          autocomplete="tel-national"
          placeholder="123 456 789"
        />
        <output class="break-all text-xs text-muted"
          >{{ text('Surowa', 'Raw') }}: {{ postal ?? 'null' }} ·
          {{ text('Prezentacja', 'Formatted') }}: {{ formatted || '—' }} ·
          {{ text('Kompletna', 'Complete') }}: {{ complete }}</output
        >
      </Form>
    </Card>
    <Card
      ><template #header
        ><h2>
          {{
            text(
              'Pola zależne · trzy poziomy',
              'Dependent fields · three levels',
            )
          }}
        </h2></template
      >
      <div class="space-y-5">
        <Checkbox
          v-model="fail"
          :label="
            text(
              'Symuluj błąd pobierania miast',
              'Simulate city loading failure',
            )
          "
        />
        <div class="grid gap-5 md:grid-cols-3">
          <Select
            v-model="dependencyValues.country!"
            :label="text('Kraj', 'Country')"
            :options="state.country!.options"
            clearable
            :locale="locale"
          />
          <div class="space-y-2">
            <Select
              v-model="dependencyValues.city!"
              :label="text('Miasto', 'City')"
              :options="state.city!.options"
              :disabled="!state.city!.ready"
              clearable
              :locale="locale"
            />
            <p
              v-if="state.city!.loading"
              role="status"
              class="text-xs text-muted"
            >
              {{ text('Ładowanie…', 'Loading…') }}
            </p>
            <Button v-if="state.city!.loading" @click="cancel('city')">{{
              text('Anuluj pobieranie', 'Cancel loading')
            }}</Button>
            <Alert v-if="state.city!.error" tone="danger">{{
              text('Nie udało się pobrać miast.', 'Unable to load cities.')
            }}</Alert>
            <Button
              v-if="
                dependencyValues.country &&
                !state.city!.loading &&
                !state.city!.ready
              "
              @click="retry('city')"
              >{{ text('Ponów pobieranie', 'Retry loading') }}</Button
            >
          </div>
          <div class="space-y-2">
            <Select
              v-model="dependencyValues.office!"
              :label="text('Biuro', 'Office')"
              :options="state.office!.options"
              :disabled="!state.office!.ready"
              clearable
              :locale="locale"
            />
            <p
              v-if="state.office!.loading"
              role="status"
              class="text-xs text-muted"
            >
              {{ text('Ładowanie…', 'Loading…') }}
            </p>
          </div>
        </div>
        <p class="text-xs text-muted">
          {{
            text(
              'Zmiana kraju czyści miasto i biuro. Zmiana miasta czyści biuro.',
              'Changing country clears city and office. Changing city clears office.',
            )
          }}
        </p>
      </div>
    </Card>
    <Card
      ><template #header
        ><h2>
          {{ text('Kreator', 'Wizard') }}
        </h2></template
      >
      <div class="space-y-5">
        <Form
          :dirty="dirty"
          :busy="wizard.busy.value"
          :label="text('Formularz wieloetapowy', 'Multistep form')"
          @submit="wizard.index.value === 2 ? finish() : next()"
        >
          <Wizard
            ref="wizardElement"
            :steps="steps"
            :model-value="wizard.index.value"
            :label="text('Kroki formularza', 'Form steps')"
            :busy="wizard.busy.value"
            :invalid="wizard.failed.value"
            :locale="locale"
            @update:model-value="go"
            @next="next"
            @previous="wizard.previous"
            @finish="finish"
          >
            <template #default="{ index }">
              <TextInput
                v-if="index === 0"
                v-model="values.name"
                :label="text('Nazwa', 'Name')"
                :error="errors.name ?? []"
                required
                autocomplete="organization"
                @update:model-value="clearErrors('name')"
              />
              <MaskedInput
                v-else-if="index === 1"
                v-model="values.phone"
                pattern="### ### ###"
                :label="text('Telefon kontaktowy', 'Contact phone')"
                inputmode="tel"
                :error="errors.phone ?? []"
                required
                @update:model-value="clearErrors('phone')"
              />
              <div v-else class="space-y-4">
                <p class="break-all text-sm">
                  {{ values.name }} · {{ values.phone }}
                </p>
                <TextInput
                  v-model="values.notes"
                  type="textarea"
                  :label="text('Uwagi', 'Notes')"
                />
              </div>
            </template>
            <template #feedback
              ><Alert v-if="wizard.failed.value" tone="danger">{{
                text(
                  'Sprawdź dane lub ponów zakończenie. Wartości zostały zachowane.',
                  'Check the fields or retry completion. Values have been preserved.',
                )
              }}</Alert
              ><Alert v-if="wizard.complete.value" tone="success">{{
                text(
                  'Lokalny przykład zakończony. Dane nie zostały wysłane.',
                  'Local example completed. No data was sent.',
                )
              }}</Alert></template
            >
          </Wizard>
        </Form>
      </div>
    </Card>
    <Card>
      <template #header
        ><h2>
          {{
            text(
              'Sterowanie testowe kreatora i ochrony zmian',
              'Wizard and unsaved changes test controls',
            )
          }}
        </h2></template
      >
      <div class="space-y-5">
        <Checkbox
          v-model="guardEnabled"
          :label="
            text(
              'Ostrzegaj o niezapisanych zmianach',
              'Warn about unsaved changes',
            )
          "
        />
        <Checkbox
          v-model="submitFail"
          :label="
            text('Symuluj błąd zakończenia', 'Simulate completion failure')
          "
        />
        <p class="text-xs text-muted">
          {{ text('Niezapisane zmiany', 'Unsaved changes') }}: {{ dirty }}
        </p>
        <div class="flex flex-wrap gap-3">
          <Button
            :disabled="guard.pending.value || wizard.busy.value"
            @click="leave"
            >{{
              text('Spróbuj opuścić formularz', 'Try leaving the form')
            }}</Button
          ><Button
            :disabled="wizard.busy.value"
            @click="
              reset();
              wizard.go(0);
              left = false;
            "
            >{{
              text('Przywróć zapisane wartości', 'Restore saved values')
            }}</Button
          >
        </div>
        <ConfirmDialog
          :model-value="leavePending"
          :title="
            text(
              'Opuścić formularz z niezapisanymi zmianami?',
              'Leave with unsaved changes?',
            )
          "
          :cancel-label="text('Zostań', 'Stay')"
          :confirm-label="text('Opuść', 'Leave')"
          :locale="locale"
          confirm-tone="warning"
          @update:model-value="!$event && respond(false)"
          @confirm="respond(true)"
        />
        <a
          href="/__fixtures/ui?category=advanced#guarded"
          class="text-xs underline"
          @click="
            route.navigate('/__fixtures/ui?category=advanced#guarded', $event)
          "
          >{{
            text(
              'Przejdź pod chroniony adres podglądu',
              'Navigate to guarded preview address',
            )
          }}</a
        >
        <p class="text-xs text-muted">
          {{
            text(
              'Link sprawdza ochronę nawigacji. Wstecz w przeglądarce także wymaga decyzji przy niezapisanych zmianach.',
              'The link exercises navigation protection. Browser Back also requires a decision with unsaved changes.',
            )
          }}
        </p>
        <p v-if="left" role="status" class="text-xs text-muted">
          {{
            text(
              'Próba opuszczenia została zaakceptowana. Podgląd pozostaje otwarty.',
              'Leaving was allowed. This preview remains open.',
            )
          }}
        </p>
      </div>
    </Card>
  </div>
</template>
