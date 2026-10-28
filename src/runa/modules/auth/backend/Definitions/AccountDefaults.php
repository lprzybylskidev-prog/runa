<?php

namespace Runa\Modules\Auth\Definitions;

use Runa\Modules\Auth\Contracts\PreferenceDefinition;

/** Pure declarations shared by Auth's SettingsProvider; no writable second configuration. */
final class AccountDefaults
{
    /**
     * @return list<PreferenceDefinition>
     */
    public static function preferences(): array
    {
        return [
            new PreferenceDefinition('global', 'locale', 'string', 'pl', rules: ['in:pl,en']),
            new PreferenceDefinition('global', 'theme', 'string', 'system', rules: ['in:light,dark,system']),
            new PreferenceDefinition('navigation', 'collapsed', 'boolean', false),
            new PreferenceDefinition('navigation', 'expanded', 'array', [], rules: ['max:200']),
        ];
    }
}
