<?php

namespace Runa\Support\Modules\Updates;

use Runa\Contracts\Modules\PreparationContext;
use Runa\Contracts\Modules\PreparationParticipant;

/** Before Audit installation there is no history store to write. */
final class NoPreparationParticipation implements PreparationParticipant
{
    public function preparing(PreparationContext $context): void
    {
        if ($context->connection->table('shared.modules')->where('module_id', 'runa.audit')->whereNotNull('installed_version')->exists()) {
            throw new \RuntimeException('Installed Audit requires transactional participation.');
        }
    }
}
