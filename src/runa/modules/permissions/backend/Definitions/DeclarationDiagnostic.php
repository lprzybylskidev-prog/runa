<?php

namespace Runa\Modules\Permissions\Definitions;

use Runa\Contracts\Modules\DiagnosticHandler;
use Runa\Modules\Permissions\Actions\SynchronizeDeclarations;

/** Trusted operator read only, usable while ordinary ingress is fenced for updates. */
final readonly class DeclarationDiagnostic implements DiagnosticHandler
{
    public function __construct(private SynchronizeDeclarations $sync) {}

    public function diagnose(array $payload): array
    {
        if (array_keys($payload) !== ['module'] || ! is_string($payload['module'])) {
            throw new \InvalidArgumentException('Declaration preview requires exactly one module identifier.');
        }
        $preview = $this->sync->preview($payload['module']);

        return ['module' => $preview->module, 'fingerprint' => $preview->fingerprint, 'changes' => $preview->changes, 'affected_users' => $preview->affectedUsers];
    }
}
