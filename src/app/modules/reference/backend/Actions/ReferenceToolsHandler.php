<?php

namespace App\Modules\Reference\Actions;

use App\Modules\Reference\Contracts\ReferenceTools;
use App\Modules\Reference\Definitions\ReferenceSettings;
use App\Modules\Reference\Management\ReferenceSnapshots;
use App\Modules\Reference\Models\Sample;
use App\Modules\Reference\Policies\SamplePolicy;
use App\Modules\Reference\Queries\ReadSamples;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;
use Runa\Contracts\Modules\ManagementRead;
use Runa\Contracts\Modules\ManagementWrite;
use Runa\Modules\Audit\Contracts\AuditEntity;
use Runa\Modules\Audit\Contracts\AuditQuery;
use Runa\Modules\Audit\Contracts\AuditReads;
use Runa\Support\Modules\Api\ModuleApis;
use Runa\Support\Modules\Api\OperationRejected;
use Runa\Support\Modules\Data\ModuleTransactions;
use Runa\Support\Modules\Data\PublicReferenceRule;
use Runa\Support\Modules\Settings\ModuleSettings;
use Runa\Support\Modules\Work\ModuleWork;

/** Owning operations; Admin and standalone HTTP consume the same guarded API. */
final readonly class ReferenceToolsHandler implements ReferenceTools
{
    public function __construct(private ModuleSettings $settingsStore, private ModuleApis $apis,
        private ModuleTransactions $transactions, private SamplePolicy $policy, private ModuleWork $managedWork) {}

    public function samples(): array
    {
        return app(ReadSamples::class)->read();
    }

    public function editSample(string $publicId, string $revision, string $code): array
    {
        return $this->transactions->run([new EditSample($publicId, $revision, $code)])['app.reference.samples.edit'];
    }

    public function settings(): array
    {
        $state = $this->settingsStore->readAuthorized('app.reference');
        $items = [];
        foreach ($state['settings'] as $key => $setting) {
            $definition = $setting['definition'];
            $items[$key] = ['names' => $definition->names, 'descriptions' => $definition->descriptions,
                'sensitive' => $definition->sensitive, 'application' => $definition->application]
                + ($definition->sensitive ? ['set' => $setting['set']] : ['value' => $setting['value']]);
        }

        return ['revision' => $state['revision'], 'review_id' => $state['review_id'], 'settings' => $items];
    }

    public function reviewSettings(#[\SensitiveParameter] array $patch, int $revision, string $reviewId): array
    {
        $changes = $this->candidate($patch, $revision, $reviewId);

        // Use Laravel authenticated encryption so the review exposes no guessable secret digest.
        return ['revision' => $revision, 'review_id' => $reviewId, 'changes' => $changes,
            'review' => Crypt::encryptString($this->fingerprint($patch, $revision, $reviewId))];
    }

    public function publishSettings(#[\SensitiveParameter] array $patch, int $revision, string $reviewId, string $review): array
    {
        $this->candidate($patch, $revision, $reviewId);
        try {
            $expected = Crypt::decryptString($review);
        } catch (DecryptException) {
            throw new OperationRejected('reference_review_stale', 'app.reference', 'settings.publish', 409);
        }
        if (! hash_equals($this->fingerprint($patch, $revision, $reviewId), $expected)) {
            throw new OperationRejected('reference_review_stale', 'app.reference', 'settings.publish', 409);
        }
        // Native settings owns the independent runner, revalidation, one-use proof and Audit.
        $result = $this->settingsStore->update('app.reference', $patch, $revision, $reviewId);

        return ['operation' => $result->operationId, 'status' => $result->status];
    }

    /** @param array<string,mixed> $patch
     * @return array<string,mixed> */
    private function candidate(#[\SensitiveParameter] array $patch, int $revision, string $reviewId): array
    {
        $state = $this->settingsStore->readAuthorized('app.reference');
        if ($state['revision'] !== $revision || $state['review_id'] !== $reviewId) {
            throw new OperationRejected('reference_settings_stale', 'app.reference', 'settings.review', 409);
        }
        if ($patch === [] || array_diff(array_keys($patch), ['label', 'credential']) !== []) {
            throw new OperationRejected('reference_settings_invalid', 'app.reference', 'settings.review', 422);
        }
        if (! $this->policy->allows('settings.write')
            || (array_key_exists('credential', $patch) && ! $this->policy->allows('settings.credential.write'))) {
            throw new OperationRejected('reference_settings_denied', 'app.reference', 'settings.review', 403);
        }
        $rules = [];
        foreach ((new ReferenceSettings)->settings() as $definition) {
            $rules[$definition->key] = [$definition->nullable ? 'nullable' : 'required', 'string', ...$definition->rules];
        }
        Validator::make($patch, array_intersect_key($rules, $patch))->validate();
        $changes = [];
        foreach ($patch as $key => $value) {
            if ($key === 'credential') {
                $changes[$key] = ['replaced' => true];
            } elseif ($state['settings'][$key]['value'] !== $value) {
                $changes[$key] = ['old' => $state['settings'][$key]['value'], 'new' => $value];
            }
        }

        return $changes;
    }

    /** @param array<string,mixed> $patch */
    private function fingerprint(#[\SensitiveParameter] array $patch, int $revision, string $reviewId): string
    {
        ksort($patch);

        return hash('sha256', serialize([$patch, $revision, $reviewId]));
    }

    public function snapshots(?string $publicId = null): array
    {
        return app(ReferenceSnapshots::class)->read(new ManagementRead($publicId))->values;
    }

    public function captureSnapshot(string $name, int $revision, string $reviewId): array
    {
        $outcome = app(ReferenceSnapshots::class)->execute(new ManagementWrite(
            'capture', null, $revision.':'.$reviewId, ['name' => $name]));

        return ['status' => $outcome->status];
    }

    public function deleteSnapshot(string $publicId, int $revision, string $reviewId): array
    {
        $outcome = app(ReferenceSnapshots::class)->execute(new ManagementWrite(
            'delete', null, $revision.':'.$reviewId, ['snapshot_id' => $publicId], confirmed: true));

        return ['status' => $outcome->status];
    }

    public function history(?string $sample = null, ?string $before = null): array
    {
        if ($sample !== null) {
            if (! PublicReferenceRule::valid($sample)) {
                throw new \InvalidArgumentException('Expected a UUIDv7 sample reference.');
            }
            $this->policy->assert(Sample::where('public_id', $sample)->firstOrFail());
        }
        // Broad history requires AuditReads' own grant too. No field values are reprojected.
        $page = $this->apis->resolve('runa.audit', AuditReads::class, '1.1.0')->page(new AuditQuery(
            module: 'app.reference', limit: 25,
            entity: $sample === null ? null : new AuditEntity('app.reference', 'sample', $sample), before: $before));

        return ['items' => array_map(static fn ($entry) => ['public_id' => $entry->reference->publicId,
            'occurred_at' => $entry->reference->occurredAt, 'action' => $entry->action, 'outcome' => $entry->outcome], $page->entries),
            'next' => $page->nextBefore];
    }

    public function dispatch(): array
    {
        return $this->transactions->run([new DispatchWork])['app.reference.work.dispatch'];
    }

    public function work(): array
    {
        // This bounded owner listing never accepts an arbitrary work/module identity.
        return ['items' => array_map(static fn ($status) => ['work_id' => $status->workId,
            'operation' => $status->operation, 'status' => $status->status, 'progress' => $status->progress,
            'attempts' => $status->attempts, 'cancellation_requested' => $status->cancellationRequested],
            $this->managedWork->history('app.reference', 25))];
    }
}
