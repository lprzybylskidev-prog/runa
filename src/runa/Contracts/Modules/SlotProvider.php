<?php

namespace Runa\Contracts\Modules;

interface SlotProvider
{
    /** @return list<SlotHost> */
    public function hosts(): array;

    /** @return list<SlotContribution> */
    public function contributions(): array;
}
