<?php

namespace Runa\Contracts\Modules;

/** Source-owned, read-only checks. Never return/log sensitive upstream diagnostics. */
interface HealthPolicy
{
    public function schemaVersion(): int;

    /** Reject unknown/forbidden overrides. The revision covers module-owned policy inputs.
     * @param  array<string, mixed>  $overrides
     */
    public function definition(array $overrides): HealthDefinition;

    /** Honor the deadline and bound network/IO calls. False is a safe failed observation. */
    public function check(string $check, HealthContext $context): bool;
}
