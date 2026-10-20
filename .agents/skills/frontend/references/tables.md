# Table and List Patterns

Canonical implementation: `src/runa/resources/js/components/AppTable.vue`, provider contracts in `src/runa/resources/js/runa/ui/tables.ts`, processing/transport helpers in `src/runa/resources/js/lib/table.ts`.

Reuse filters, statistics, pagination, sorting, row/bulk actions and loading/empty/error states. Planned RM-2026-004 order is heading/description/actions, breadcrumbs, optional statistics, optional toolbar/filters, then mandatory content, with an optional supplementary panel. Statistics/Statistic now implement the global/filtered user switch, KPI slots and optional per-view mode persistence through useStatisticsMode; providers/calculations remain external. the isolated ApplicationScenario test fixture demonstrates integration above its toolbar. Pagination and the table foundation are available; personalization and named saved views are available; cross-page selection, provider-owned bulk operations and exports are available. Preserve mobile access to data and actions, including horizontal scrolling or an intentional responsive presentation when needed. Respect the canonical API pagination/filter contract and finite page sizes. Update this reference when a reusable pattern changes.

## Table foundation

`Table`, `useTable`, `TableColumn<Row>`, `TableQuery`, `TablePage<Row>` and the provider helpers are public through `@/runa/ui`. Table is controlled through `query` / `update:query` and `selection` / `update:selection` (legacy explicit `selected` / `update:selected` remains supported). Use a stable `rowKey`, normally the backend `public_id`. Selection supports explicit IDs or an all-matching query snapshot with exclusions.

Pass `mode="client"` with a bounded, already-loaded `rows` array, or `mode="server"` with `provider(query, signal)`. Client mode filters and sorts before slicing; use `compare` for numeric/exact domain ordering and `filter` for column-specific semantics. Server mode never applies local filtering/slicing to returned rows. Providers return `rows`, `total`, `matching`, `counts` (`exact`, `stale`, `unknown`), `hasNext`, and capabilities (`sort`, `filter`, `maxPerPage`). Only exact matching counts drive numbered pagination. Unknown/stale counts use previous/next. Consumers must keep query page sizes within provider capabilities; normalization bounds helper requests to 100 by default. Filters reset the page through the filter slot's `update`; custom query controls must do the same.

Use `cell-<key>` slots for presentation, `actions` for row actions, `filters` for application-owned controls, and `bulk` for external selected-record controls. All controls work without a layout. Wide content remains accessible in a labelled keyboard-scrollable table region. Async states reuse AsyncContent and retry; obsolete requests abort and their eventual replies are ignored. Filters remain usable when results are empty or fail.

`createApiTableProvider({ path, sort, filter, maxPerPage, decode })` uses the shared HTTP client with encoded `page`, `per_page`, `sort` (leading `-` for descending) and `filter[key]` parameters. `decode(unknown)` must validate the application's `data` + `meta` envelope, rows/public IDs, counts and capabilities and translate it to TablePage. Invalid payloads must throw. The backend must independently validate allowlists/page bounds, authorize records and return counts scoped to the same access/query rules; frontend capabilities are UX only. Custom providers translate their own wire contracts. No backend table engine is installed by this component.

Table defaults use compact vertical spacing with middle-aligned body cells and sortable labels aligned to the values below. Use small Buttons in row action slots to retain approximately 45px single-line rows. Table pagination uses the small size; Pagination's standalone default stays unchanged. Selection uses opt-in compact Checkbox targets (32px), with visually hidden accessible labels.

## Column personalization and named views

`TablePreferences` and `useTableViews` are optional, shell-independent exports. Table accepts controlled `columnState` (`key`, `visible`, optional pixel `width`); processing still uses every declared column so hiding a field does not silently remove its active sort/filter. Never put unauthorized fields in descriptors or API responses: hiding columns is presentation only.

```ts
const tableViews = useTableViews({
  scope: { applicationId: 'atlas', moduleKey: 'projects', viewKey: 'list' },
  columns: () => columns.value,
  query,
  // Optional replaceable PreferenceStore; default is the browser-local store.
});
```

Pass `:controller="tableViews" :columns="columns" :english="locale === 'en'"` to TablePreferences and `:column-state="tableViews.columns.value"` to Table. Keep the same controlled query ref connected to Table. No layout/provider installation is required; client and server modes share the same presentation contract.

The preference control is `table-views`, scoped by application/module/view, using schema version 2. A version-1 current snapshot (columns/query) or version-1 workspace object migrates to the normalized schema. Corrupt/unknown storage resets safely; denied/quota storage uses the existing store's in-memory fallback. Removed columns and their filters/sorts are discarded from current and saved snapshots, new columns append visible, duplicate keys are removed, widths clamp to 80–800px, and at least one available data column stays visible. State contains no rows, credentials or authorization decisions. Filter values are browser-local preferences; applications must not persist sensitive filters through this adapter.

Column controls offer visibility, drag-before reordering plus keyboard move buttons, a compact width text field with px suffix and automatic-width reset. Widths are layout hints with a fixed table layout when configured; spare desktop space can expand columns and narrow screens retain horizontal scrolling. Width values initially use the measured rendered column width (160px fallback for unmeasured/hidden columns), while explicit values show the configured width. Pass Table's column-widths event into TablePreferences measuredWidths. Custom controls can call setColumns/move/reorder/resetColumns directly. Width reset removes manual sizing and restores descriptor order/visibility.

Named views save global search, filters, sort, page size, column order/visibility/width, never the transient page number (applying a view starts at page 1). Controller methods: create(name), select(id), update(), rename(name), remove(), setDefault(id|null), reset(), resetColumns(), selectDefault(). Names are trimmed, unique case-insensitively, 1–80 characters, with at most 50 views. The default view is applied on re-entry; without one the last current snapshot is restored. Dirty is exposed relative to the selected saved snapshot; create/update are explicit and unsaved edits do not mutate saved views. Deleting a default clears its default designation and retains the current presentation. Reset current restores the initial query and columns, preserving named views/default; clear the default separately to change next-entry behavior. Full account/shared synchronization remains deferred.

The default Table toolbar uses sm TextInput/Select controls for **Search** and page size. Search splits trimmed text into terms and requires each term to match at least one declared searchable column value before pagination; `TableColumn.searchable=false` excludes a column. Hidden columns remain searchable unless explicitly excluded. Search is independent of per-column filters, which applications may implement through the existing filter slot. `TableQuery.search` is optional and included in saved snapshots. Server providers receive the normalized term string; createApiTableProvider encodes it as `filter[search]` (disable with searchable=false and disable the Table search control for unsupported endpoints). Backend implementations must validate this filter, search only permitted fields/records and count the same matching query, without downloading datasets into the browser.

The adjacent Columns and views trigger is a sm ghost icon button using the same settings icon and Tooltip treatment as export settings; it retains the complete shared Dialog for column/view editing. The selector is **Available views**; its first option is always **Default view**, followed by every saved named view. Selecting Default resolves the designated saved default when present, otherwise the application's initial snapshot. A saved default is still included in the named list. The current browser implementation uses localStorage via PreferenceStore, with memory fallback, rather than cookies. Auth must later review and move per-user preferences to durable backend storage across browsers; see the deferred-module concerns registry.

## Cross-page selection and operations

`RowSelection<Query>` is `{ mode: 'explicit', publicIds }` or `{ mode: 'all-matching', query, excludedPublicIds, matchingCount }`. The page checkbox affects the current page only. The selection strip appears after selecting rows; a query-change notice remains visible after invalidation without empty selection buttons. Select all matching requires an exact count and stores the query, never fetched IDs. Paging preserves selection; changing search, filters or sort invalidates an all-matching selection with a live notice. The application/backend must revalidate snapshots against authorized current records; counts are UX hints, never permission or integrity proof. Explicit off-page IDs remain selected until cleared by their owner. Selection is transient and not part of a saved view.

`TableActions` combines sm icon buttons for CSV/XLSX/PDF/Print, an accessible settings Popover for scope/PDF orientation, optional application-owned bulk buttons and `OperationPanel`. Icon buttons retain accessible names and Tooltip hints; the current scope remains visible above them. Bulk controls appear only after selection. Pass `provider`, optional `printAdapter`/`bulkProvider`, controlled `query`/`selection` and ordered visible export columns. The Table bulk slot exposes `selection` and `count`. Export descriptors supply raw typed values independently of rendered cells. Supply only authorized columns; presentation hiding is not authorization. Successful file generation downloads automatically; the completion panel offers Download again. Partial results require an explicit download action. Application-owned destructive bulk actions must provide their own confirmation before calling the provider.

```ts
const selection = ref<RowSelection<TableQuery>>({
  mode: 'explicit',
  publicIds: [],
});
const exports = createClientExportProvider({
  rows: () => alreadyLoadedRows.value,
  columns: () => visibleExportColumns.value,
  tableColumns: () => columns.value,
  rowKey: (row) => row.public_id,
});
// Table: :selection="selection" @update:selection="selection = $event"
// Table #toolbar="{ feedbackTarget }": preferences and TableActions.
// Pass :feedback-target="feedbackTarget" to TableActions for full-width status below the toolbar.
```

Browser adapters accept only already-loaded bounded data. All exports are capped at 2,000 rows, 50 columns, 20,000 cells including headers, 500,000 text characters and 32,767 characters per cell. XLSX additionally caps 1,500 cells, 20,000 XML-escaped UTF-8 text bytes and worksheet XML below 150,000 bytes. These limits preserve the restrictive CSP: write-excel-file/fflate would otherwise start blob workers for larger individual ZIP entries. Do not weaken CSP; use an application-owned server export for larger results. Custom adapters may impose lower limits. Cancellation ignores eventual local generator results; it cannot preempt synchronous library CPU work.

CSV is UTF-8 with BOM, semicolon/CRLF by default, full quoting and formula-prefix escaping for string cells/headers; negative numeric cells remain numbers. Trusted callers may explicitly opt into formula preservation. XLSX uses genuine number/boolean/string/date cells; declared date/datetime fields require canonical date-only/offset-instant strings, exported as UTC calendar values without timezone metadata. Exact decimals larger than JavaScript precision must remain strings. No user value becomes an XLSX formula. PDF uses A4 portrait/landscape, embedded local Roboto Unicode fonts, repeating headers and automatic pagination. PDF accepts plain cell text only; no document definitions, remote resources, HTML, SVG or attachments. XLSX/PDF libraries load dynamically on demand. PDF engine/font chunks are approximately 287/469 kB gzip; the ordinary welcome application does not import them.

`Table` exposes a toolbar slot alongside its default Search/page-size controls. Keep view preferences and export actions there, with sm controls aligned along their bottom edges. Its feedbackTarget slot property identifies the per-table feedback outlet; TableActions can defer-teleport OperationPanel there so progress/errors do not shift the toolbar. The legacy bulk slot remains available for custom application controls. Ordinary empty feedback outlets occupy no space. Both client/server review tables consume the same composition; neither existing shell has provisional table controls to replace.

`createClientPrintAdapter` builds a temporary same-origin document with textContent, a static emitted stylesheet and repeated table headers; separate zero-spacing cell borders with 1pt CSS rules provide uniform horizontal separators without collapsed fractional hairlines; it prints the entire scoped result, then cleans up on afterprint/abort/timeout. It is separate from file export. Server datasets require an application-owned print adapter using an authorized dedicated print endpoint; never pass the current page as the complete result or fetch every page into the browser. The temporary catalog's server simulator owns synthetic local fixtures and is not a production data strategy.

`useOperation` handles queued/running/progress/completed/failed/partial/cancelled states, bounded polling, AbortSignal, duplicate-start blocking and stale-response/unmount protection. A failed poll resumes the same operation; explicit operation retry uses its retry hook. Requests snapshot scope/selection/columns and carry a stable operationKey across retries; backend idempotency is mandatory for side effects. Cancellation is exposed only when a handle supports it; failure to confirm cancellation is recoverable and never reported as cancelled. Unmount stops local polling without silently cancelling server work. `OperationPanel` localizes known failures, permission denial and partial counts; server error text is not injected into UI. Backend providers own durable operation state, permission checks and partial retry semantics.

## Server export transport

`createApiExportProvider({ path: '/exports', capabilities })` uses the single HTTP wrapper. No endpoint/job implementation is installed. Start POST carries the typed ExportRequest JSON and Idempotency-Key. Return either `{ kind: 'download', download: { filename, mime_type, url } }` or `{ kind: 'operation', operation: { public_id, status_path, cancel_path?, retry_path? } }`. Resource paths exclude `/api`, which the wrapper adds. Status GET returns queued/running (optional 0–100 progress), completed (optional download/succeeded), failed (error), partial (succeeded/failed/error/optional download) or cancelled. Errors contain code/retryable; counts are nonnegative safe integers. Cancel and retry POST to supplied paths; retry returns a fresh download/operation envelope. Backend cancellation responses must confirm cancellation. Downloads allow only same-origin HTTP(S) and known export MIME types. Unknown/invalid envelopes fail closed. Backend must validate all column/scope/filter/action keys, authorize both start and status/download/retry/cancel, enforce size/rate limits and idempotency, stream durable files and recheck selection staleness. Provider capabilities are presentation only.

License notices are versioned in resources/licenses and emitted under build/licenses: export-dependencies.txt covers the complete pinned graph, Roboto-OFL.txt covers the actual OFL font, and dependencies.md is Vite's bundled-module report. Preserve these files when redistributing export-enabled bundles; regenerate/review notices during deliberate dependency changes. The owner approved the two exact direct versions and normal transitive graph in RM-2026-004 point 19.

## Typed provider extension example

A custom transport adapts to the public provider contract; it does not change Table or require a layout:

```ts
import type { TableProvider, TableQuery, TablePage } from '@/runa/ui';
interface ProjectRow {
  public_id: string;
  name: string;
}
// Owned by the application: shared HTTP client + unknown-payload validation.
declare function loadProjects(
  query: TableQuery,
  signal: AbortSignal,
): Promise<TablePage<ProjectRow>>;
const projectsProvider: TableProvider<ProjectRow> = (query, signal) =>
  loadProjects(query, signal);
```

Pass `mode="server" :provider="projectsProvider"` and the same controlled query/columns/rowKey as client mode. For canonical Laravel API transport prefer `createApiTableProvider`, with allowlisted sort/filter keys and a decoder that rejects malformed unknown responses. The server independently authorizes every row, count, export and bulk operation. `src/runa/tests/frontend/fixtures/browser/ScenarioTables.vue` retains deterministic client/server/operation fixtures; production applications supply their own endpoint and operation provider. Column presentation uses `cell-<key>` slots; exports require explicit authorized `ExportColumn` descriptors and the documented budgets.
