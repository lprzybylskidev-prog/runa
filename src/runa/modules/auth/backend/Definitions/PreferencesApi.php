<?php

namespace Runa\Modules\Auth\Definitions;

use Runa\Contracts\Modules\ApiDefinition;
use Runa\Contracts\Modules\ApiOperation;
use Runa\Contracts\Modules\ApiProvider;
use Runa\Modules\Auth\Actions\AccountPreferences;
use Runa\Modules\Auth\Contracts\AuthPreferences;
use Runa\Modules\Auth\PreferencesProxy;

final class PreferencesApi implements ApiProvider
{
    public function definition(): ApiDefinition
    {
        return new ApiDefinition(AuthPreferences::class, PreferencesProxy::class, AccountPreferences::class, '1.0.0', [
            'read' => new ApiOperation('preferences.read'), 'write' => new ApiOperation('preferences.write'),
            'reset' => new ApiOperation('preferences.reset'), 'resetAccount' => new ApiOperation('preferences.admin_reset', 'runa.auth.preferences.reset'),
        ]);
    }
}
