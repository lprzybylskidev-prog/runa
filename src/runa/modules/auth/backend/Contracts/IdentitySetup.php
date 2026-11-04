<?php

namespace Runa\Modules\Auth\Contracts;

/** Initial Auth state only. Secrets never participate in a read or serialized result. */
final readonly class IdentitySetup implements \JsonSerializable
{
    public function __construct(
        public string $credentials = 'none',
        #[\SensitiveParameter] public ?string $password = null,
        public bool $emailVerified = false,
        public ?string $activateAt = null,
        public ?string $deactivateAt = null,
        public bool $securityLocked = false,
        public ?string $attemptLockedUntil = null,
        public bool $passwordChangeRequired = false,
    ) {
        if (! in_array($credentials, ['none', 'invitation', 'password'], true)
            || ($credentials === 'password') !== ($password !== null)
            || ($passwordChangeRequired && $credentials !== 'password')) {
            throw new \InvalidArgumentException('Invalid initial credential options.');
        }
        foreach ([$activateAt, $deactivateAt, $attemptLockedUntil] as $moment) {
            if ($moment !== null) {
                new LifecycleChange('schedule_activation', $moment);
            }
        }
    }

    /** @return array<string, bool|string|null> */
    public function jsonSerialize(): array
    {
        return ['credentials' => $this->credentials, 'emailVerified' => $this->emailVerified,
            'activateAt' => $this->activateAt, 'deactivateAt' => $this->deactivateAt,
            'securityLocked' => $this->securityLocked, 'attemptLockedUntil' => $this->attemptLockedUntil,
            'passwordChangeRequired' => $this->passwordChangeRequired];
    }

    /** @return array<string, bool|string|null> */
    public function __debugInfo(): array
    {
        return ['credentials' => $this->credentials, 'password' => '[REDACTED]'];
    }
}
