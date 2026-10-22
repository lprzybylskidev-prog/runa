<?php

namespace Tests\Fixtures;

use Psr\Log\AbstractLogger;
use Stringable;

final class EventLog extends AbstractLogger
{
    /** @var list<array{string, array<string, mixed>}> */
    public array $entries = [];

    public function log($level, string|Stringable $message, array $context = []): void
    {
        $this->entries[] = [(string) $message, $context];
    }
}
