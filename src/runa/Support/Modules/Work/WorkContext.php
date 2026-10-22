<?php

namespace Runa\Support\Modules\Work;

use Runa\Contracts\Modules\WorkStatus;

final readonly class WorkContext
{
    public function __construct(public string $workId, private string $token, private ModuleWork $work) {}

    /** Call outside atomic units, before the next side effect. May stop this attempt. */
    public function checkpoint(string $progress): WorkStatus
    {
        return $this->work->checkpoint($this->workId, $this->token, $progress);
    }
}
