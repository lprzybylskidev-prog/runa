<?php

namespace App\Modules\Reference\Queries;

use App\Modules\Reference\Models\Sample;
use App\Modules\Reference\Policies\SamplePolicy;
use Illuminate\Support\Facades\DB;

/** Bounded owning read; projection checks each record and reads each field grant once. */
final readonly class ReadSamples
{
    public function __construct(private SamplePolicy $policy) {}

    /** @return array{items:list<array{public_id:string,revision:string,code?:string}>} */
    public function read(): array
    {
        return DB::transaction(function (): array {
            $samples = array_values(Sample::orderBy('id')->limit(20)->get()->all());

            return ['items' => $this->policy->projectAll($samples)];
        });
    }
}
