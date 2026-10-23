<?php

namespace Runa\Testing;

use NunoMaduro\Collision\Adapters\Laravel\Commands\TestCommand;

final class ApplicationTestCommand extends TestCommand
{
    /** @return list<string> */
    protected function commonArguments(): array
    {
        $arguments = parent::commonArguments();
        if ($this->usingPest()) {
            $arguments[] = '--test-directory=app/tests';
        }

        return $arguments;
    }
}
