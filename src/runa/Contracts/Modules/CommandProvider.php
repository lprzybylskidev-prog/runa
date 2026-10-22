<?php

namespace Runa\Contracts\Modules;

interface CommandProvider
{
    /** @return list<CommandDefinition> */
    public function commands(): array;
}
