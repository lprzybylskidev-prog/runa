<?php

namespace Runa\Support\Modules;

use RuntimeException;

/** Interrupt trusted blocking checks on the supported Linux/pcntl runtime; no unbounded fallback. */
final class HealthDeadline
{
    public function run(int $seconds, callable $check): bool
    {
        if (! function_exists('pcntl_alarm') || ! function_exists('pcntl_async_signals')) {
            throw new RuntimeException('Health deadline support is unavailable.');
        }
        $handler = pcntl_signal_get_handler(SIGALRM);
        $async = pcntl_async_signals(true);
        $prior = pcntl_alarm(0);
        if ($prior !== 0) {
            pcntl_alarm($prior);
            pcntl_async_signals($async);
            throw new RuntimeException('Health cannot replace an active process deadline.');
        }
        $started = hrtime(true);
        try {
            pcntl_signal(SIGALRM, static function (): never {
                throw new RuntimeException('Module health deadline exceeded.');
            });
            pcntl_alarm($seconds);

            return $check() === true && (hrtime(true) - $started) / 1e9 < $seconds;
        } finally {
            pcntl_alarm(0);
            pcntl_signal(SIGALRM, $handler);
            pcntl_async_signals($async);
        }
    }
}
