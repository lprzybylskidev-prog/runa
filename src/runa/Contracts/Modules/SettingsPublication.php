<?php

namespace Runa\Contracts\Modules;

/** Safe changes only; the revision is the actual new stored revision. */
final readonly class SettingsPublication
{
    /** @param array<string, array<string, mixed>> $changes */
    public function __construct(public SettingsContext $context, public int $revision, public string $phase, public array $changes) {}
}
