<?php

namespace Runa\Modules\Auth\Contracts;

use Illuminate\Database\Connection;

/** Trusted local adapter implemented by Permissions; never a ModuleApis DTO.
 * Caller holds registry -> authority -> Auth user locks on the default connection.
 */
interface OperatorAuthority
{
    /** Assign the real protected system role within this transaction, or throw. */
    public function initialize(Connection $connection, string $publicId): void;

    /** Include inactive administrators; no ordinary permission/eligibility shortcut. */
    public function isAdministrator(Connection $connection, string $publicId): bool;
}
