<?php

namespace Runa\Contracts\Modules;

/** Trusted owning adapter. Never transported through HTTP or ModuleApis. */
interface ManagementParticipant
{
    public function prepare(ManagementWrite $input): TransactionalMutation;

    /** @param array<string,mixed> $result */
    public function project(array $result): ManagementOutcome;
}
