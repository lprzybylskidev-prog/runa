<?php

namespace Runa\Modules\Auth\Contracts;

use Runa\Modules\Auth\Support\Identifiers;

/** Credentials deliberately stay outside module API values. */
final readonly class NewIdentity
{
    public string $email;

    public ?string $login;

    public function __construct(
        string $email, public ?string $name = null, public ?string $firstName = null,
        public ?string $surname = null, ?string $login = null,
        public bool $active = false, public bool $approved = false,
    ) {
        $this->email = Identifiers::email($email);
        $this->login = $login === null ? null : Identifiers::login($login);
        if (! (($name !== null && $firstName === null && $surname === null)
            || ($name === null && $firstName !== null && $surname !== null))) {
            throw new \InvalidArgumentException('Supply a name or both first name and surname.');
        }
        foreach ([$name, $firstName, $surname] as $part) {
            if ($part !== null && (trim($part) !== $part || $part === '' || mb_strlen($part) > 160 || preg_match('/[\p{C}]/u', $part))) {
                throw new \InvalidArgumentException('Invalid identity name.');
            }
        }
    }
}
