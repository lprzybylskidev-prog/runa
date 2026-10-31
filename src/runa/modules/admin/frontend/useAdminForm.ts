import {
  computed,
  effectScope,
  onScopeDispose,
  ref,
  shallowReactive,
  shallowRef,
  watch,
} from 'vue';
import type { EffectScope, Ref } from 'vue';
import { ApiError, request } from '@/runa/http';
import { useOperationProof } from '@/runa/auth';
import type { OperationProofController } from '@/runa/auth';
import { formValuesEqual, useDirtyGuard, useForm } from '@/runa/ui';
import type {
  AdminAggregateInput,
  AdminCommandData,
  AdminCommandResult,
  AdminCommandReview,
  AdminComponentRegistry,
  AdminConflict,
  AdminError,
  AdminFieldLookup,
  AdminFormController,
  AdminText,
  AdminJson,
  AdminPageController,
  AdminPreview,
  AdminRecord,
  AdminResourceController,
  AdminSaveResult,
  AdminSectionData,
  AdminWrite,
} from './contracts';
import { adminPath, decodeRead, decodeCommandReview, object } from './decode';
import { pageOwners } from './pageContext';
import { navigationGuards, pageRuntime } from './runtime';
import type { NavigationGuard } from './runtime';
import { resolveAdminComponent } from './components';
import {
  refuseAdmin,
  sessionStamp,
  adminPresentationIdentity,
} from './useAdmin';
import { useAdminResource } from './useAdminResource';
import { validateAdminField } from './fieldValidation';
import { writeFailure } from './writeFailure';

const forms = new WeakMap<AdminPageController, AdminFormController>();
const copy = <T>(value: T): T => JSON.parse(JSON.stringify(value)) as T;
export function useAdminForm(
  page: AdminPageController,
  options: {
    publicId?: Ref<string | null>;
    confirmLeave?: () => boolean | Promise<boolean>;
    components?: AdminComponentRegistry | undefined;
    autoRead?: boolean;
  } = {},
): AdminFormController {
  const existing = forms.get(page);
  if (existing) return existing;
  const draft = useForm<Record<string, AdminJson>>({});
  const { values, errors, dirty } = draft;
  const base = ref<Record<string, AdminJson>>({});
  const records = ref<Record<string, AdminRecord>>({});
  const incoming = new Map<string, AdminRecord>();
  const overlaps = ref<Record<string, AdminConflict>>({});
  const error = shallowRef<AdminError>();
  const notice = ref('');
  const operations = ref<
    Record<
      string,
      { status: 'pending' | 'recovery_required'; operationId: string }
    >
  >({});
  const review = shallowRef<AdminPreview>();
  const commandReviews = ref<Record<string, AdminCommandReview>>({});
  const confirmation = shallowRef<{ title: AdminText; description: string }>();
  const failures = ref(new Set<string>());
  const editors = ref<Record<string, string | null>>({});
  const activated = ref(new Set<string>());
  const subject = ref(options.publicId?.value ?? null);
  let subjectSelected = false;
  const runtime = pageRuntime(page),
    admin = pageOwners.get(page);
  const lanes = shallowReactive(
    new Map<
      string,
      { scope: EffectScope; resource: AdminResourceController }
    >(),
  );
  const lookupLanes = shallowReactive(
    new Map<
      string,
      { scope: EffectScope; resource: AdminResourceController }
    >(),
  );
  const lookups = new Map<string, AdminFieldLookup>();
  const proofs = shallowReactive(
    new Map<
      string,
      { scope: EffectScope; controller: OperationProofController }
    >(),
  );
  let alive = true,
    epoch = 0,
    abort = new AbortController();
  let confirmationResult: ((accepted: boolean) => void) | undefined;
  const fieldPath = (section: string, field: string) => `${section}.${field}`;
  const sections = computed(() =>
    (page.page.value?.sections ?? []).filter(
      (section) =>
        (section.view === 'record' &&
          (options.autoRead !== false || activated.value.has(section.key))) ||
        Object.hasOwn(editors.value, section.key),
    ),
  );
  const busy = computed(
    () =>
      runtime.busy.value ||
      [...proofs.values()].some((proof) => proof.controller.busy.value),
  );
  function confirm(accepted: boolean) {
    confirmation.value = undefined;
    const resolve = confirmationResult;
    confirmationResult = undefined;
    resolve?.(accepted);
  }
  function ask(title: AdminText, description: string): Promise<boolean> {
    if (confirmationResult || !alive) return Promise.resolve(false);
    confirmation.value = { title, description };
    return new Promise((resolve) => {
      confirmationResult = resolve;
    });
  }
  const guard = useDirtyGuard({
    dirty,
    confirmLeave: () =>
      options.confirmLeave?.() ??
      ask(
        'Discard unsaved changes?',
        'Your unsaved changes will be discarded when you leave this editor.',
      ),
  });
  const beforeLeave = async () =>
    !runtime.busy.value && (await guard.beforeLeave());
  const stamp = () =>
    JSON.stringify([
      page.page.value?.owner,
      page.page.value?.key,
      admin && sessionStamp(admin.session),
    ]);
  function clearReviews() {
    review.value = undefined;
    commandReviews.value = {};
  }
  function setAvailable(identity: string, available: boolean) {
    const next = new Set(failures.value);
    if (available) next.delete(identity);
    else next.add(identity);
    failures.value = next;
  }
  const requiresSave = (section: AdminSectionData) =>
    section.required ||
    section.commands.some(
      (command) => command.mode === 'atomic' && command.intent === 'save',
    );
  const blocked = computed(
    () =>
      page.state.value !== 'ready' ||
      (page.page.value?.sections ?? []).some(
        (section) =>
          requiresSave(section) &&
          section.view === 'record' &&
          !sections.value.includes(section),
      ) ||
      sections.value.some(
        (section) =>
          (requiresSave(section) && section.diagnostic !== null) ||
          (requiresSave(section) &&
            !section.readonly &&
            (!records.value[section.key] ||
              lanes.get(section.key)?.resource.state.value !== 'ready' ||
              lanes.get(section.key)?.resource.busy.value ||
              !resolveAdminComponent(section.component, options.components)
                .available ||
              section.fields.some(
                (field) =>
                  !field.readonly &&
                  !resolveAdminComponent(field.component, options.components)
                    .available,
              ))),
      ) ||
      [...failures.value].some((path) =>
        (page.page.value?.sections ?? []).some(
          (section) =>
            requiresSave(section) &&
            (path === section.key || path.startsWith(section.key + '.')),
        ),
      ),
  );
  function clearSection(key: string) {
    const section = page.page.value?.sections.find(
      (section) => section.key === key,
    );
    for (const field of section?.fields ?? []) {
      const path = fieldPath(key, field.key);
      delete values.value[path];
      delete base.value[path];
      delete errors.value[path];
      delete overlaps.value[path];
    }
    delete records.value[key];
    incoming.delete(key);
    clearReviews();
    delete operations.value[key];
    draft.markClean(base.value);
  }
  function acceptRevision(key: string) {
    const section = page.page.value?.sections.find(
      (section) => section.key === key,
    );
    if (
      section &&
      !section.fields.some((field) => overlaps.value[fieldPath(key, field.key)])
    ) {
      const record = incoming.get(key);
      if (record) records.value[key] = copy(record);
    }
  }
  function reconcile(
    section: AdminSectionData,
    record: AdminRecord,
    submitted?: Record<string, AdminJson>,
  ) {
    const previous = records.value[section.key];
    incoming.set(section.key, copy(record));
    for (const field of section.fields) {
      const path = fieldPath(section.key, field.key);
      if (!Object.hasOwn(record.values, field.key)) {
        delete values.value[path];
        delete base.value[path];
        delete errors.value[path];
        delete overlaps.value[path];
        continue;
      }
      const remote = record.values[field.key]!,
        local = values.value[path];
      const baseline =
        submitted && Object.hasOwn(submitted, path)
          ? submitted[path]
          : base.value[path];
      if (
        local === undefined ||
        field.readonly ||
        section.readonly ||
        formValuesEqual(local, remote) ||
        baseline === undefined ||
        formValuesEqual(local, baseline)
      ) {
        values.value[path] = copy(remote);
        delete overlaps.value[path];
      } else if (field.sensitive && previous?.revision !== record.revision) {
        values.value[path] = copy(remote);
        delete overlaps.value[path];
        errors.value[path] = [
          'Re-enter this value after reviewing the current record.',
        ];
      } else if (!formValuesEqual(remote, baseline)) {
        overlaps.value[path] = {
          path,
          base: copy(baseline),
          local: copy(local),
          remote: copy(remote),
        };
      } else if (submitted) delete overlaps.value[path];
      base.value[path] = copy(remote);
    }
    acceptRevision(section.key);
    draft.markClean(base.value);
    if (previous?.revision !== record.revision) {
      clearReviews();
      runtime.revision.value = undefined;
    }
  }
  function createLane(key: string, lookup = false) {
    const target = lookup ? lookupLanes : lanes;
    let lane = target.get(key);
    if (!lane) {
      const scope = effectScope(true);
      const resource = scope.run(() =>
        useAdminResource(
          page,
          lookup ? key.slice(0, key.lastIndexOf(':')) : key,
        ),
      )!;
      lane = { scope, resource };
      target.set(key, lane);
      if (!lookup)
        scope.run(() =>
          watch(
            resource.result,
            (result) => {
              const section = sections.value.find(
                (section) => section.key === key,
              );
              if (section && result && 'values' in result)
                reconcile(section, result);
            },
            { flush: 'sync' },
          ),
        );
    }
    return lane.resource;
  }
  function readSection(section: AdminSectionData) {
    const id = Object.hasOwn(editors.value, section.key)
      ? editors.value[section.key]
      : options.publicId || subjectSelected
        ? subject.value
        : (section.record?.public_id ?? subject.value);
    return createLane(section.key).read({
      kind: 'record',
      ...(id ? { public_id: id } : {}),
    });
  }
  async function refresh() {
    if (!alive || page.state.value !== 'ready') return;
    await Promise.all(
      sections.value.filter((section) => !section.diagnostic).map(readSection),
    );
  }
  async function activateSection(key: string) {
    const section = page.page.value?.sections.find(
      (section) => section.key === key,
    );
    if (!section || section.view !== 'record' || activated.value.has(key))
      return;
    activated.value = new Set([...activated.value, key]);
    await readSection(section);
  }
  function erase() {
    ++epoch;
    abort.abort();
    abort = new AbortController();
    runtime.busy.value = false;
    confirm(false);
    for (const lane of [...lanes.values(), ...lookupLanes.values()])
      lane.scope.stop();
    lanes.clear();
    lookupLanes.clear();
    incoming.clear();
    lookups.clear();
    for (const proof of proofs.values()) proof.scope.stop();
    proofs.clear();
    notice.value = '';
    operations.value = {};
    values.value = {};
    base.value = {};
    records.value = {};
    overlaps.value = {};
    errors.value = {};
    editors.value = {};
    failures.value = new Set();
    clearReviews();
    error.value = undefined;
    draft.markClean({});
  }
  const draftIdentity = () =>
    JSON.stringify([
      page.page.value?.owner,
      page.page.value?.key,
      admin && adminPresentationIdentity(admin.session),
    ]);
  let identity = draftIdentity(),
    context = stamp();
  watch(
    [
      () => page.page.value,
      () => page.state.value,
      () => admin && sessionStamp(admin.session),
    ],
    () => {
      const current = draftIdentity();
      if (context !== stamp()) {
        // Authority observations invalidate in-flight work/reviews, never replay a write.
        ++epoch;
        abort.abort();
        abort = new AbortController();
        runtime.busy.value = false;
        confirm(false);
        clearReviews();
        context = stamp();
      }
      if (identity !== current || page.state.value !== 'ready') {
        erase();
        subjectSelected = false;
        subject.value = options.publicId?.value ?? null;
        identity = current;
      }
      if (page.state.value !== 'ready') return;
      const allowed = new Set(
        sections.value.flatMap((section) =>
          section.fields.map((field) => fieldPath(section.key, field.key)),
        ),
      );
      for (const path of Object.keys(values.value))
        if (!allowed.has(path)) {
          delete values.value[path];
          delete base.value[path];
          delete errors.value[path];
          delete overlaps.value[path];
        }
      for (const section of sections.value)
        for (const field of section.fields) {
          const path = fieldPath(section.key, field.key);
          if (
            (section.readonly || field.readonly) &&
            Object.hasOwn(base.value, path)
          ) {
            values.value[path] = copy(base.value[path]!);
            delete overlaps.value[path];
          }
        }
      for (const section of sections.value) {
        const readable = new Set(section.fields.map((field) => field.key));
        for (const record of [
          records.value[section.key],
          incoming.get(section.key),
        ])
          if (record)
            record.values = Object.fromEntries(
              Object.entries(record.values).filter(([key]) =>
                readable.has(key),
              ),
            );
      }
      for (const lane of lookupLanes.values()) lane.scope.stop();
      lookupLanes.clear();
      for (const [key, lane] of lanes)
        if (!sections.value.some((section) => section.key === key)) {
          lane.scope.stop();
          lanes.delete(key);
          delete records.value[key];
          incoming.delete(key);
        }
      clearReviews();
      draft.markClean(base.value);
      for (const section of sections.value)
        if (!lanes.has(section.key) && !section.diagnostic)
          void readSection(section);
    },
    { flush: 'sync', immediate: true },
  );
  function set(path: string, value: AdminJson) {
    const entry = sections.value
      .flatMap((section) => section.fields.map((field) => ({ section, field })))
      .find(({ section, field }) => fieldPath(section.key, field.key) === path);
    if (
      !entry ||
      entry.section.readonly ||
      entry.field.readonly ||
      !Object.hasOwn(values.value, path) ||
      busy.value
    )
      return;
    // useForm enforces finite JSON values on the subsequent controlled state comparison.
    formValuesEqual(value, value);
    values.value[path] = copy(value);
    notice.value = '';
    delete errors.value[path];
    if (overlaps.value[path])
      overlaps.value[path] = { ...overlaps.value[path]!, local: copy(value) };
    clearReviews();
  }
  function reset() {
    draft.reset(base.value);
    overlaps.value = {};
    error.value = undefined;
    clearReviews();
    for (const key of incoming.keys()) acceptRevision(key);
  }
  function resolve(path: string, choice: 'local' | 'remote') {
    const conflict = overlaps.value[path];
    if (!conflict || busy.value) return;
    if (choice === 'remote') values.value[path] = copy(conflict.remote);
    delete overlaps.value[path];
    clearReviews();
    for (const key of incoming.keys()) acceptRevision(key);
  }
  async function selectSubject(id: string | null) {
    if (!(await beforeLeave())) return false;
    erase();
    subject.value = id;
    subjectSelected = true;
    if (options.publicId) options.publicId.value = id;
    await refresh();
    return true;
  }
  if (options.publicId)
    watch(
      options.publicId,
      async (id, previous) => {
        if (id === subject.value) return;
        options.publicId!.value = previous;
        await selectSubject(id);
      },
      { flush: 'sync' },
    );
  async function editSection(key: string, id: string | null) {
    if (
      !(await beforeSectionLeave(key)) ||
      !page.page.value?.sections.some((section) => section.key === key)
    )
      return false;
    clearSection(key);
    lanes.get(key)?.scope.stop();
    lanes.delete(key);
    for (const [identity, lane] of lookupLanes)
      if (identity.startsWith(key + ':')) {
        lane.scope.stop();
        lookupLanes.delete(identity);
        lookups.delete(identity);
      }
    editors.value = { ...editors.value, [key]: id };
    await readSection(sections.value.find((section) => section.key === key)!);
    return true;
  }
  async function closeSection(key: string) {
    if (!(await beforeSectionLeave(key))) return false;
    clearSection(key);
    lanes.get(key)?.scope.stop();
    lanes.delete(key);
    delete editors.value[key];
    return true;
  }
  async function beforeSectionLeave(key: string): Promise<boolean> {
    if (busy.value) return false;
    const section = sections.value.find((section) => section.key === key);
    const changed = section?.fields.some((field) => {
      const path = fieldPath(key, field.key);
      return (
        Object.hasOwn(values.value, path) &&
        (!Object.hasOwn(base.value, path) ||
          !formValuesEqual(values.value[path]!, base.value[path]!))
      );
    });
    return (
      !changed ||
      Boolean(
        await (options.confirmLeave?.() ??
          ask(
            'Discard unsaved changes?',
            'Your unsaved changes will be discarded when you leave this editor.',
          )),
      )
    );
  }
  function commandInput(
    section: AdminSectionData,
    command: AdminCommandData,
    confirmed = false,
  ): AdminWrite | undefined {
    const record = records.value[section.key];
    if (
      !record ||
      operations.value[section.key] ||
      section.readonly ||
      section.diagnostic ||
      !command.available ||
      lanes.get(section.key)?.resource.busy.value ||
      lanes.get(section.key)?.resource.state.value !== 'ready' ||
      Object.keys(overlaps.value).some((path) =>
        section.fields.some(
          (field) => fieldPath(section.key, field.key) === path,
        ),
      )
    )
      return;
    const input: Record<string, AdminJson> = {};
    let invalid = false;
    for (const key of command.inputs) {
      const field = section.fields.find((field) => field.key === key),
        path = fieldPath(section.key, key);
      if (!field || field.readonly || !Object.hasOwn(values.value, path)) {
        invalid = true;
        continue;
      }
      const value = values.value[path]!;
      const messages = validateAdminField(field, value);
      if (messages.length) {
        errors.value[path] = messages;
        invalid = true;
      }
      // Secret state is not a value to submit; unchanged secrets remain absent.
      if (!(field.sensitive && value !== null && typeof value === 'object'))
        input[key] = copy(value);
    }
    if (invalid) {
      error.value = {
        reason: 'validation_failed',
        message: 'Check the highlighted fields.',
      };
      return;
    }
    const result: AdminWrite = {
      command: command.key,
      public_id: record.public_id,
      revision: record.revision,
      values: input,
      confirmed,
    };
    const currentReview = commandReviews.value[`${section.key}:${command.key}`];
    if (currentReview)
      result.review = copy(currentReview) as unknown as Record<
        string,
        AdminJson
      >;
    return result;
  }
  function applyErrors(
    section: AdminSectionData,
    failure: AdminError | undefined,
  ) {
    error.value = failure;
    for (const [key, messages] of Object.entries(failure?.errors ?? {})) {
      const field = section.fields.find(
        (field) => key === field.key || key.startsWith(field.key + '.'),
      );
      if (field) errors.value[fieldPath(section.key, field.key)] = messages;
    }
  }
  async function reviewCommand(key: string, name: string) {
    const section = sections.value.find((section) => section.key === key),
      command = section?.commands.find((command) => command.key === name);
    if (!section || !command || busy.value) return;
    const input = commandInput(section, command);
    if (!input) return;
    const result = await createLane(key).preview(input);
    if (!alive || !sections.value.some((section) => section.key === key))
      return;
    if (result) commandReviews.value[`${key}:${name}`] = result;
    else applyErrors(section, createLane(key).error.value);
    return result;
  }
  async function execute(
    key: string,
    name: string,
  ): Promise<AdminCommandResult | undefined> {
    const section = sections.value.find((section) => section.key === key),
      command = section?.commands.find((command) => command.key === name);
    if (
      !section ||
      !command ||
      busy.value ||
      [...failures.value].some(
        (path) => path === key || path.startsWith(key + '.'),
      )
    )
      return;
    let input = commandInput(section, command);
    if (!input) return;
    if (command.preview_required && !input.review) {
      error.value = {
        reason: 'admin_review_required',
        message: 'Review the current differences before submitting.',
      };
      return;
    }
    if (
      command.destructive &&
      !(await ask(command.title, 'Confirm this destructive operation.'))
    )
      return;
    // Confirmation never authorizes a changed draft, subject, review or descriptor.
    const current = commandInput(section, command, true);
    if (
      !current ||
      !formValuesEqual(input.values, current.values) ||
      input.revision !== current.revision ||
      page.state.value !== 'ready'
    )
      return;
    input = current;
    const submitted = copy(values.value),
      own = epoch;
    error.value = undefined;
    notice.value = '';
    const result = await createLane(key).command(input);
    if (!alive || own !== epoch) return;
    if (!result) {
      applyErrors(section, createLane(key).error.value);
      clearReviews();
      for (const field of section.fields)
        if (field.sensitive) {
          const path = fieldPath(key, field.key);
          if (Object.hasOwn(base.value, path))
            values.value[path] = copy(base.value[path]!);
        }
      proofs.get(`${key}:${name}`)?.controller.reset();
      return;
    }
    clearReviews();
    if (result.record) reconcile(section, result.record, submitted);
    if (result.status !== 'committed') {
      operations.value[key] = {
        status: result.status,
        operationId: result.operation_id!,
      };
      error.value = {
        reason: result.status,
        message:
          'The owning operation has not committed. Check its status before submitting again.',
      };
    } else notice.value = 'Changes saved.';
    proofs.get(`${key}:${name}`)?.controller.reset();
    return result;
  }
  function aggregate(): AdminAggregateInput | undefined {
    if (blocked.value || busy.value || Object.keys(overlaps.value).length)
      return;
    const participants: AdminAggregateInput['participants'] = [];
    for (const section of sections.value) {
      const command = section.commands.find(
        (command) => command.intent === 'save' && command.mode === 'atomic',
      );
      if (!command || section.readonly) continue;
      const input = commandInput(section, command);
      if (!input) return;
      const participantReview = review.value?.participants.find(
        (participant) => participant.id === section.key,
      )?.review;
      if (participantReview)
        input.review = copy(participantReview) as unknown as Record<
          string,
          AdminJson
        >;
      participants.push({ id: section.key, input });
    }
    if (!participants.length || !page.page.value) return;
    return {
      revision:
        review.value?.revision ??
        runtime.revision.value ??
        page.page.value.revision,
      participants,
    };
  }
  async function aggregateRequest(
    previewing: boolean,
  ): Promise<AdminPreview | AdminSaveResult | undefined> {
    const input = aggregate();
    if (!input) return;
    const intent = JSON.stringify(input);
    if (
      !previewing &&
      input.participants.some(
        (participant) =>
          sections.value
            .find((section) => section.key === participant.id)
            ?.commands.find(
              (command) => command.key === participant.input.command,
            )?.preview_required,
      ) &&
      !review.value
    ) {
      error.value = {
        reason: 'admin_review_required',
        message: 'Review the current differences before saving.',
      };
      return;
    }
    if (
      !previewing &&
      input.participants.some(
        (participant) =>
          sections.value
            .find((section) => section.key === participant.id)
            ?.commands.find(
              (command) => command.key === participant.input.command,
            )?.destructive,
      )
    ) {
      if (
        !(await ask(
          'Save destructive changes?',
          'Confirm the reviewed changes.',
        ))
      )
        return;
      if (JSON.stringify(aggregate()) !== intent) {
        error.value = {
          reason: 'admin_review_changed',
          message:
            'The editor changed. Review the current changes before saving.',
        };
        return;
      }
      for (const participant of input.participants)
        participant.input.confirmed = true;
    }
    const own = epoch,
      context = stamp(),
      submitted = copy(values.value);
    runtime.busy.value = true;
    error.value = undefined;
    try {
      const response = object(
        object(
          await request(
            adminPath({
              owner: page.page.value!.owner,
              page: page.page.value!.key,
            }) + (previewing ? '/preview' : '/save'),
            {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify(input),
              signal: abort.signal,
            },
          ),
        ).data,
      );
      if (!alive || own !== epoch || context !== stamp()) return;
      if (
        typeof response.revision !== 'string' ||
        !response.revision ||
        !Array.isArray(response.participants) ||
        response.participants.length !== input.participants.length
      )
        throw new TypeError('Invalid aggregate result.');
      const ids = new Set<string>();
      for (const item of response.participants) {
        const participant = object(item);
        if (
          typeof participant.id !== 'string' ||
          ids.has(participant.id) ||
          !input.participants.some((input) => input.id === participant.id)
        )
          throw new TypeError('Invalid participant.');
        ids.add(participant.id);
      }
      if (previewing) {
        const result: AdminPreview = {
          revision: response.revision,
          participants: response.participants.map((item) => {
            const participant = object(item),
              value = decodeCommandReview(
                { data: participant.review },
                sections.value.find(
                  (section) => section.key === participant.id,
                ),
              );
            return { id: participant.id as string, review: value };
          }),
        };
        review.value = result;
        return result;
      }
      if (response.status !== 'committed')
        throw new TypeError('Uncommitted aggregate.');
      const result: AdminSaveResult = {
        status: 'committed',
        revision: response.revision,
        participants: response.participants.map((item) => {
          const participant = object(item),
            section = sections.value.find(
              (section) => section.key === participant.id,
            )!;
          return {
            id: section.key,
            record:
              participant.record === null
                ? null
                : (decodeRead(
                    { data: participant.record },
                    {
                      kind: 'record',
                      ...(records.value[section.key]?.public_id
                        ? { public_id: records.value[section.key]!.public_id! }
                        : {}),
                    },
                    section,
                  ) as AdminRecord),
          };
        }),
      };
      // Decode every participant before changing any baseline.
      for (const participant of result.participants)
        if (participant.record)
          reconcile(
            sections.value.find((section) => section.key === participant.id)!,
            participant.record,
            submitted,
          );
      runtime.revision.value = result.revision;
      clearReviews();
      runtime.changed.value++;
      notice.value = 'Changes saved.';
      return result;
    } catch (cause) {
      if (alive && own === epoch && context === stamp()) {
        error.value = writeFailure(cause, Object.keys(values.value));
        for (const [path, messages] of Object.entries(
          error.value.errors ?? {},
        )) {
          const target = Object.keys(values.value).find(
            (field) => path === field || path.startsWith(field + '.'),
          );
          if (target) errors.value[target] = messages;
        }
        if (cause instanceof ApiError && admin) {
          let entry = false;
          try {
            entry = object(cause.payload).scope === 'entry';
          } catch {
            /* Safe unknown payload. */
          }
          if (cause.status === 401 || entry) refuseAdmin(admin, cause);
        }
        if (cause instanceof ApiError && cause.status === 409) {
          clearReviews();
          void refresh();
        }
      }
    } finally {
      if (own === epoch) runtime.busy.value = false;
    }
  }
  function lookup(key: string, name: string): AdminFieldLookup {
    const identity = `${key}:${name}`,
      existing = lookups.get(identity);
    if (existing) return existing;
    const lookup: AdminFieldLookup = {
      searchable: false,
      provider: async (request) => {
        const section = sections.value.find((section) => section.key === key);
        if (
          !section?.fields.some(
            (field) => field.key === name && field.lookup,
          ) ||
          request.signal.aborted
        )
          throw new Error('Lookup unavailable.');
        const id = records.value[key]?.public_id;
        const resource = createLane(`${key}:${name}`, true);
        // Generic options have no search parameter. Never invent a filter or scan pages.
        if (request.query.trim())
          throw new Error(
            'Use the owning declared filters to search this lookup.',
          );
        await resource.read(
          {
            kind: 'options',
            field: name,
            ...(id ? { public_id: id } : {}),
            ...(request.cursor ? { cursor: request.cursor } : {}),
            limit: request.limit,
          },
          { signal: request.signal },
        );
        const result = resource.result.value;
        if (
          request.signal.aborted ||
          !result ||
          !('field' in result) ||
          records.value[key]?.public_id !== id
        )
          throw new Error('Lookup unavailable.');
        return { options: result.items, nextCursor: result.next };
      },
    };
    lookups.set(identity, lookup);
    return lookup;
  }
  function proof(key: string, name: string): OperationProofController {
    const identity = `${key}:${name}`;
    let entry = proofs.get(identity);
    if (!entry) {
      const scope = effectScope(true);
      const command = () =>
        sections.value
          .find((section) => section.key === key)
          ?.commands.find((command) => command.key === name);
      const controller = scope.run(() =>
        useOperationProof({
          identity: () =>
            page.state.value === 'ready' &&
            command()?.available &&
            command()?.action
              ? stamp()
              : undefined,
          action: () => command()?.action ?? '',
          requirement: () => {
            const declared = command()?.requirement ?? 'none',
              reviewed =
                commandReviews.value[identity]?.requirement ??
                review.value?.participants.find(
                  (participant) => participant.id === key,
                )?.review.requirement ??
                'none';
            const password = [declared, reviewed].some(
              (requirement) =>
                requirement === 'password' || requirement === 'both',
            );
            const factor = [declared, reviewed].some(
              (requirement) =>
                requirement === 'second_factor' || requirement === 'both',
            );
            return password && factor
              ? 'both'
              : password
                ? 'password'
                : factor
                  ? 'second_factor'
                  : 'none';
          },
        }),
      )!;
      entry = { scope, controller };
      proofs.set(identity, entry);
    }
    return entry.controller;
  }
  const controller: AdminFormController = {
    state: computed(() => page.state.value),
    values,
    errors,
    records,
    sections,
    dirty,
    busy,
    blocked,
    conflicts: computed(() => Object.values(overlaps.value)),
    error: computed(
      () =>
        error.value ??
        [...lanes.values()].find((lane) => lane.resource.error.value)?.resource
          .error.value,
    ),
    review,
    commandReviews,
    confirmation,
    set,
    refresh,
    reset,
    resolve,
    confirm,
    beforeLeave,
    selectSubject,
    fieldPath,
    lookup,
    execute,
    reviewCommand,
    editSection,
    closeSection,
    setAvailable,
    notice,
    proof,
    activateSection,
    operations,
    preview: async () =>
      (await aggregateRequest(true)) as AdminPreview | undefined,
    save: async () =>
      (await aggregateRequest(false)) as AdminSaveResult | undefined,
  };
  forms.set(page, controller);
  const leave: NavigationGuard = (action) =>
    runtime.busy.value ? Promise.resolve(false) : guard.requestLeave(action);
  const guards = admin
    ? (navigationGuards.get(admin) ?? new Set<NavigationGuard>())
    : undefined;
  if (admin && guards) {
    guards.add(leave);
    navigationGuards.set(admin, guards);
  }
  runtime.beforeLeave.add(beforeLeave);
  function click(event: MouseEvent) {
    if (
      !dirty.value ||
      event.defaultPrevented ||
      event.button !== 0 ||
      event.metaKey ||
      event.ctrlKey ||
      event.shiftKey ||
      event.altKey
    )
      return;
    const anchor =
      event.target instanceof Element ? event.target.closest('a[href]') : null;
    if (
      !(anchor instanceof HTMLAnchorElement) ||
      (anchor.target && anchor.target !== '_self') ||
      anchor.hasAttribute('download') ||
      anchor.href === location.href
    )
      return;
    event.preventDefault();
    if (runtime.busy.value) return;
    void guard.requestLeave(() => {
      window.location.assign(anchor.href);
    });
  }
  document.addEventListener('click', click, true);
  onScopeDispose(() => {
    alive = false;
    erase();
    runtime.busy.value = false;
    forms.delete(page);
    guards?.delete(leave);
    runtime.beforeLeave.delete(beforeLeave);
    document.removeEventListener('click', click, true);
  });
  return controller;
}
