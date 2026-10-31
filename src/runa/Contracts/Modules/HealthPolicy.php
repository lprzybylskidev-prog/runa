<?php

namespace Runa\Contracts\Modules;

/** Source-owned, read-only checks. Never return/log sensitive upstream diagnostics. */
interface HealthPolicy
{
    public function schemaVersion(): int;

    /** Source-only policy. The revision covers all module-owned policy inputs. */
    public function definition(): HealthDefinition;

    /** Honor the deadline and bound network/IO calls. False is a safe failed observation. */
    public function check(string $check, HealthContext $context): bool;
}
