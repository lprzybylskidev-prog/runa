<?php

namespace Runa\Modules\Auth\Definitions;

use Runa\Contracts\Modules\ApiOperation;
use Runa\Contracts\Modules\HttpAccess;
use Runa\Contracts\Modules\HttpEndpoint;
use Runa\Contracts\Modules\HttpProvider;
use Runa\Modules\Auth\Http;

final class ProfileHttp implements HttpProvider
{
    public function schemaVersion(): int
    {
        return 1;
    }

    public function endpoints(): array
    {
        return [
            new HttpEndpoint('POST', 'delivery-preferences', new ApiOperation('delivery.own'), Http\DeliveryController::class, HttpAccess::Authenticated),
            new HttpEndpoint('POST', 'preferences', new ApiOperation('preferences.own'), Http\PreferencesController::class, HttpAccess::Authenticated),
            new HttpEndpoint('GET', 'profile', new ApiOperation('profile.show'), Http\ProfileShowController::class, HttpAccess::Authenticated),
            new HttpEndpoint('PATCH', 'profile', new ApiOperation('profile.edit'), Http\ProfileEditController::class, HttpAccess::Authenticated),
            new HttpEndpoint('POST', 'profile/lifecycle', new ApiOperation('profile.self_lifecycle'), Http\SelfLifecycleController::class, HttpAccess::Authenticated),
            new HttpEndpoint('GET', 'profile/history/detail', new ApiOperation('profile.history_detail'), Http\OwnHistoryDetailController::class, HttpAccess::Authenticated),
            new HttpEndpoint('GET', 'profile/history', new ApiOperation('profile.history'), Http\OwnHistoryController::class, HttpAccess::Authenticated),
            new HttpEndpoint('POST', 'profile/email', new ApiOperation('email.change'), Http\EmailChangeController::class, HttpAccess::Authenticated),
            new HttpEndpoint('POST', 'profile/secondary-emails', new ApiOperation('secondary.add_own'), Http\SecondaryAddController::class, HttpAccess::Authenticated),
            new HttpEndpoint('PATCH', 'profile/secondary-emails', new ApiOperation('secondary.change_own'), Http\SecondaryChangeController::class, HttpAccess::Authenticated),
            new HttpEndpoint('DELETE', 'profile/secondary-emails', new ApiOperation('secondary.remove_own'), Http\SecondaryRemoveController::class, HttpAccess::Authenticated),
            new HttpEndpoint('GET', 'profile/avatar', new ApiOperation('avatar.read'), Http\AvatarReadController::class, HttpAccess::Authenticated),
            new HttpEndpoint('POST', 'profile/avatar', new ApiOperation('avatar.upload'), Http\AvatarUploadController::class, HttpAccess::Authenticated),
            new HttpEndpoint('DELETE', 'profile/avatar', new ApiOperation('avatar.remove'), Http\AvatarRemoveController::class, HttpAccess::Authenticated),
            new HttpEndpoint('POST', 'users/avatar', new ApiOperation('avatar.admin_upload', 'runa.auth.avatars.manage'), Http\AdminAvatarUploadController::class, HttpAccess::Authenticated),
            new HttpEndpoint('DELETE', 'users/avatar', new ApiOperation('avatar.admin_remove', 'runa.auth.avatars.manage'), Http\AdminAvatarRemoveController::class, HttpAccess::Authenticated),
            new HttpEndpoint('POST', 'email/complete', new ApiOperation('email.complete'), Http\EmailCompleteController::class, HttpAccess::Public),
        ];
    }
}
