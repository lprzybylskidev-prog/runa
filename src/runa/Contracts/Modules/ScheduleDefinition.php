<?php

namespace Runa\Contracts\Modules;

use Cron\CronExpression;
use InvalidArgumentException;

final readonly class ScheduleDefinition
{
    public function __construct(public string $key, public string $command, public string $expression, public ?int $overlapMinutes = null, public int $schemaVersion = 1)
    {
        new ApiOperation($key);
        new ApiOperation($command);
        if ($schemaVersion !== 1 || strlen($expression) > 128 || ! CronExpression::isValidExpression($expression) || ($overlapMinutes !== null && ($overlapMinutes < 1 || $overlapMinutes > 1440))) {
            throw new InvalidArgumentException('Invalid module schedule declaration.');
        }
    }
}
