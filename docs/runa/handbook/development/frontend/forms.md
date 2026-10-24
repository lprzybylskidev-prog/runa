# Form Patterns

Public imports use `@/runa/ui`. Form extends the existing AppForm; FormSection, FormField, TextInput, NumberInput and useForm supply shell-independent composition. Auth entry/restricted and own-account forms consume these controls with guarded backend writes; see the [Auth entry contract](../../modules/auth/entry-ui.md). Both accepted layouts contain no provisional text/numeric form fields to replace; root preferences remain buttons. the isolated ApplicationScenario test fixture's Select toolbar now shares the normalized field appearance; selection modes now share the themed panel.

## Container and field composition

Form always uses novalidate, prevents native navigation and blocks submission when busy or valid=false. Busy disables the contained fieldset, including actions. Props also provide dirty metadata, accessible label, description and 1/2/3 responsive columns. Slots are default fields, feedback and actions. It emits submit(SubmitEvent) or invalid. After synchronous validation it focuses the first aria-invalid control. Exposed focusInvalid() focuses that control or the feedback wrapper for async failures; call after nextTick when applying server errors.

FormSection is a labeled fieldset with description, responsive columns and optional controlled collapsed state (update:collapsed). Collapsing hides but does not unmount inputs. FormField supplies a visible label, stable supplied/generated control ID, required marker and helper/warning/error descriptions; its slot receives id/describedBy/invalid for custom controls. It does not guess field semantics or business validation. Error messages may be a string or readonly list. Pass exact dot keys such as errors['contacts.0.email'] when binding nested backend errors.

TextInput composes FormField with text/textarea/password/email/url/tel semantics. v-model is string|null; nullable opt-in emits null on empty, otherwise empty string. Native attributes/listeners (name, autocomplete, maxlength, rows, blur, etc.) are forwarded to the control; external aria-describedby is merged with field descriptions. Props include required/disabled/readonly/helper/warning/error. Preserve explicit autofill intent; no implicit password-reveal workflow. Email/url/tel preserve native type semantics and mobile keyboard hints, not automatic masks or feature validation. With novalidate, the owning feature must present errors explicitly; email in the review form does so, while the type inventory demonstrates semantic controls. IME input waits for composition end, with no formatting/cursor rewrite. Readonly/disabled controls do not emit edits. Use controlled inline errors and the form's novalidate; browser validation bubbles do not drive UX. TextInput/NumberInput/textarea and the existing Select share .runa-field-control styling: appearance:none, no detached outer focus ring, a visible internal focus border, theme text selection and state-aware autofill paint. Error/focus borders take precedence over hover; keep hover selector specificity low. Preserve autocomplete/password managers; do not use delayed-color transition hacks or disable saved credentials. Forced-colors retains a system outline. Future field implementations should compose this shared field treatment where applicable.

## Exact numeric values

NumberInput wraps TextInput with integer/decimal/currency/percentage kind, pl/en locale, optional scale and unit. Input mode is numeric/decimal; model remains string|null, never a binary float. Canonical complete values use a decimal dot. Invalid/incomplete drafts (for example '-') are emitted intact and marked invalid, rather than retaining a stale valid model. validity emits parsing state; the submit validator must validate/serialize the current model. Empty is null; required/range/business rules belong to the feature.

parseDecimal returns a discriminated valid/value or invalid/reason result. serializeDecimal throws for invalid input; apply it at the request boundary after validation. formatDecimal substitutes the PL comma/EN dot exactly, preserving fractional zeros. Optional scale limits digits without rounding. Currency and percentage do not introduce automatic scaling, currency conversion, fixed precision or a 0–100 business limit. Integer strings may exceed Number.MAX_SAFE_INTEGER. Group separators/exponents are rejected. Positional masks are provided separately by MaskedInput; they do not implement grouped monetary editing. The control preserves raw editing/cursor state and formats only on blur or an external value/locale replacement. Unit text is a noneditable attached suffix beside the input (for example PLN or %), participates in aria-describedby and never enters the numeric model. TextInput exposes the shared suffix slot so NumberInput does not duplicate field markup.

## Values, dirty baselines and request failures

useForm accepts finite JSON form data only (no Date/File/classes/cycles/nonfinite values); file controls later expose their own file-state contract. It clones values/baselines, compares nested values independently of object key order, and exposes values/errors/busy/valid/dirty/failure. markClean(snapshot?) advances the baseline without overwriting edits. reset(snapshot?) restores the last confirmed baseline or explicitly supplied server data and clears failures/errors. Keep password/other sensitive values in local memory, never in browser preference storage or logs.

submit(action, validate?) validates first, blocks known errors/concurrent submissions, passes an isolated snapshot and holds busy until settlement. Success marks only that submitted snapshot clean, so programmatic edits during the request remain dirty. clearErrors(name?) and setError(name, messages) support feature-owned immediate validation. Validators should return fresh FieldErrors; preserve backend authority. No domain constraints are embedded in primitives.

ApiError 422 maps validated string-array errors using exact Laravel dot keys; 409 sets failure=conflict, and other errors set unexpected. Input/baselines survive failures. A screen must display operation-wide errors as well as inline messages, focus the relevant error and offer explicit conflict comparison/reload/retry. Never silently overwrite a draft on 409 or invent a successful retry. reset(serverSnapshot) is the deliberate reload seam; optimistic-lock tokens and server reconciliation remain owned by the feature. The catalog demonstrates local fixtures only, including a newer sample snapshot.

Optional dirty guards, masks, cascading fields and wizards are available through the public facade; their contracts are below. No business write APIs or server drafts are introduced.

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
  <template #actions><Button type="submit" variant="outline" :busy="busy">Zapisz</Button></template>
</Form>
```

Autofill browser constraints: [MDN :autofill](https://developer.mozilla.org/en-US/docs/Web/CSS/Reference/Selectors/:autofill) documents UA-important background/text rules. The shared field paints its theme surface with an inset shadow and sets text-fill/caret colors while retaining the internal focus indicator; it does not attempt to replace browser-owned suggestion UI.

## Additional field values and incomplete drafts

DateInput stores YYYY-MM-DD/null; TimeInput stores local HH:mm with optional seconds/fractions/null. DateRangeInput stores { start, end }, permits open/null endpoints and rejects reversed/invalid dates. formatDateOnly formats PL/EN without timezone conversion. DateTimeInput requires an IANA timeZone; toLocalDateTime displays an explicit-offset instant in that zone and resolveLocalDateTime commits UTC ISO values. Nonexistent DST wall times reject; repeated times reject by default, requiring an explicit earlier/later disambiguation policy. Never silently select an occurrence or shift a date-only value through UTC.

Temporal inputs preserve incomplete text drafts independently of committed values. Fields are type=text; no native date/time picker opens. PL accepts DD.MM.YYYY, EN uses YYYY-MM-DD; canonical dates are accepted in both. Moments append a space or T and 24-hour HH:mm with optional seconds/fractions. Invalid/impossible text remains visible and no partial or normalized date is silently committed. Calendar and time choices share this same validation path, including DST rejection. Consume validity to block submission of a stale last-valid model, and use reset() when deliberately discarding an incomplete draft (including when the model itself did not change). Their focus() method supports recovery. DateRangeInput combines endpoint and order validity. Supply localized invalidMessage/zoneLabel and linked helper text. TagsInput likewise reports validity=false while a nonempty draft awaits Enter; prevent submission or deliberately commit/discard it. ColorInput retains invalid drafts in its string/null model and emits validity. Compose these flags into Form.valid; feature rules still validate required/domain constraints.

FileInput stores File objects in separate shallowRef state, outside useForm's finite-JSON snapshots. Its issues event exposes count/size/type failures without truncating selection. Its dropzone accepts local file drops as well as keyboard/touch activation of the native operating-system chooser. Multiple batches append (without silent deduplication/truncation), single selection replaces; an empty/cancelled chooser preserves the current model. The UI shows limits, selection totals and file rows, never fake upload progress/success. Disabled, readonly and disabled ancestor fieldsets block mutation. Reset/removal is controlled through the model; clearing the native picker after selection allows choosing the same file again. Names/MIME/accept are untrusted UX hints: backend upload authorization, type/content/size validation and storage remain feature-owned. No file reads, previews, object URLs or uploads occur in the primitive.

Checkbox supports an indeterminate null value; Switch uses boolean. RadioGroup uses explicit string option identities and native arrow navigation. Color is six-digit hexadecimal without alpha. Its application panel has an HSV plane for pointer/touch and equivalent named Hue/Saturation/Brightness sliders for keyboard access, plus palette buttons; there is no native color input. Temporal panels open from their labelled trigger or Alt+Down in the text field; clear commits null, Done/Escape closes without rolling back already committed choices. Tags are plain trimmed strings, with caller-owned limits and localization; there is no remote suggestion provider. HiddenInput does not protect its value from modification.

## Selection fields

Select composes FormField for label/helper/warning/error and required state. A single model is a string (empty means clear); multiple=true uses string[]. Use searchable for local search or provide SelectionProvider for bounded cursor pages. Pass selectedOptions to resolve initial off-page labels. Providers own HTTP through the shared client, cancellation/authorization and stable IDs; the control never fetches all data. Search results replace the visible page, preserving selected IDs/labels. Clear is opt-in; errors do not clear entered values. See components.md for provider/keyboard contracts.

## Masks and advanced forms

MaskedInput composes TextInput and keeps raw string|null in v-model. pattern uses # for ASCII digits, A for Unicode letters, * for letters/digits, and backslash to escape literals. formatted emits presentation separately; completeness reports only occupied mask slots, never domain validity (a complete postal code may not exist). formatMask formats raw data; parseMaskedInput reads already formatted DOM text without treating fixed prefixes as field values. Do not format a numeric amount with a positional mask; use NumberInput’s exact decimal contract. The control maps native UTF-16 selection boundaries, retains caret edits, paste and selection deletion, deletes across separators and waits for IME composition end. Empty is null; incomplete raw values are preserved. Forward native autocomplete/inputmode/placeholder attributes and supply localized helper/error messages.

useDependentFields takes a stable array of keyed definitions and a Ref<Record<string,string>> of selected IDs. Definitions have dependsOn and either local options or async load({parents,signal}); unknown parents, duplicate keys and cycles reject before any requests. Changing a parent synchronously clears all descendant values/options and aborts obsolete requests. Each descendant waits for its parents’ available, enabled selections; stale responses cannot restore old options. state[key] exposes options/loading/error/ready; retry(key) restarts the current dependency context, cancel(key) clears readiness and descendants. Failed/cancelled loads are explicit, not empty successes. This is for bounded option domains; use SelectionProvider for large remote option searches. The feature owns HTTP/authorization and localized errors/retry controls. Definitions stay stable for the scope lifetime.

useDirtyGuard({dirty,enabled?,confirmLeave}) registers beforeunload only while enabled and dirty, removing it on cleanup. Browser unload confirmation requires browser-supported user activation and uses browser-owned text; it never validates fields. confirmLeave is an application-supplied boolean/Promise<boolean>, so a screen can use inline confirmation or a dialog. requestLeave(action) preserves the draft on refusal, blocks concurrent confirmations and invokes only an approved action; it does not mark values clean or discard them. beforeLeave is the router adapter. Use usePageResolver(pages,{beforeLeave:guard.beforeLeave}) for named, known same-origin routes. navigate stays synchronous without a guard and returns Promise<boolean> for guarded transitions. Guarded resolver history entries carry an internal position; browser Back/Forward retains the current view until consent and restores the prior entry after refusal. History entries created outside this resolver need their owning router/document-navigation adapter; do not mix arbitrary pushState calls into its guarded history. Modified/new-tab links retain native behavior; wrap other intentional exits with requestLeave. Only a successful save or deliberate reset should alter useForm’s dirty baseline.

useWizard accepts stable keyed steps, validate(key), submit() and optional disabled input. go/next validate intervening forward steps; previous/backward go preserve values without validation. finish revalidates all steps and stops at the earliest error before calling submit. Rejected/thrown validation or submit leaves a retryable failed state; busy prevents duplicate work and scope disposal prevents a later submit. Values/errors remain in the caller’s useForm; neither composable nor Wizard stores drafts. Wizard is a controlled view: pass steps/index/busy/failed and bind go/next/previous/finish; optional progress defaults on. It supplies accessible step/current semantics, busy fieldset, localized controls and active-heading/error focus. Call its exposed focusInvalid after nextTick when an already-failed validation changes messages without changing failed. The feature owns server writes, validation rules and error feedback.

For modal confirmation, render ConfirmDialog under the owning screen and resolve useDirtyGuard’s confirmLeave promise on confirm/cancel. Set modelValue false after resolving; Escape/X/cancel must resolve false so a pending navigation never hangs. Async form confirmations keep the dialog open while the caller sets busy/error, restoring errors inline and closing only after success. Dialog’s content fieldset disables native inputs while busy; external operations still enforce their own write guards.

TextInput/NumberInput/Select expose optional size=sm for compact table/filter/toolbars (36px, matching small Buttons); their default md form size stays 44px. Select popup controls/options follow its size. TextInput hideLabel forwards to FormField's associated visually hidden label, keeping compact inline width controls accessible without a visible repeated heading. Form fields keep validation semantics and browser autofill in either size; do not shrink whole ordinary forms merely to demonstrate the variant.

Account reauthentication preserves only explicitly safe name/login fields in memory,
bound to the same account. Offer explicit restoration/review; never replay a write.
Clear credentials, codes and files, fence late responses, and refresh stale revisions
or CSRF only through deliberate actions. See [account UI](../../modules/auth/account-ui.md).

Form submit actions use Button variant="outline" by default, including Auth entry,
account/security and preference-save forms; retain semantic tone for destructive
operations and existing busy/disabled behavior. Solid fills are not the default
submit hierarchy. Navigation beside forms uses public TextLink like welcome:
muted text without a permanent underline, underline/color on hover, visible focus,
44px touch targets and reduced-motion support. Active tab selection is separate.

## Related settings in one editor

Group related settings into one visible editing area rather than requiring task
buttons to reveal ordinary fields. Compose existing Card headers/bodies and give
independent operations their own Form and outlined submit. Keep each operation's
credential draft, validation, focus target and cleanup scoped to its form. Use
FormSection for field groups within a form, not as a substitute for a Card.
Saving one operation must not submit or discard another operation's draft.

Use shared Alert for inline feedback and ToastViewport/useToasts for transient
success, with specific operation descriptions. Preserve deliberate multi-step
security and destructive confirmation. Where one form needs multiple endpoints,
validate first, respect each proof/version, stop on failure and report partial
success without replay. The [account editor](../../modules/auth/account-ui.md)
supplies this pattern through its public controller; customer code does not copy
orchestration.
