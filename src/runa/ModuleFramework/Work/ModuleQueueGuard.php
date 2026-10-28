<?php

namespace Runa\ModuleFramework\Work;

use Illuminate\Queue\Events\JobProcessing;
use Runa\Support\Modules\Work\ModuleJob;
use RuntimeException;

/** Inspect queue metadata before Laravel deserializes the command. Module objects use the envelope. */
final class ModuleQueueGuard
{
    /** @param array<string, mixed> $payload
     * @return array<string, string>
     */
    public static function payload(string $connection, ?string $queue, array $payload): array
    {
        if (self::moduleClass(self::command($payload)) || self::moduleClass($payload['displayName'] ?? null) || self::moduleClass($payload['job'] ?? null)) {
            throw new RuntimeException('Module jobs must use ModuleWork dispatch.');
        }
        $command = self::command($payload);

        return $command instanceof ModuleJob ? ['runa_work' => $command->workId] : [];
    }

    public static function before(JobProcessing $event): void
    {
        $payload = $event->job->payload();
        if (self::moduleClass(self::command($payload)) || self::moduleClass($payload['displayName'] ?? null) || self::moduleClass($payload['job'] ?? null)
            || (self::command($payload) === ModuleJob::class && (! is_string($payload['runa_work'] ?? null) || preg_match('/\A[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}\z/D', $payload['runa_work']) !== 1))) {
            // Delete before job->fire(): even __wakeup/__unserialize must not run for unmanaged jobs.
            $event->job->delete();
        }
    }

    /** @param array<array-key, mixed> $payload */
    private static function command(array $payload): mixed
    {
        $data = $payload['data'] ?? null;

        return is_array($data) ? ($data['commandName'] ?? null) : null;
    }

    private static function moduleClass(mixed $value): bool
    {
        $value = is_object($value) ? $value::class : $value;

        return is_string($value) && preg_match('/\A\\\\?(?:App|Runa)\\\\Modules\\\\/D', $value) === 1;
    }
}
