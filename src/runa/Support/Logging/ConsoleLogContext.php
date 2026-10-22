<?php

namespace Runa\Support\Logging;

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Str;

final class ConsoleLogContext
{
    /** @var array<int, list<string>> */
    private array $tokens = [];

    public function __construct(private readonly LogContext $context) {}

    public function starting(CommandStarting $event): void
    {
        $id = (string) Str::uuid7();
        $this->tokens[spl_object_id($event->input)][] = $this->context->begin([
            'origin' => 'platform', 'command' => $event->command, 'operation_id' => $id,
            'correlation_id' => $this->context->current()['correlation_id'] ?? $id,
        ]);
    }

    public function finished(CommandFinished $event): void
    {
        $key = spl_object_id($event->input);
        if (isset($this->tokens[$key])) {
            $token = array_pop($this->tokens[$key]);
            if ($token !== null) {
                $this->context->end($token);
            }
            if ($this->tokens[$key] === []) {
                unset($this->tokens[$key]);
            }
        }
    }
}
