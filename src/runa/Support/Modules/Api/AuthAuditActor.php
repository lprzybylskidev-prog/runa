<?php

namespace Runa\Support\Modules\Api;

use Runa\Modules\Audit\Contracts\AuditActor;
use Runa\Modules\Audit\Contracts\AuditActorResolver;
use Runa\Modules\Audit\Support\TrustedActor;
use Runa\Modules\Auth\Contracts\AuthorizationIdentity;

/** Optional safe snapshot enrichment; Audit remains usable without Auth or Permissions. */
final readonly class AuthAuditActor implements AuditActorResolver
{
    public function __construct(private TrustedActor $actors) {}

    public function resolve(): AuditActor
    {
        $actor = $this->actors->resolve();
        if ($actor->kind !== 'human' || $actor->publicId === null) {
            return $actor;
        }
        try {
            $identity = app(AuthorizationIdentity::class)->eligibility($actor->publicId)?->identity;
        } catch (\Throwable) {
            return $actor; // Optional label must never make the independent Audit store depend on Auth.
        }

        return $identity === null ? $actor : new AuditActor('human', $actor->publicId, mb_strcut($identity->displayName, 0, 160, 'UTF-8'));
    }
}
