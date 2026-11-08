<?php

namespace App\Modules\Reference\Actions;

use App\Modules\Reference\Models\Sample;
use App\Modules\Reference\Policies\SamplePolicy;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Runa\Contracts\Modules\ApiOperation;
use Runa\Contracts\Modules\MutationAccess;
use Runa\Contracts\Modules\MutationDefinition;
use Runa\Contracts\Modules\TransactionalMutation;
use Runa\Support\Modules\Api\OperationRejected;
use Runa\Support\Modules\Data\PublicReferenceRule;

/** Registry/authority/actor locks precede this record lock in the native runner. */
final class EditSample implements TransactionalMutation
{
    private ?Sample $sample = null;

    private bool $applied = false;

    public function __construct(private readonly string $publicId, private readonly string $revision, private readonly string $code)
    {
        if (! PublicReferenceRule::valid($publicId)) {
            throw new \InvalidArgumentException('Expected a UUIDv7 sample reference.');
        }
    }

    public function definition(): MutationDefinition
    {
        return new MutationDefinition('app.reference', 'samples.edit', [
            new MutationAccess('app.reference', new ApiOperation('samples.edit', 'app.reference.samples.write')),
            new MutationAccess('app.reference', new ApiOperation('samples.code.write', 'app.reference.samples.code.write')),
            new MutationAccess('app.reference', new ApiOperation('samples.read', 'app.reference.samples.read')),
            new MutationAccess('runa.permissions', new ApiOperation('permission.check')),
        ], [], ['app.reference.sample:'.$this->publicId]);
    }

    public function preflight(): void {}

    public function lock(): void
    {
        if ($this->sample !== null) {
            throw new \LogicException('Use a fresh sample mutation.');
        }
        $this->sample = Sample::where('public_id', $this->publicId)->lockForUpdate()->first()
            ?? throw new OperationRejected('reference_sample_missing', 'app.reference', 'samples.edit', 404);
    }

    public function validate(): void
    {
        $sample = $this->sample ?? throw new \LogicException('Sample is not locked.');
        app(SamplePolicy::class)->assert($sample, true);
        if (! hash_equals(SamplePolicy::revision($sample), $this->revision)) {
            throw new OperationRejected('reference_sample_stale', 'app.reference', 'samples.edit', 409);
        }
        Validator::make(['code' => $this->code], ['code' => ['required', 'string', 'max:40', 'regex:/\A[a-z][a-z0-9_-]*\z/D', Rule::unique(Sample::class, 'code')->ignore($sample->getKey())]])->validate();
    }

    public function apply(): void
    {
        if ($this->sample === null || $this->applied) {
            throw new \LogicException('Sample requires one apply.');
        }
        $this->sample->setAttribute('code', $this->code);
        $this->sample->setAttribute('revision', $this->sample->revision + 1);
        $this->sample->save(); // Auditable captures only approved code in this transaction.
        $this->applied = true;
    }

    public function result(): array
    {
        if (! $this->applied || $this->sample === null) {
            throw new \LogicException('Sample has no committed result.');
        }

        return app(SamplePolicy::class)->project($this->sample);
    }

    public function refused(string $reason): void
    {
        $this->sample = null;
        $this->applied = false;
    }
}
