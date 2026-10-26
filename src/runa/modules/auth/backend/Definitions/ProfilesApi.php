<?php

namespace Runa\Modules\Auth\Definitions;

use Runa\Contracts\Modules\ApiDefinition;
use Runa\Contracts\Modules\ApiOperation;
use Runa\Contracts\Modules\ApiProvider;
use Runa\Modules\Auth\Actions\ManageProfiles;
use Runa\Modules\Auth\Contracts\AuthProfiles;
use Runa\Modules\Auth\ProfilesProxy;

final class ProfilesApi implements ApiProvider
{
    public function definition(): ApiDefinition
    {
        return new ApiDefinition(AuthProfiles::class, ProfilesProxy::class, ManageProfiles::class, '1.0.0', [
            'edit' => new ApiOperation('profile.edit', 'runa.auth.profiles.manage'),
            'changeEmail' => new ApiOperation('email.admin_change', 'runa.auth.emails.manage'),
            'replaceVerifiedEmail' => new ApiOperation('email.admin_bypass', 'runa.auth.emails.verify_bypass'),
            'requireVerification' => new ApiOperation('email.require_verification', 'runa.auth.emails.reverify'),
            'addSecondary' => new ApiOperation('secondary.add', 'runa.auth.secondary.manage'),
            'changeSecondary' => new ApiOperation('secondary.change', 'runa.auth.secondary.manage'),
            'removeSecondary' => new ApiOperation('secondary.remove', 'runa.auth.secondary.manage'),
            'notificationEmails' => new ApiOperation('addresses.notification', 'runa.auth.addresses.read'),
        ]);
    }
}
