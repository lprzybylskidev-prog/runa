<?php

namespace Runa\Contracts\Modules;

use Illuminate\Database\Connection;

/** Reserved framework integration; not an application event-writing API. */
interface OperationalParticipant
{
    /** @param array<string, scalar|null> $context Safe identities/revisions only; never configuration or runner tokens. */
    public function recording(Connection $connection, string $module, string $operation, array $context): void;
}
