<?php

namespace Runa\Management;

final class HookCommand extends ToolingCommand
{
    protected $signature = 'runa:hook {action} {message_file?} {--repository= : Internal staged-index repository context} {--plan : Print a host plan without execution} {--host-request= : Internal request binding}';

    protected $description = 'Run versioned staged-content and commit-message hooks';

    protected string $tool = 'hook';
}
