<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use Monolog\Handler\TestHandler;
use Runa\Contracts\Modules\OperationContext;
use Runa\Support\Logging\LogContext;
use Runa\Support\Logging\QueueLogContext;
use Runa\Support\Modules\Api\OperationScope;
use Tests\Fixtures\LoggingJob;

beforeEach(function (): void {
    $this->sink = new TestHandler;
    Log::channel()->getLogger()->setHandlers([$this->sink]);
});

it('redacts arbitrary context and objects before interpolation and bounds messages and records', function (): void {
    $object = new class implements JsonSerializable
    {
        public function jsonSerialize(): mixed
        {
            throw new RuntimeException('Objects must never be serialized.');
        }
    };
    Log::error("Failure {password}\r\ntoken=private_token Bearer private_bearer https://name:password@example.test/path", [
        'password' => 'private_password', 'payload' => ['nested' => 'private_payload'],
        'exception' => new RuntimeException('private_exception'), 'object' => $object,
        'origin' => '../../forged', 'code' => 'handler_failed', 'status' => 'failed',
    ]);
    $record = $this->sink->getRecords()[0];
    $json = json_encode($record->toArray(), JSON_THROW_ON_ERROR);
    foreach (['private_token', 'private_bearer', 'name:password', 'private_password', 'private_payload', 'private_exception', '../../forged'] as $secret) {
        expect($json)->not->toContain($secret);
    }
    expect($record->message)->not->toContain("\n")->and($record->context['code'])->toBe('handler_failed')
        ->and($record->extra)->toBe(['schema_version' => 1, 'origin' => 'platform']);
    Log::error('SQL query containing private_binding', ['exception' => new RuntimeException('private_exception')]);
    expect($this->sink->getRecords()[1]->message)->toBe('Exception reported.');
    Log::info(str_repeat('token=a ', 1000), array_fill_keys(range(0, 1000), 'private'));
    expect(strlen($this->sink->getRecords()[2]->message))->toBe(2048)
        ->and($this->sink->getRecords()[2]->context)->toBe([]);
});

it('restores nested origins and correlation after exceptions without leaking into later work', function (): void {
    $logs = app(LogContext::class);
    $scope = app(OperationScope::class);
    $logs->run(['origin' => 'application:customer', 'request_id' => 'request-1', 'correlation_id' => 'request-1'], function () use ($scope): void {
        Log::info('Application boundary.');
        try {
            $scope->run(new OperationContext('app.orders', 'process', 'operation-1', 'request-1', 1), function () use ($scope): void {
                Log::info('Owned operation.');
                $scope->run(new OperationContext('runa.files', 'read', 'operation-2', 'request-1', 1, 'app.orders'), fn () => Log::info('Nested operation.'));
                throw new RuntimeException('private');
            });
        } catch (RuntimeException) {
            Log::info('Application restored.');
        }
    });
    Log::info('Later worker operation.');
    $records = $this->sink->getRecords();
    expect(array_column(array_column($records, 'extra'), 'origin'))->toBe(['application:customer', 'app.orders', 'runa.files', 'application:customer', 'platform'])
        ->and($records[2]->extra['origin_module'])->toBe('app.orders')
        ->and($records[4]->extra)->toBe(['schema_version' => 1, 'origin' => 'platform'])
        ->and($scope->current())->toBeNull()->and($scope->diagnostics())->toBe([])->and($logs->current())->toBe([]);
});

it('attributes actual HTTP requests and replaces unsafe client correlation IDs', function (): void {
    Route::get('/api/logging-probe', function () {
        Log::info('Request handled.');

        return response()->json(['ok' => true]);
    })->name('tests.logging.probe');
    $this->getJson('/api/logging-probe', ['X-Request-ID' => 'accepted_123'])->assertOk()->assertHeader('X-Request-ID', 'accepted_123');
    $this->getJson('/api/logging-probe', ['X-Request-ID' => '../invalid'])->assertOk();
    $records = $this->sink->getRecords();
    expect($records[0]->extra['request_id'])->toBe('accepted_123')
        ->and($records[0]->extra['origin'])->toBe('application:'.config('branding.application_id'))
        ->and($records[1]->extra['request_id'])->not->toBe('../invalid')
        ->and($records[1]->extra['correlation_id'])->toBe($records[1]->extra['request_id'])
        ->and(app(LogContext::class)->current())->toBe([]);
});

it('attributes actual Artisan commands without recording arguments and cleans failed commands', function (): void {
    $kernel = app(Kernel::class);
    $kernel->rerouteSymfonyCommandEvents();
    $kernel->setArtisan(null);
    Artisan::command('logging:probe {secret}', function (): void {
        Log::info('Command handled.');
        throw new RuntimeException('private command exception');
    });
    try {
        Artisan::call('logging:probe', ['secret' => 'private_cli']);
    } catch (RuntimeException) {
        // Native in-process Artisan callers may receive the command exception.
    }
    $record = $this->sink->getRecords()[0];
    expect($record->extra['command'])->toBe('logging:probe')
        ->and($record->extra['correlation_id'])->toBe($record->extra['operation_id'])
        ->and(json_encode($record->toArray()))->not->toContain('private_cli')
        ->and(app(LogContext::class)->current())->toBe([]);
});

it('writes identical single-line UTC JSON to stderr and fixed-name private rotating files', function (): void {
    $directory = sys_get_temp_dir().'/runa-log-'.bin2hex(random_bytes(8));
    mkdir($directory, 0700);
    try {
        for ($day = 1; $day <= 20; $day++) {
            file_put_contents($directory.'/runa-'.gmdate('Y-m-d', time() - $day * 86400).'.log', "old\n");
        }
        config(['logging.channels.test_stderr' => array_replace(config('logging.channels.stderr'), ['with' => ['stream' => 'php://memory']]),
            'logging.channels.test_files' => array_replace(config('logging.channels.files'), ['path' => $directory.'/runa.log'])]);
        $stderr = Log::channel('test_stderr');
        $files = Log::channel('test_files');
        $stack = Log::stack([$stderr, $files]);
        $stack->warning('Safe diagnostic.', ['password' => 'private', 'code' => 'test_complete', 'module_id' => 'app.probe']);
        $stream = $stderr->getLogger()->getHandlers()[0]->getStream();
        rewind($stream);
        $stderrLine = stream_get_contents($stream);
        $files->getLogger()->close();
        $path = $directory.'/runa-'.gmdate('Y-m-d').'.log';
        $fileLine = file_get_contents($path);
        expect($stderrLine)->toBe($fileLine)->and(substr_count($fileLine, "\n"))->toBe(1)
            ->and(count(glob($directory.'/*.log')))->toBe(14)
            ->and(fileperms($path) & 0777)->toBe(0640);
        $record = json_decode($fileLine, true, flags: JSON_THROW_ON_ERROR);
        expect($record['datetime'])->toMatch('/Z$/')->and($record['level_name'])->toBe('WARNING')
            ->and($record['context']['password'])->toBe('[redacted]')
            ->and($record['extra']['schema_version'])->toBe(1);
    } finally {
        foreach (glob($directory.'/*') as $path) {
            unlink($path);
        }
        rmdir($directory);
    }
});

it('rejects path and line injection in trusted origin scopes before creating any channel', function (): void {
    expect(fn () => app(LogContext::class)->begin(['origin' => '../../private']))->toThrow(InvalidArgumentException::class)
        ->and(fn () => app(LogContext::class)->begin(['correlation_id' => "one\ntwo"]))->toThrow(InvalidArgumentException::class)
        ->and(app(LogContext::class)->current())->toBe([]);
    $command = 'app:'.str_repeat('m', 58).':'.str_repeat('k', 128);
    app(LogContext::class)->run(['command' => $command], function () use ($command): void {
        Log::info('Long command observed.');
        expect($this->sink->getRecords()[0]->extra['command'])->toBe($command);
    });
});

it('carries correlation to native application jobs and unwinds success and failure scopes', function (): void {
    $context = app(LogContext::class);
    $context->run(['origin' => 'application:customer', 'correlation_id' => 'job-correlation'], function (): void {
        Queue::connection('sync')->push(new LoggingJob);
        expect(fn () => Queue::connection('sync')->push(new LoggingJob(true)))->toThrow(RuntimeException::class);
    });
    foreach ($this->sink->getRecords() as $record) {
        expect($record->extra['origin'])->toBe('application:'.config('branding.application_id'))
            ->and($record->extra['correlation_id'])->toBe('job-correlation')
            ->and($record->context['payload'])->toBe('[redacted]');
    }
    expect($context->current())->toBe([]);
});

it('replaces malformed native queue correlation metadata before entering a logging scope', function (): void {
    $job = $this->createStub(Job::class);
    $job->method('payload')->willReturn(['displayName' => 'App\\Jobs\\LoggingProbe', 'runa_log' => 'private_metadata']);
    $queue = app(QueueLogContext::class);
    $queue->before(new JobProcessing('sync', $job));
    try {
        Log::info('Malformed metadata handled.');
        $record = $this->sink->getRecords()[0];
        expect($record->extra['correlation_id'])->toMatch('/^[a-zA-Z0-9_-]{1,64}$/')
            ->and(json_encode($record->toArray()))->not->toContain('private_metadata');
    } finally {
        $queue->after(new JobProcessed('sync', $job));
    }
    expect(app(LogContext::class)->current())->toBe([]);
});
