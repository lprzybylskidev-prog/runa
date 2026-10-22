<?php

namespace Runa\Contracts\Modules;

interface DiagnosticHandler
{
    /** Trusted CLI diagnostic/recovery only. Never expose arbitrary operational work here.
     * @param  array<array-key, mixed>  $payload
     * @return array<array-key, mixed>
     */
    public function diagnose(array $payload): array;
}
