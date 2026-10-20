<?php

namespace Runa\Management;

final class DoctorCommand extends ToolingCommand
{
    protected $signature = 'runa:doctor {area?} {--plan : Print a host plan without execution} {--host-request= : Internal request binding}';

    protected $description = 'Run deterministic diagnostics; no repair or deployment';

    protected string $tool = 'doctor';
}
