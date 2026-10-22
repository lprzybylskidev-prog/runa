<?php

namespace Runa\Contracts\Modules;

/** Future Audit integration payload. No audit store or substitute audit logger. */
final readonly class SettingChange
{
    /** @param array<string, mixed> $change */
    public function __construct(public SettingsContext $context, public string $key, public array $change, public string $outcome) {}
}
