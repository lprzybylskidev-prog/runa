<?php

namespace Runa\Integration\Core;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Str;
use Runa\Contracts\Modules\OperationAuthorizer;
use Runa\Contracts\Modules\OperationContext;
use Runa\ModuleFramework\Api\PrincipalScope;
use Runa\ModuleFramework\ModuleRegistry;

/** Ordinary namespaced abilities only. Record-level policies always retain their decision. */
final readonly class PermissionGate
{
    public function __construct(private PrincipalScope $principals, private OperationAuthorizer $authorizer, private ModuleRegistry $registry) {}

    /** @param array<mixed> $arguments */
    public function before(?Authenticatable $user, string $ability, array $arguments = []): ?bool
    {
        if (preg_match('/\A((?:runa|app)\.[a-z][a-z0-9_]*)\.[a-z][a-z0-9_.]*\z/D', $ability, $match) !== 1) {
            return null;
        }
        try {
            $principal = $this->principals->current();
            // No client model/public_id property is accepted as the trusted actor.
            if ($principal === null || $user === null || $user->getAuthIdentifier() !== $principal->publicId) {
                return false;
            }
            $context = new OperationContext($match[1], 'permission.check', (string) Str::uuid7(), (string) Str::uuid7(), $this->registry->snapshot()->generation);
            $allowed = $this->authorizer->allows($context, $ability);

            return $allowed ? ($arguments === [] ? true : null) : false;
        } catch (\Throwable) {
            return false;
        }
    }
}
