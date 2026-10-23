<?php

namespace Runa\Modules\Auth\Contracts;

/** Reasons are for authorized server consumers, never anonymous login responses. */
final readonly class Eligibility
{
    /** @param list<string> $blockers
     * @param  list<string>  $restrictions
     */
    public function __construct(public Identity $identity, public array $blockers, public array $restrictions, public int $securityVersion) {}

    public function mayAuthenticate(): bool
    {
        return $this->blockers === [];
    }

    public function mayOperate(): bool
    {
        return $this->mayAuthenticate() && $this->restrictions === [];
    }
}
