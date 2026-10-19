# Form Patterns

Public imports use `@/runa/ui`. Form extends the existing AppForm; FormSection, FormField, TextInput, NumberInput and useForm supply shell-independent composition. There are no business forms or backend write endpoints yet. Both accepted layouts contain no provisional text/numeric form fields to replace; root preferences remain buttons. ApplicationReview's Select toolbar now shares the normalized field appearance; richer field/selection APIs remain in point 14.

## Container and field composition

Form always uses novalidate, prevents native navigation and blocks submission when busy or valid=false. Busy disables the contained fieldset, including actions. Props also provide dirty metadata, accessible label, description and 1/2/3 responsive columns. Slots are default fields, feedback and actions. It emits submit(SubmitEvent) or invalid. After synchronous validation it focuses the first aria-invalid control. Exposed focusInvalid() focuses that control or the feedback wrapper for async failures; call after nextTick when applying server errors.

FormSection is a labeled fieldset with description, responsive columns and optional controlled collapsed state (update:collapsed). Collapsing hides but does not unmount inputs. FormField supplies a visible label, stable supplied/generated control ID, required marker and helper/warning/error descriptions; its slot receives id/describedBy/invalid for custom controls. It does not guess field semantics or business validation. Error messages may be a string or readonly list. Pass exact dot keys such as errors['contacts.0.email'] when binding nested backend errors.

TextInput composes FormField with text/textarea/password/email/url/tel semantics. v-model is string|null; nullable opt-in emits null on empty, otherwise empty string. Native attributes/listeners (name, autocomplete, maxlength, rows, blur, etc.) are forwarded to the control; external aria-describedby is merged with field descriptions. Props include required/disabled/readonly/helper/warning/error. Preserve explicit autofill intent; no implicit password-reveal workflow. Email/url/tel preserve native type semantics and mobile keyboard hints, not automatic masks or feature validation. With novalidate, the owning feature must present errors explicitly; email in the review form does so, while the type inventory demonstrates semantic controls. IME input waits for composition end, with no formatting/cursor rewrite. Readonly/disabled controls do not emit edits. Use controlled inline errors and the form's novalidate; browser validation bubbles do not drive UX. TextInput/NumberInput/textarea and the existing Select share .runa-field-control styling: appearance:none, no detached outer focus ring, a visible internal focus border, theme text selection and state-aware autofill paint. Error/focus borders take precedence over hover; keep hover selector specificity low. Preserve autocomplete/password managers; do not use delayed-color transition hacks or disable saved credentials. Forced-colors retains a system outline. Future field implementations should compose this shared field treatment where applicable.

## Exact numeric values

NumberInput wraps TextInput with integer/decimal/currency/percentage kind, pl/en locale, optional scale and unit. Input mode is numeric/decimal; model remains string|null, never a binary float. Canonical complete values use a decimal dot. Invalid/incomplete drafts (for example '-') are emitted intact and marked invalid, rather than retaining a stale valid model. validity emits parsing state; the submit validator must validate/serialize the current model. Empty is null; required/range/business rules belong to the feature.

parseDecimal returns a discriminated valid/value or invalid/reason result. serializeDecimal throws for invalid input; apply it at the request boundary after validation. formatDecimal substitutes the PL comma/EN dot exactly, preserving fractional zeros. Optional scale limits digits without rounding. Currency and percentage do not introduce automatic scaling, currency conversion, fixed precision or a 0–100 business limit. Integer strings may exceed Number.MAX_SAFE_INTEGER. Group separators/exponents are rejected; grouped display and cursor masks remain the later mask phase. The control preserves raw editing/cursor state and formats only on blur or an external value/locale replacement. Unit text is a noneditable attached suffix beside the input (for example PLN or %), participates in aria-describedby and never enters the numeric model. TextInput exposes the shared suffix slot so NumberInput does not duplicate field markup.

## Values, dirty baselines and request failures

useForm accepts finite JSON form data only (no Date/File/classes/cycles/nonfinite values); file controls later expose their own file-state contract. It clones values/baselines, compares nested values independently of object key order, and exposes values/errors/busy/valid/dirty/failure. markClean(snapshot?) advances the baseline without overwriting edits. reset(snapshot?) restores the last confirmed baseline or explicitly supplied server data and clears failures/errors. Keep password/other sensitive values in local memory, never in browser preference storage or logs.

submit(action, validate?) validates first, blocks known errors/concurrent submissions, passes an isolated snapshot and holds busy until settlement. Success marks only that submitted snapshot clean, so programmatic edits during the request remain dirty. clearErrors(name?) and setError(name, messages) support feature-owned immediate validation. Validators should return fresh FieldErrors; preserve backend authority. No domain constraints are embedded in primitives.

ApiError 422 maps validated string-array errors using exact Laravel dot keys; 409 sets failure=conflict, and other errors set unexpected. Input/baselines survive failures. A screen must display operation-wide errors as well as inline messages, focus the relevant error and offer explicit conflict comparison/reload/retry. Never silently overwrite a draft on 409 or invent a successful retry. reset(serverSnapshot) is the deliberate reload seam; optimistic-lock tokens and server reconciliation remain owned by the feature. The catalog demonstrates local fixtures only, including a newer sample snapshot.

Optional route/beforeunload dirty guards, masks, cascading fields and wizards remain in point 15. No guards, business write APIs or server drafts are introduced here.

```ts
const { values, errors, busy, dirty, submit } = useForm({
  title: '',
  amount: null as string | null,
});
// The feature supplies saveToBackend and its own validation rules.
async function save() {
  return submit(
    async (snapshot) => {
      await saveToBackend({
        ...snapshot,
        amount: serializeDecimal(snapshot.amount, { scale: 2 }),
      });
    },
    (snapshot) =>
      parseDecimal(snapshot.amount, { scale: 2 }).valid
        ? {}
        : { amount: ['Podaj poprawną kwotę.'] },
  );
}
```

```vue
<Form :busy="busy" :dirty="dirty" @submit="save">
  <TextInput v-model="values.title" label="Nazwa" :error="errors.title ?? []" />
  <NumberInput v-model="values.amount" label="Kwota" kind="currency"
    :scale="2" locale="pl" unit="PLN" :error="errors.amount ?? []"
    invalid-message="Podaj poprawną kwotę." />
  <template #actions><Button type="submit" :busy="busy">Zapisz</Button></template>
</Form>
```

Autofill browser constraints: [MDN :autofill](https://developer.mozilla.org/en-US/docs/Web/CSS/Reference/Selectors/:autofill) documents UA-important background/text rules. The shared field paints its theme surface with an inset shadow and sets text-fill/caret colors while retaining the internal focus indicator; it does not attempt to replace browser-owned suggestion UI.

## Additional field values and incomplete drafts

DateInput stores YYYY-MM-DD/null; TimeInput stores local HH:mm with optional seconds/fractions/null. DateRangeInput stores { start, end }, permits open/null endpoints and rejects reversed/invalid dates. formatDateOnly formats PL/EN without timezone conversion. DateTimeInput requires an IANA timeZone; toLocalDateTime displays an explicit-offset instant in that zone and resolveLocalDateTime commits UTC ISO values. Nonexistent DST wall times reject; repeated times reject by default, requiring an explicit earlier/later disambiguation policy. Never silently select an occurrence or shift a date-only value through UTC.

Temporal inputs preserve native partial segment editing independently of committed values. Consume validity to block submission of a stale last-valid model, and use reset() when deliberately discarding an incomplete draft (including when the model itself did not change). Their focus() method supports recovery. DateRangeInput combines endpoint and order validity. Supply localized invalidMessage/zoneLabel and linked helper text. TagsInput likewise reports validity=false while a nonempty draft awaits Enter; prevent submission or deliberately commit/discard it. ColorInput retains invalid drafts in its string/null model and emits validity. Compose these flags into Form.valid; feature rules still validate required/domain constraints.

FileInput stores File objects in separate shallowRef state, outside useForm's finite-JSON snapshots. Its issues event exposes count/size/type failures without truncating selection. Reset/removal is controlled through the model; clearing the native picker after selection allows choosing the same file again. Names/MIME/accept are untrusted UX hints: backend upload authorization, type/content/size validation and storage remain feature-owned. No file reads, previews, object URLs or uploads occur in the primitive.

Checkbox supports an indeterminate null value; Switch uses boolean. RadioGroup uses explicit string option identities and native arrow navigation. Color is six-digit hexadecimal without alpha. Tags are plain trimmed strings, with caller-owned limits and localization; there is no remote suggestion provider. HiddenInput does not protect its value from modification.
