<?php

namespace Runa\Contracts\Modules;

/** Trusted update-only integration; never called through ordinary module ingress. */
interface PreparationParticipant
{
    public function preparing(PreparationContext $context): void;
}
