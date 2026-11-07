<?php

namespace App\Modules\Reference\Queries;

use App\Modules\Reference\Models\Sample;
use App\Modules\Reference\Policies\SamplePolicy;
use Illuminate\Support\Facades\DB;

/** Bounded owning read; projection checks each record and reads each field grant once. */
final readonly class ReadSamples
{
    public function __construct(private SamplePolicy $policy) {}

    /** @return array<string,mixed> */
    public function read(): array
    {
        return DB::transaction(fn () => ['items' => $this->policy->projectAll(Sample::orderBy('id')->limit(20)->get()->all())]);
    }
}
