<?php

namespace Runa\Modules\Auth\Contracts;

final readonly class LifecycleChange
{
    public function __construct(public string $operation, public ?string $at = null, public ?bool $enabled = null)
    {
        $dates = ['schedule_activation', 'schedule_deactivation', 'set_expiry', 'set_attempt_lock'];
        $flags = ['set_security_lock', 'require_password_change'];
        if (! in_array($operation, [...$dates, ...$flags, 'activate', 'deactivate', 'approve', 'delete', 'restore'], true)
            || (! in_array($operation, $dates, true) && $at !== null)
            || (in_array($operation, $flags, true) !== ($enabled !== null))) {
            throw new \InvalidArgumentException('Invalid lifecycle change.');
        }
        if ($at !== null) {
            $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d\TH:i:s\Z', $at, new \DateTimeZone('UTC'));
            if ($parsed === false || $parsed->format('Y-m-d\TH:i:s\Z') !== $at) {
                throw new \InvalidArgumentException('Lifecycle moments require canonical UTC seconds.');
            }
        }
    }
}
