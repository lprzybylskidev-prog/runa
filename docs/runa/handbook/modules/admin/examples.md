# Admin authoring and consumer examples

These examples consume the current source contracts, including additive Framework
API 1.1.0 read-health planning. Source and focused
consumer checks do not establish final browser, installation or release acceptance.
Keep application implementations in `src/app`, application documentation in
`docs/app` and tests in the owning central `tests` roots. The supplied application
stays minimal; do not install these examples into every customer scaffold.

## Opt-in module generation

```bash
./bin/artisan runa:make:module app.documents --write
./bin/artisan runa:make:module app.documents admin Document
```

Review the second command's JSON Lines: candidate contents and hashes, including
the existing manifest's current SHA-256. Apply that exact plan with
`./bin/artisan runa:make:module app.documents admin Document --write
--overwrite=src/app/modules/documents/module.json=<reviewed-current-sha256>` on
one command line. Every existing target requires its own current hash. A repeat
generation preserves customer edits by refusing unapproved targets. There is no
force switch, registry synchronization, installation or grant assignment.

The `admin` element requires a simple PascalCase name whose snake_case spelling
is at most 48 bytes. It generates a guarded owning `DocumentTools` API 1.0.0,
handler/proxy/API declaration, ManagementPageProvider/ManagementEntityProvider,
resource/widget/command adapter, immediate settings provider, custom section Vue
component, central test and module guide. It registers `apis`, `management` and
`settings` and adds the guide to the manifest. Its management provider declares an
exact normalize call for each read/page/widget; the reviewed manifest requires
framework_api ^2.0.0. The generated business API remains usable without Admin. A module has one settings provider;
if one already belongs to another class, generation refuses. Extend that provider
and author declarations deliberately rather than overwrite its validation/recovery.
Generation retains the module version/dependencies: assess versions, source
synchronization, installed data and any required forward changes before delivery.

The runnable example is a stateless text normalization tool. Its generated page
has a controlled input, independent calculation button, read-only bounded table,
dashboard widget and an owned summary contribution to its own page. The table
contains one configuration value with no durable public ID. Calculation results
are temporary; rereading restores the configured default. This generated tool does not expose settings editing. Its configured default is
consumed through native settings. To make settings administrator-editable, author
a dedicated owning module page with named snapshots, typed validation, grants,
revisions and transactional Audit; use Auth policy as the supplied workflow
reference. The module dialog never supplies a generic settings editor. The widget intentionally has unknown observation
freshness. It must not advertise a newly invented health timestamp.

The business API is usable without Admin:

```php
use App\Modules\Documents\Contracts\DocumentTools;
use Runa\Support\Modules\Api\ModuleApis;

$result = app(ModuleApis::class)->resolve('app.documents', DocumentTools::class, '1.0.0')
    ->normalize('  Example   text  ');
```

The native operation requires `app.documents.document.use`, independently of Admin
entry. No required or optional Admin dependency is added. The handler owns input
validation; the adapter invokes its guarded API rather than reimplement business
behavior. No Audit mutation event is emitted for this pure calculation. For a
persisted entity, replace the example with owning Actions, record Policies,
UUIDv7/revisions, default-connection transaction and atomic Audit. Do not persist
inside a renderer, use another module's private models or label a calculation as
an entity Save.

To contribute to `runa.auth.users.profile` or `.access`, change the source host and
declare `runa.auth` as an optional manifest dependency. Keep the section, adapter,
fields, component and permissions with their owner. A foreign subject needs its
own read policy: the generated singleton deliberately refuses a supplied UUID.
Changing the host alone does not implement subject-aware reads. An unavailable
optional host must leave the owning business API usable. The dashboard host accepts
widgets rather than arbitrary editable sections. Visual SlotHost/SlotContribution
contracts remain separate from editable ManagementContribution declarations.

## Supplied reference module

The unreleased optional development-only `app.reference` candidate declares a read-only inspect page,
owned adapter, replaceable section and dashboard widget through its existing Ping
API. Its source dictionary declares `app.reference.reference.inspect`; native Admin
entry is still required separately. It deliberately has no settings editor or
foreign-record write. When the example is selected, follow its application-owned
`docs/app/modules/reference/README.md` for capability reproduction and composition.
Reference declares one Ping occurrence through ManagementReadPlanProvider. Native
focused regressions cover its authorized section/widget and revoked access; final
runtime/browser acceptance remains separate. See the
[read-health source contract](declarations.md#read-health-plans).
The clean scaffold keeps the generator examples below without installing reference.

## Independent application page

No module manifest is needed for application-owned source information. The following
two files form a complete read-only example. They reuse the existing native Admin
entry permission; app registrations declare no permission dictionary. Real record
reads additionally need their own Policies and field projection.

```php
<?php
// src/app/Management/ReleasePage.php

namespace App\Management;

use Runa\Contracts\Modules\{ManagementBinding, ManagementField, ManagementFieldAccess,
    ManagementPage, ManagementPageProvider, ManagementSection};

final class ReleasePage implements ManagementPageProvider
{
    public function permissions(): array { return []; }
    public function actions(): array { return []; }
    public function components(): array { return []; }
    public function contributions(): array { return []; }
    public function widgets(): array { return []; }

    public function pages(): array
    {
        $permission = 'runa.admin.enter';

        return [new ManagementPage('release', 'Application release', $permission, [
            new ManagementSection('release', 'Release information', $permission, null, 'release', [
                new ManagementFieldAccess(new ManagementField('label', 'text',
                    ['pl' => 'Wydanie', 'en' => 'Release'],
                    ['pl' => 'Informacja aplikacji.', 'en' => 'Application source information.']), $permission),
            ]),
        ])];
    }

    public function bindings(): array
    {
        return [new ManagementBinding('release', ReleaseReader::class, [])];
    }
}
```

```php
<?php
// src/app/Management/ReleaseReader.php

namespace App\Management;

use Runa\Contracts\Modules\{ManagementPageResult, ManagementRead, ManagementRecord, ManagementResourceReader};
use Runa\Support\Modules\Api\OperationRejected;

final class ReleaseReader implements ManagementResourceReader
{
    public function read(ManagementRead $input): ManagementRecord
    {
        if ($input->publicId !== null || $input->cursor !== null || $input->filters !== [] || $input->sort !== []) {
            throw new OperationRejected('release_query_invalid', 'runa.admin', 'read', 422);
        }

        return new ManagementRecord(null, 'release-1', ['label' => 'Application release 1']);
    }

    public function page(ManagementRead $input): ManagementPageResult
    {
        return new ManagementPageResult([$this->read($input)], null, 1);
    }
}
```

In the existing application service provider's `boot(): void`, bind the handler
explicitly and register lazily:

```php
$this->app->bind(\App\Management\ReleaseReader::class, \App\Management\ReleaseReader::class);
$this->app->afterResolving(\Runa\Modules\Admin\Contracts\AdminRegistry::class,
    fn ($registry) => $registry->register(new \App\Management\ReleasePage));
```

Do not eagerly resolve AdminRegistry during cold commands/installation. Admin
resolves the callback before discovery, then freezes registrations. The page is
`/admin/app/release`; navigation is generated only when available and authorized.
Its empty API targets are valid for App-owned source data. Module handlers require
explicit actual guarded API targets; empty targets do not grant engine access.

## Partial presentation replacement

Save this as an application component. It preserves the public form controller,
controlled fields, error/conflict/proof handling and independent actions:

```vue
<script setup lang="ts">
import { AdminSection } from '@/runa/admin';
import type { AdminSectionPresentationProps } from '@/runa/admin';
defineProps<AdminSectionPresentationProps>();
</script>
<template>
  <section class="space-y-3">
    <p class="text-sm text-muted">Application administration</p>
    <AdminSection :controller="controller" :section="section" />
  </section>
</template>
```

For all sections, wrap the existing application with
`<AdminPresentationProvider :components="{ section: CustomerSection }">`.
For a single declared section, use its source-owned `component` key. Module keys
map to `frontend/components/<key>.vue`; independent App keys map to
`src/app/resources/js/components/admin/<key>.vue`. The compiled registry must match
the projected owner/manifest/build identities. A backend string never supplies an
import path. Do not substitute a page component where section props are required.

Field replacements use AdminFieldProps, emit `update:modelValue` and `availability`,
and respect readonly/busy/errors. Full page replacements receive AdminPageProps;
compose AdminForm with its existing form controller and AdminSectionContent for
each authorized section. Do not create a second useAdminForm for the same page.
Table replacements compose AdminResourceTable/useAdminTable and the public table
providers for selection/views/export/bulk. Filters, option labels, export columns,
counts and review differences remain constrained by current readable fields.

## Full content replacement

This replaces the ready Admin content and retains one session/entry owner. A full
content replacement owns its accessible navigation and page composition; compose
the default AdminApplication without its default slot when only changing sections.

```vue
<script setup lang="ts">
import { AdminApplication } from '@/runa/admin';
import type { AdminApplicationProps } from '@/runa/admin';
import CustomerAdminPage from './CustomerAdminPage.vue';
const props = defineProps<AdminApplicationProps>();
</script>
<template>
  <AdminApplication v-bind="props" v-slot="{ controller }">
    <main>
      <h1>Application administration</h1>
      <nav aria-label="Administrator panel">
        <ul>
          <li
            v-for="owner in controller.bootstrap.value?.owners"
            :key="owner.id"
          >
            <a
              :href="`/admin/${owner.id}/${owner.default_page}`"
              @click.prevent="
                controller.navigate({
                  owner: owner.id,
                  page: owner.default_page,
                })
              "
            >
              {{ owner.title }}
            </a>
          </li>
        </ul>
      </nav>
      <CustomerAdminPage
        v-if="controller.location.value"
        :admin="controller"
        :location="controller.location.value"
        :components="props.components"
      />
    </main>
  </AdminApplication>
</template>
```

`CustomerAdminPage.vue` explicitly composes the public page and form owners:

```vue
<script setup lang="ts">
import { toRef } from 'vue';
import {
  AdminLoading,
  AdminPage,
  useAdminForm,
  useAdminPage,
} from '@/runa/admin';
import type {
  AdminComponentRegistry,
  AdminController,
  AdminLocation,
} from '@/runa/admin';
import { Alert, Button } from '@/runa/ui';
const props = defineProps<{
  admin: AdminController;
  location: AdminLocation;
  components?: AdminComponentRegistry | undefined;
}>();
const page = useAdminPage(props.admin, toRef(props, 'location'));
const form = useAdminForm(page, {
  components: props.components,
  autoRead: false,
});
</script>
<template>
  <AdminLoading v-if="page.state.value === 'loading'" />
  <AdminPage
    v-else-if="page.state.value === 'ready' && page.page.value"
    :controller="page"
    :form="form"
    :components="components"
  />
  <Alert v-else tone="warning">
    This application administration page is unavailable.
    <Button @click="page.refresh">Retry</Button>
  </Alert>
</template>
```

Keep the supplied Auth route selection, or explicitly render the customer component
for `/admin` and its supported child paths from the application router. Other routes
continue through the supplied DefaultApplication. Pass the existing session when
already inside a session shell. Admin owns entry proof, authority fencing, paused/
pending session handling and disposal; customer markup is rendered only after ready
admission. Do not clone useAdmin internals or issue raw fetch writes. The full slot
does not automatically render a selected page: the child above composes the public
useAdminPage/useAdminForm controllers and AdminPage. It deliberately supplies its own
page presentation; to retain module-specific Core defaults, resolve authorized page
components with resolveAdminComponent and the supplied registry, or retain the
default shell and use partial presentation replacement. These
controllers own cleanup and beforeLeave guards, not the customer router's defaults.

## Authoring map

The following 18 topics route to canonical delivered contracts, rather than parallel
instructions or a second implementation of the engines.

| Topic                          | Owning guide and authoring choice                                                                                                                                     |
| ------------------------------ | --------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1. Architecture                | [Ownership](README.md#ownership-and-supported-entrypoints): business owners remain independent of Admin.                                                              |
| 2. Declarations                | [Provider/DTO signatures](declarations.md#optional-provider-and-descriptors): pages, sections, fields, bindings and entities.                                         |
| 3. Navigation                  | [Declarations](declarations.md): source order/group/default, authorized owner/page projection; account-menu entry only.                                               |
| 4. Composition                 | [Frontend contracts](frontend.md): shared shell, page/section/component boundaries and required-section failure.                                                      |
| 5. Tables                      | [Tables](../../development/frontend/tables.md): bounded cursor reads and existing selection/views/exports/bulk adapters.                                              |
| 6. Forms                       | [Forms](../../development/frontend/forms.md): one draft owner, optimistic revisions, overlap resolution, no silent write replay.                                      |
| 7. Components                  | [Replacement contracts](frontend.md): public props/slots/controllers, build-bound owned component keys.                                                               |
| 8. Backend operations          | [Owned handlers](declarations.md#owned-handler-signatures): explicit guarded APIs, validation, Policies and independent commands.                                     |
| 9. Widgets                     | [Typed results](declarations.md#owned-handler-signatures): safe values, persisted observation/expiry, unknown/stale/unavailable states.                               |
| 10. Cross-module participation | [Composition](composition.md): owned contributions, optional hosts and explicit compatible participant set.                                                           |
| 11. Transactions               | [Native phases](../../architecture/module-transactions.md): all owners validate locked pre-write state before apply; proof and Audit share the root.                  |
| 12. Authorization              | [Declarations](declarations.md): entry plus page/section/field/action grants and owning Policies/proofs; denied values disappear.                                     |
| 13. Availability               | [Operations](operations.md): cached health, dependency isolation, explicit diagnosis, CLI-only maintenance recovery.                                                  |
| 14. Core customization         | [Auth](../auth/administration.md), [Permissions](../permissions/administration.md), [Audit](../audit/administration.md): supported owning API/presentation contracts. |
| 15. Independent pages          | This guide's application page: no manifest, explicit App binding and lazy frozen registry.                                                                            |
| 16. Full replacement           | This guide and [frontend adoption](../../development/frontend/customization.md): application markup with platform orchestration.                                      |
| 17. Generator                  | [Module authoring](../../development/modules.md#generate-and-review): opt-in `admin`, exact file hashes, owned roots and development-only generation.                 |
| 18. Testing and limitations    | The acceptance section below and [testing](../../testing/strategy.md): meaningful owning scenarios and final rendered acceptance.                                     |

## Tests, delivery and limits

Run the generated owning test with the command in its central module guide. Add
tests for allowed/denied fields, readonly/unknown submission refusal, record Policies,
stale revisions/ABA, preview/proof changes, rollback/Audit and explicit participant
compatibility. Use [public test support](../../testing/strategy.md); application
tests must not import private owner suites. Mounted public-facade consumers should
exercise partial/full replacements, dirty reconciliation, forbidden/proof/session
transitions and cleanup. Final browser acceptance covers responsive sizes, light/
dark themes, keyboard/focus and PL/EN Admin text. Fixtures are examples, not a
claim that every customer replacement has passed visual review.

Owner fixtures exercise the generated business API without Admin, declaration
constructors, namespace/import boundaries, exact hash refusal and competing settings
providers. Customer section/full/table fixtures use only public facades. Update
review fixtures classify application providers, adapters, components, central guides
and tests as preserved customer files. Native delivery/update acceptance remains
in final roadmap verification. Protected-platform updates do not regenerate the
application or rewrite customer compatibility ranges.

Admin does not provide arbitrary CRUD, SQL, shell/Artisan, module install/update,
maintenance admission bypass, generic undo or a global search engine. Settings,
uploads, external effects and lifecycle/work retain their independent protocols;
metadata never makes them compatible with aggregate Save. Environment/technical
settings and source identity stay outside dynamic application branding. See
[application settings](operations.md#application-settings-contracts-and-storage) for the approved
safe-value declarations, runtime precedence and browser/mail/error fallback.

Custom Admin views import useAdminLocale from `@/runa/admin`; render metadata with
`t(page.title)`/`t(field.label)` and use its reactive locale for shared controls.
Supply localized title/group maps in source declarations and/or a Polish message
catalogue through AdminApplication.messages for App-owned English source strings.
Do not key/remount an editor on locale or translate stored business values.

Administrator-editable module settings use the reusable [named snapshot contract](settings-snapshots.md).
