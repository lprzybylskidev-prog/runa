<?php

namespace Runa\Modules\Auth\Definitions;

use Runa\Contracts\Modules\ApiDefinition;
use Runa\Contracts\Modules\ApiOperation;
use Runa\Contracts\Modules\ApiProvider;
use Runa\Modules\Auth\Actions\EntryTokens;
use Runa\Modules\Auth\Contracts\AuthInvitations;
use Runa\Modules\Auth\InvitationsProxy;

final class InvitationsApi implements ApiProvider
{
    public function definition(): ApiDefinition
    {
        return new ApiDefinition(AuthInvitations::class, InvitationsProxy::class, EntryTokens::class, '1.0.0', [
            'invite' => new ApiOperation('invitation.issue', 'runa.auth.invitations.manage'),
            'invalidate' => new ApiOperation('invitation.invalidate', 'runa.auth.invitations.manage'),
        ]);
    }
}
