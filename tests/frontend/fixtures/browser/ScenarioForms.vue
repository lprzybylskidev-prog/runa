<script setup lang="ts">
import { computed, nextTick, ref } from 'vue';
import {
  Form,
  FormSection,
  TextInput,
  NumberInput,
  Button,
  Alert,
  Card,
  useForm,
  parseDecimal,
  serializeDecimal,
} from '@/runa/ui';
import type { FieldErrors, NumericKind, TextInputType } from '@/runa/ui';
import { ApiError } from '@/lib/http';
const smallText = ref<string | null>('');
const smallNumber = ref<string | null>('12');
const props = defineProps<{ english: boolean }>();
const text = (pl: string, en: string) => (props.english ? en : pl);
const locale = computed(() =>
  props.english ? ('en' as const) : ('pl' as const),
);
const {
  values,
  errors,
  busy,
  failure,
  dirty,
  reset,
  clearErrors,
  setError,
  submit: submitValues,
} = useForm({ name: '', email: '', budget: null as string | null, notes: '' });
const formElement = ref<InstanceType<typeof Form>>();
const collapsed = ref(false);
const outcome = ref('success');
const saved = ref(false);
const payload = ref('');
const sampleTexts = ref<Record<string, string | null>>({});
const samples: readonly TextInputType[] = [
  'text',
  'textarea',
  'password',
  'email',
  'url',
  'tel',
];
const sampleNumbers = ref<Record<string, string | null>>({
  integer: '12345678901234567890',
  decimal: '123.4500',
  currency: '199.99',
  percentage: '12.5',
});
const kinds: readonly NumericKind[] = [
  'integer',
  'decimal',
  'currency',
  'percentage',
];
const stateValue = ref<string | null>('Runa');
const stateLabels = computed(() => ({
  name: text('Nazwa', 'Name'),
  email: text('E-mail', 'Email'),
  budget: text('Budżet', 'Budget'),
  notes: text('Notatki', 'Notes'),
}));
function validate(): FieldErrors {
  const result: FieldErrors = {};
  if (!values.value.name.trim())
    result.name = [text('Podaj nazwę.', 'Enter a name.')];
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(values.value.email))
    result.email = [
      text('Podaj poprawny adres e-mail.', 'Enter a valid email address.'),
    ];
  if (!parseDecimal(values.value.budget, { scale: 2 }).valid)
    result.budget = [
      text(
        'Podaj kwotę z maksymalnie 2 miejscami po przecinku.',
        'Enter an amount with at most 2 decimal places.',
      ),
    ];
  return result;
}
function validateField(name: 'name' | 'email') {
  const messages = validate()[name];
  if (messages?.length) setError(name, messages);
}
async function save() {
  saved.value = false;
  const result = await submitValues(async (snapshot) => {
    await new Promise((resolve) => window.setTimeout(resolve, 800));
    if (outcome.value === '422')
      throw new ApiError(422, {
        errors: {
          email: [
            text(
              'Ten adres jest już zajęty w przykładzie.',
              'This address is already used in this example.',
            ),
          ],
          'contacts.0.email': [
            text('Sprawdź adres kontaktu.', 'Check the contact email.'),
          ],
        },
      });
    if (outcome.value === '409')
      throw new ApiError(409, { message: 'Conflict' });
    if (outcome.value === '500') throw new ApiError(500, null);
    payload.value = JSON.stringify(
      { ...snapshot, budget: serializeDecimal(snapshot.budget, { scale: 2 }) },
      null,
      2,
    );
  }, validate);
  saved.value = result;
  if (!result) {
    await nextTick();
    formElement.value?.focusInvalid();
  }
}
const statuses = computed(() => [
  { value: 'success', label: text('Zapis', 'Save') },
  { value: '422', label: '422' },
  { value: '409', label: '409' },
  { value: '500', label: '500' },
]);
</script>
<template>
  <div class="space-y-6">
    <Card
      ><template #header
        ><h2>{{ text('Formularz', 'Form') }}</h2></template
      >
      <Form
        ref="formElement"
        :busy="busy"
        :dirty="dirty"
        :label="text('Przykładowy formularz', 'Sample form')"
        :description="
          text(
            'Lokalny przykład walidacji i odzyskiwania po błędzie. Bez żądań do API.',
            'Local validation and recovery example. No API requests.',
          )
        "
        @submit="save"
      >
        <template #feedback
          ><Alert
            v-if="failure === 'validation'"
            tone="danger"
            :title="
              text('Popraw wskazane pola', 'Correct the indicated fields')
            "
            ><ul class="space-y-1">
              <li v-for="(messages, key) in errors" :key="key">
                {{ key }}: {{ messages.join(' ') }}
              </li>
            </ul></Alert
          ><Alert
            v-else-if="failure === 'conflict'"
            tone="warning"
            :title="text('Dane zostały zmienione', 'The data has changed')"
            ><p>
              {{
                text(
                  'Twoje wpisy zachowano. Porównaj je z nowszą wersją przed ponownym zapisem.',
                  'Your entries are preserved. Compare them with the newer version before saving again.',
                )
              }}
            </p>
            <p class="mt-3 text-xs">
              {{
                text(
                  'Nowsza przykładowa nazwa: Runa Studio.',
                  'Newer sample name: Runa Studio.',
                )
              }}
            </p>
            <template #actions
              ><Button
                @click="
                  reset({
                    name: 'Runa Studio',
                    email: 'studio@example.test',
                    budget: '2500.00',
                    notes: '',
                  });
                  outcome = 'success';
                "
                >{{
                  text('Wczytaj nowszą wersję', 'Load newer version')
                }}</Button
              ><Button @click="outcome = 'success'">{{
                text('Porównano — ponów zapis', 'Compared — retry save')
              }}</Button></template
            ></Alert
          ><Alert
            v-else-if="failure === 'unexpected'"
            tone="danger"
            :title="text('Zapis nie powiódł się', 'Save failed')"
            >{{
              text(
                'Wpisane dane zachowano. Spróbuj ponownie.',
                'Your entries are preserved. Try again.',
              )
            }}</Alert
          ><Alert
            v-else-if="saved"
            tone="success"
            :title="text('Zapisano przykład', 'Example saved')"
        /></template>
        <FormSection
          :title="text('Dane podstawowe', 'Basic information')"
          :columns="2"
          ><TextInput
            v-model="values.name"
            :label="stateLabels.name"
            name="name"
            autocomplete="organization"
            required
            :error="errors.name ?? []"
            @update:model-value="
              clearErrors('name');
              validateField('name');
            "
            @blur="validateField('name')" /><TextInput
            v-model="values.email"
            type="email"
            :label="stateLabels.email"
            name="email"
            autocomplete="email"
            required
            :error="errors.email ?? []"
            @update:model-value="
              clearErrors('email');
              validateField('email');
            "
            @blur="validateField('email')" /><NumberInput
            v-model="values.budget"
            :locale="locale"
            kind="currency"
            :scale="2"
            :label="stateLabels.budget"
            unit="PLN"
            :helper="
              text(
                'Kwota jest wysyłana jako dokładny tekst.',
                'The amount is sent as exact text.',
              )
            "
            :invalid-message="
              text(
                'Podaj poprawną kwotę (maks. 2 miejsca po przecinku).',
                'Enter a valid amount (max. 2 decimal places).',
              )
            "
            :error="errors.budget ?? []"
            @update:model-value="clearErrors('budget')"
        /></FormSection>
        <FormSection
          v-model:collapsed="collapsed"
          collapsible
          :title="text('Informacje dodatkowe', 'Additional information')"
          :description="
            text(
              'Zwinięcie zachowuje wpisane dane.',
              'Collapsing preserves entered data.',
            )
          "
          ><TextInput
            v-model="values.notes"
            type="textarea"
            :label="stateLabels.notes"
            name="notes"
            :rows="4"
            :helper="text('Pole opcjonalne.', 'Optional field.')"
        /></FormSection>
        <template #actions
          ><Button type="submit" variant="solid" :busy="busy">{{
            text('Zapisz przykład', 'Save example')
          }}</Button
          ><Button
            :disabled="busy"
            @click="
              reset();
              saved = false;
              payload = '';
            "
            >{{ text('Przywróć', 'Reset') }}</Button
          ><span class="text-xs text-muted" role="status">{{
            dirty
              ? text('Niezapisane zmiany', 'Unsaved changes')
              : text('Bez zmian', 'No changes')
          }}</span></template
        >
      </Form>
      <div
        role="group"
        :aria-label="text('Wynik przykładu', 'Example outcome')"
        class="mt-6 flex flex-wrap gap-2"
      >
        <Button
          v-for="status in statuses"
          :key="status.value"
          :disabled="busy"
          :aria-pressed="outcome === status.value"
          @click="outcome = status.value"
          >{{ status.label }}</Button
        >
      </div>
      <pre
        v-if="payload"
        class="mt-4 overflow-x-auto rounded bg-surface p-3 text-xs"
        >{{ payload }}</pre>
    </Card>
    <Card
      ><template #header
        ><h2>{{ text('Pola tekstowe', 'Text inputs') }}</h2></template
      >
      <div class="grid gap-5 md:grid-cols-2">
        <TextInput
          v-for="type in samples"
          :key="type"
          :model-value="sampleTexts[type] ?? ''"
          :label="type"
          :type="type"
          :autocomplete="type === 'password' ? 'new-password' : 'off'"
          @update:model-value="sampleTexts[type] = $event"
        /></div
    ></Card>
    <Card
      ><template #header
        ><h2>{{ text('Pola liczbowe', 'Numeric inputs') }}</h2></template
      >
      <div class="grid gap-5 md:grid-cols-2">
        <NumberInput
          v-for="kind in kinds"
          :key="kind"
          :model-value="sampleNumbers[kind] ?? null"
          :label="kind"
          :kind="kind"
          :locale="locale"
          :unit="kind === 'currency' ? 'PLN' : kind === 'percentage' ? '%' : ''"
          :invalid-message="
            text('Podaj poprawną liczbę.', 'Enter a valid number.')
          "
          @update:model-value="sampleNumbers[kind] = $event"
        /></div
    ></Card>
    <Card
      ><template #header
        ><h2>{{ text('Stany pól', 'Field states') }}</h2></template
      >
      <div class="grid gap-5 md:grid-cols-2">
        <TextInput
          v-model="stateValue"
          :label="text('Domyślne', 'Default')"
          :helper="
            text(
              'Opis pomocniczy pod polem.',
              'Helper description below the field.',
            )
          "
        /><TextInput
          :model-value="'Runa'"
          :label="text('Wyłączone', 'Disabled')"
          disabled
        /><TextInput
          :model-value="'Runa'"
          :label="text('Tylko do odczytu', 'Read only')"
          readonly
        /><TextInput
          :model-value="''"
          :label="text('Wymagane', 'Required')"
          required
        /><TextInput
          v-model="stateValue"
          :label="text('Ostrzeżenie', 'Warning')"
          :warning="
            text('Sprawdź dane przed zapisem.', 'Check the data before saving.')
          "
        /><TextInput
          v-model="stateValue"
          :label="text('Błąd', 'Invalid')"
          :error="
            text(
              'Przykładowy komunikat walidacji.',
              'Sample validation message.',
            )
          "
        /></div
    ></Card>
    <div class="grid gap-4 sm:grid-cols-2">
      <TextInput
        size="sm"
        :model-value="smallText"
        :label="text('Małe pole (sm)', 'Small input (sm)')"
        @update:model-value="smallText = $event"
      />
      <NumberInput
        v-model="smallNumber"
        size="sm"
        kind="integer"
        :locale="english ? 'en' : 'pl'"
        :label="text('Mała liczba (sm)', 'Small number (sm)')"
      />
    </div>
  </div>
</template>
