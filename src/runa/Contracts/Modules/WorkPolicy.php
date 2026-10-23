<?php

namespace Runa\Contracts\Modules;

/** Module-owned, idempotent policy. Methods return protocol decisions, never execute business units. */
interface WorkPolicy
{
    public function schemaVersion(): int;

    public function pending(WorkStatus $work, bool $available): WorkDecision;

    public function checkpoint(WorkStatus $work): WorkDecision;
}
