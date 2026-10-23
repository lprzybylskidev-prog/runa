<?php

namespace Runa\Modules\Auth\Contracts;

/** Complete naming/login replacement; email has a separate verified workflow. */
final readonly class ProfileChange
{
    public ?string $login;

    public function __construct(public ?string $name = null, public ?string $firstName = null, public ?string $surname = null, ?string $login = null)
    {
        $validated = new NewIdentity('validation@example.test', $name, $firstName, $surname, $login);
        $this->login = $validated->login;
    }
}
