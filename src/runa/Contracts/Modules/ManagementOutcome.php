<?php

namespace Runa\Contracts\Modules;

use Runa\ModuleFramework\Management\DeclarationRules as Rules;

final readonly class ManagementOutcome
{
    public function __construct(
        public string $status, public ?ManagementRecord $record = null, public ?string $operationId = null,
    ) {
        if (! in_array($status, ['committed', 'pending', 'recovery_required'], true) || ($status !== 'committed' && ($record !== null || $operationId === null))) {
            throw new \InvalidArgumentException('Invalid management outcome.');
        }
        if ($operationId !== null) {
            Rules::revision($operationId);
        }
    }
}
