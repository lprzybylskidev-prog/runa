<?php

namespace Runa\Support\Logging;

use Closure;
use Illuminate\Support\Str;
use InvalidArgumentException;

/** Trusted synchronous diagnostic scopes; never store request/job payloads here. */
final class LogContext
{
    /** @var array<string, array<string, string>> */
    private array $scopes = [];

    /** @return array<string, string> */
    public function current(): array
    {
        return $this->scopes === [] ? [] : $this->scopes[array_key_last($this->scopes)];
    }

    /** @param array<string, string|null> $fields */
    public function begin(array $fields): string
    {
        foreach ($fields as $key => $value) {
            if (! in_array($key, ['origin', 'request_id', 'correlation_id', 'operation_id', 'event_id', 'origin_module', 'command', 'work_id'], true)
                || ($value !== null && (strlen($value) > ($key === 'command' ? 256 : 128) || preg_match('/\A[a-zA-Z0-9_.:-]+\z/D', $value) !== 1))) {
                throw new InvalidArgumentException('Invalid diagnostic context.');
            }
            if ($key === 'origin' && $value !== null && $value !== 'platform'
                && preg_match('/\A(?:application:[a-z][a-z0-9._-]{0,63}|(?:runa|app)\.[a-z][a-z0-9_]{0,63})\z/D', $value) !== 1) {
                throw new InvalidArgumentException('Invalid diagnostic origin.');
            }
        }
        $token = (string) Str::uuid7();
        $this->scopes[$token] = array_replace($this->current(), array_filter($fields, fn (?string $value): bool => $value !== null));

        return $token;
    }

    public function end(string $token): void
    {
        unset($this->scopes[$token]);
    }

    /** @template T
     * @param  array<string, string|null>  $fields
     * @param  Closure(): T  $callback
     * @return T
     */
    public function run(array $fields, Closure $callback): mixed
    {
        $token = $this->begin($fields);
        try {
            return $callback();
        } finally {
            $this->end($token);
        }
    }
}
