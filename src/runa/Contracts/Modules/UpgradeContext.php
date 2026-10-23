<?php

namespace Runa\Contracts\Modules;

use Illuminate\Database\Connection;
use Runa\Support\Modules\Data\ModuleSchema;

final readonly class UpgradeContext
{
    public function __construct(public string $operation, public string $module, public ?string $from, public string $to, public string $step, public Connection $connection, public ModuleSchema $schema) {}
}
