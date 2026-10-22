<?php

namespace App\Modules\ModuleProbe\Definitions;

use Runa\Contracts\Modules\SettingDefinition;
use Runa\Contracts\Modules\SettingsContext;
use Runa\Contracts\Modules\SettingsHandler;
use Runa\Contracts\Modules\SettingsProvider;

/** Stateless reversible acceptance: no work or external effects to reconcile. */
final class ProbeSettings implements SettingsHandler, SettingsProvider
{
    public function settings(): array
    {
        return [
            new SettingDefinition('label', 'string', 'probe', ['pl' => 'Etykieta', 'en' => 'Label'], ['pl' => 'Etykieta sondy', 'en' => 'Probe label'], rules: ['max:40']),
            new SettingDefinition('credential', 'string', null, ['pl' => 'Sekret', 'en' => 'Secret'], ['pl' => 'Sekret testowy sondy', 'en' => 'Probe test secret'], sensitive: true, application: 'reinitialize', nullable: true),
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
