<?php

namespace Runa\Providers;

use Illuminate\Support\ServiceProvider;
use Runa\Contracts\Modules\AuthorityGuard;
use Runa\Contracts\Modules\OperationAuthorizer;
use Runa\Contracts\Modules\PreparationParticipant;
use Runa\Contracts\Modules\SettingsParticipant;
use Runa\Integration\Core\AuditParticipation;
use Runa\Integration\Core\AuthAuditActor;
use Runa\Integration\Core\AuthAuthorizationIdentity;
use Runa\Integration\Core\OperatorPermissions;
use Runa\Integration\Core\PermissionAuthorizer;
use Runa\Integration\Core\PermissionUserSelection;
use Runa\Integration\Core\ProtectedAuthority;
use Runa\Modules\Audit\Contracts\AuditActorResolver;
use Runa\Modules\Audit\Contracts\AuditSuppression;
use Runa\Modules\Auth\Contracts\AuthorizationIdentity;
use Runa\Modules\Auth\Contracts\BulkUserSelection;
use Runa\Modules\Auth\Contracts\OperatorAuthority;

final class CoreIntegrationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(AuditSuppression::class);
        $this->app->bindIf(AuditActorResolver::class, AuthAuditActor::class);
        $this->app->bindIf(AuthorityGuard::class, ProtectedAuthority::class);
        $this->app->bindIf(OperatorAuthority::class, OperatorPermissions::class);
        $this->app->bindIf(BulkUserSelection::class, PermissionUserSelection::class);
        $this->app->bindIf(SettingsParticipant::class, AuditParticipation::class);
        $this->app->bindIf(PreparationParticipant::class, AuditParticipation::class);
        $this->app->bindIf(OperationAuthorizer::class, PermissionAuthorizer::class);
        $this->app->bindIf(AuthorizationIdentity::class, AuthAuthorizationIdentity::class);
    }
}
