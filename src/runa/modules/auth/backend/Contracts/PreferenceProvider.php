<?php

namespace Runa\Modules\Auth\Contracts;

/** Optional extension of a module's existing source-owned SettingsProvider. */
interface PreferenceProvider
{
    /** @return list<PreferenceDefinition> */
    public function preferences(): array;

    public function validPreference(string $view, string $key, mixed $value): bool;

    /** Return a current value or null to discard an obsolete representation. */
    public function migratePreference(string $view, string $key, mixed $value, int $version): mixed;
}
