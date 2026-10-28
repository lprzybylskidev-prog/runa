<?php

use Illuminate\Queue\Events\WorkerStarting;
use Illuminate\Queue\Events\WorkerStopping;
use Illuminate\Queue\WorkerOptions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Runa\Runtime\RuntimeHeartbeat;
use Symfony\Component\Process\Process;

it('keeps a maintenance-paused real daemon healthy without consuming queued work or refreshing from probes', function (): void {
    $directory = storage_path('framework/maintenance');
    if (! is_dir($directory)) {
        mkdir($directory, 0775, true);
    }
    $marker = $directory.'/enabled';
    $queue = 'maintenance-heartbeat-'.bin2hex(random_bytes(6));
    $id = DB::table('shared.jobs')->insertGetId([
        'queue' => $queue, 'payload' => '{}', 'attempts' => 0, 'reserved_at' => null, 'available_at' => 1, 'created_at' => 1,
    ]);
    $heartbeat = storage_path('framework/health/queue.json');
    try {
        file_put_contents($marker, 'maintenance');
        $worker = new Process([PHP_BINARY, 'artisan', 'queue:work', 'database', '--queue='.$queue, '--sleep=1', '--max-time=2', '--no-interaction'], base_path(), [
            'APP_ENV' => 'testing', 'DB_DATABASE' => DB::connection()->getDatabaseName(), 'LARAVEL_STORAGE_PATH' => storage_path(),
        ]);
        $worker->setTimeout(10);
        $worker->mustRun();
        expect(app(RuntimeHeartbeat::class)->isFresh('queue', 5))->toBeTrue()
            ->and(DB::table('shared.jobs')->where('id', $id)->value('attempts'))->toBe(0);
        $before = file_get_contents($heartbeat);
        expect(Artisan::call('runa:runtime:health', ['service' => 'queue']))->toBe(0);
        expect(file_get_contents($heartbeat))->toBe($before);
        Event::dispatch(new WorkerStarting('database', $queue, new WorkerOptions));
        Event::dispatch(new WorkerStopping(0));
        file_put_contents($heartbeat, 'stopped-worker');
        app()->isDownForMaintenance();
        expect(file_get_contents($heartbeat))->toBe('stopped-worker');
    } finally {
        unlink($marker);
        if (is_file($heartbeat)) {
            unlink($heartbeat);
        }
        DB::table('shared.jobs')->where('id', $id)->delete();
    }
});
