<?php

namespace Runa\Testing\Backend;

use NunoMaduro\Collision\Adapters\Laravel\Commands\TestCommand;

final class ApplicationTestCommand extends TestCommand
{
    public function handle(): int
    {
        if ($this->input->hasParameterOption(['--list-tests', '--list-tests-xml', '--list-suites', '--list-groups'])) {
            return (int) parent::handle();
        }

        // Match Collision's native environment handoff before the cleanup child
        // loads .env.testing; do not inherit the booted development credentials.
        $this->clearEnv();

        return TestRun::execute(dirname(base_path()), $this->output, fn (): int => (int) parent::handle());
    }

    /** @return list<string> */
    protected function commonArguments(): array
    {
        $arguments = parent::commonArguments();
        if ($this->usingPest()) {
            $arguments[] = '--test-directory=../tests/backend/app';
        }

        return $arguments;
    }
}
