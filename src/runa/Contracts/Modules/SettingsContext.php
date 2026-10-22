<?php

namespace Runa\Contracts\Modules;

final readonly class SettingsContext
{
    /** Sensitive keys have only replaced=true, never values.
     * @param  array<string, array<string, mixed>>  $changes
     */
    public function __construct(
        public string $operationId, public string $module, public int $revision,
        public string $application, public array $changes, public ?SettingsActor $actor,
        public string $occurredAt,
    ) {}
}
