<?php

namespace App\Modules\Reference\Definitions;

use Runa\Contracts\Modules\ApiOperation;
use Runa\Contracts\Modules\MutationProof;
use Runa\Contracts\Modules\SettingDefinition;
use Runa\Contracts\Modules\SettingsAccess;
use Runa\Contracts\Modules\SettingsAccessProvider;
use Runa\Contracts\Modules\SettingsContext;
use Runa\Contracts\Modules\SettingsHandler;
use Runa\Contracts\Modules\SettingsProvider;

/** Stateless reversible acceptance: no work or external effects to reconcile. */
final class ReferenceSettings implements SettingsAccessProvider, SettingsHandler, SettingsProvider
{
    public function access(): SettingsAccess
    {
        return new SettingsAccess(
            new ApiOperation('settings.read', 'app.reference.settings.read'),
            new ApiOperation('settings.publish', 'app.reference.settings.write'),
            readFields: ['credential' => 'app.reference.settings.credential.read'],
            writeFields: ['credential' => 'app.reference.settings.credential.write'],
            proof: new MutationProof('app.reference.settings.publish', 'password'),
        );
    }

    public function settings(): array
    {
        return [
            new SettingDefinition('label', 'string', 'reference', ['pl' => 'Etykieta', 'en' => 'Label'], ['pl' => 'Etykieta przykładu', 'en' => 'Reference label'], rules: ['max:40']),
            new SettingDefinition('credential', 'string', null, ['pl' => 'Sekret', 'en' => 'Secret'], ['pl' => 'Sekret testowy przykładu', 'en' => 'Reference test secret'], sensitive: true, application: 'reinitialize', nullable: true, rules: ['max:512']),
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
