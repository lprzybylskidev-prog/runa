<?php

namespace App\Modules\Reference\Policies;

use App\Modules\Reference\Models\Sample;
use Runa\Modules\Permissions\Contracts\Permissions;
use Runa\Support\Modules\Api\ModuleApis;
use Runa\Support\Modules\Api\OperationRejected;

/** Shared demonstration records; deleted records are never editable or projected. */
final readonly class SamplePolicy
{
    public function allows(string $permission): bool
    {
        return app(ModuleApis::class)->resolve('runa.permissions', Permissions::class, '1.0.0')->allows('app.reference.'.$permission);
    }

    public function assert(Sample $sample, bool $write = false): void
    {
        if ($sample->trashed() || ! $this->allows($write ? 'samples.write' : 'samples.read')
            || ($write && ! $this->allows('samples.code.write'))) {
            throw new OperationRejected('reference_record_denied', 'app.reference', 'samples', 403);
        }
    }

    /** @return array{public_id:string,revision:string,code?:string} */
    public function project(Sample $sample): array
    {
        return $this->projectAll([$sample])[0];
    }

    /** Current grants are shared only within this one read, never cached on the policy.
     * @param list<Sample> $samples
     * @return list<array{public_id:string,revision:string,code?:string}> */
    public function projectAll(array $samples): array
    {
        if (! $this->allows('samples.read')) {
            throw new OperationRejected('reference_record_denied', 'app.reference', 'samples.read', 403);
        }
        $code = $this->allows('samples.code.read');

        return array_map(static function (Sample $sample) use ($code): array {
            if ($sample->trashed()) {
                throw new OperationRejected('reference_record_denied', 'app.reference', 'samples.read', 403);
            }

            return ['public_id' => $sample->public_id, 'revision' => self::revision($sample)]
                + ($code ? ['code' => $sample->code] : []);
        }, $samples);
    }

    public static function revision(Sample $sample): string
    {
        return (string) $sample->revision;
    }
}
