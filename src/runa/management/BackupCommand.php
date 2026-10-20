<?php

namespace Runa\Management;

final class BackupCommand extends ToolingCommand
{
    protected $signature = 'runa:backup {action} {backup_id?} {--confirm-runa-prod-restore} {--plan : Print a host plan without execution} {--host-request= : Internal request binding}';

    protected $description = 'Create, verify or explicitly restore coordinated production backups';

    protected string $tool = 'backup';
}
