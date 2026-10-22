<?php

namespace Runa\Contracts\Modules;

interface SettingsHandler
{
    /** Candidate is installed behind a fence. Read private runtime values explicitly if needed.
     * Reinitialization must use module-owned work/checkpoint decisions; false means safely refuse.
     */
    public function apply(SettingsContext $context): bool;

    /** Old values are restored behind the fence first. Must be idempotent by operation ID.
     * Return true only after effects of any interrupted/failed apply are safely reconciled.
     */
    public function compensate(SettingsContext $context): bool;
}
