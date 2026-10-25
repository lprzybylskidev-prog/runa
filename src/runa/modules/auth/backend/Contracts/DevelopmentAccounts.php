<?php

namespace Runa\Modules\Auth\Contracts;

/** Trusted development console contract; never bind as an ordinary runtime API. */
interface DevelopmentAccounts
{
    /** Safe local identifiers and setup status only; never credentials/tokens.
     * @return list<array{scenario: string, public_id: string, email: string, login: ?string, setup_required: bool, deleted: bool}>
     */
    public function accounts(): array;

    public function populate(): void;

    public function setup(string $scenario, #[\SensitiveParameter] string $password): bool;
}
