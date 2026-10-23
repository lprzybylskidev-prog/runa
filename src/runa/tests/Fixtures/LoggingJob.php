<?php

namespace Tests\Fixtures;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use RuntimeException;

final readonly class LoggingJob implements ShouldQueue
{
    public function __construct(private bool $fail = false) {}

    public function displayName(): string
    {
        return 'App\\Jobs\\LoggingProbe';
    }

    public function handle(): void
    {
        Log::info('Application job handled.', ['payload' => 'private_job']);
        if ($this->fail) {
            throw new RuntimeException('private exception');
        }
    }
}
