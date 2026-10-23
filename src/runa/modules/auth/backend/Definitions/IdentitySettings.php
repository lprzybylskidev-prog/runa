<?php

namespace Runa\Modules\Auth\Definitions;

use Runa\Contracts\Modules\SettingDefinition;
use Runa\Contracts\Modules\SettingsContext;
use Runa\Contracts\Modules\SettingsHandler;
use Runa\Contracts\Modules\SettingsProvider;

/** Immediate lookup/future-deletion policy. Existing ownership is never reassigned. */
final class IdentitySettings implements SettingsHandler, SettingsProvider
{
    public function settings(): array
    {
        return [
            new SettingDefinition('identifier_mode', 'string', 'email', ['pl' => 'Identyfikator logowania', 'en' => 'Login identifier'],
                ['pl' => 'E-mail, login lub oba identyfikatory.', 'en' => 'Email, login or either identifier.'], rules: ['in:email,login,either']),
            new SettingDefinition('reuse_deleted_identifiers', 'boolean', false, ['pl' => 'Ponowne użycie identyfikatorów', 'en' => 'Reuse deleted identifiers'],
                ['pl' => 'Zwalnia identyfikatory przy kolejnych usunięciach; nie przenosi historii.', 'en' => 'Release identifiers on subsequent deletions; never transfer history.']),
        ];
    }

    public function valid(array $values): bool
    {
        return true;
    }

    public function handler(): string
    {
        return self::class;
    }

    public function apply(SettingsContext $context): bool
    {
        return true;
    }

    public function compensate(SettingsContext $context): bool
    {
        return true;
    }
}
