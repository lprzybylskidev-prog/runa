<?php

namespace Runa\Logging;

use DateTimeZone;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Routing\Route;
use Monolog\LogRecord;
use Runa\ModuleFramework\Api\OperationScope;
use Runa\Support\Logging\LogContext;

/** Sanitizes before formatting/placeholder expansion. Unknown context is redacted by default. */
final readonly class SafeLogProcessor
{
    private const SAFE_FIELDS = ['code', 'status', 'reason', 'module_id', 'origin_module', 'subscriber_module', 'listener_key', 'event_id', 'correlation_id', 'request_id', 'operation_id', 'work_id', 'command', 'operation', 'generation', 'attempt', 'count', 'duration_ms'];

    public function __construct(private Application $app) {}

    public function __invoke(LogRecord $record): LogRecord
    {
        $fields = $this->app->make(LogContext::class)->current();
        if (isset($fields['request_id']) && $this->app->bound('request')) {
            $route = $this->app->make('request')->route();
            if ($route instanceof Route && str_starts_with($route->getActionName(), 'Runa\\')) {
                $fields['origin'] = 'platform';
            }
        }
        $operation = $this->app->make(OperationScope::class)->current();
        if ($operation !== null) {
            $fields = array_replace($fields, ['origin' => $operation->moduleId, 'operation_id' => $operation->operationId,
                'correlation_id' => $operation->correlationId, 'operation' => $operation->operation]);
            if ($operation->originModule !== null) {
                $fields['origin_module'] = $operation->originModule;
            }
        }
        $fields = array_replace($fields, $this->app->make(OperationScope::class)->diagnostics());
        $fields = ['schema_version' => 1, 'origin' => 'platform', ...$fields];

        return $record->with(
            datetime: $record->datetime->setTimezone(new DateTimeZone('UTC')),
            message: ($record->context['exception'] ?? null) instanceof \Throwable ? 'Exception reported.' : $this->message($record->message),
            context: $this->context($record->context),
            extra: $fields,
        );
    }

    /** @param array<mixed> $context
     * @return array<string, scalar|null>
     */
    private function context(array $context): array
    {
        $safe = [];
        foreach (array_slice($context, 0, 32, true) as $key => $value) {
            if (! is_string($key) || preg_match('/\A[a-z][a-z0-9_]{0,63}\z/D', $key) !== 1) {
                continue;
            }
            $safe[$key] = in_array($key, self::SAFE_FIELDS, true)
                && (is_int($value) || is_bool($value) || $value === null || (is_float($value) && is_finite($value))
                    || (is_string($value) && preg_match('/\A[a-zA-Z0-9_.:-]{1,128}\z/D', $value) === 1))
                ? $value : '[redacted]';
        }

        return $safe;
    }

    private function message(string $message): string
    {
        // Structured identifiers belong in context. Never interpolate arbitrary input into messages.
        $message = substr($message, 0, 2048);
        $message = preg_replace('/[\x00-\x1f\x7f]/', ' ', $message) ?? '[invalid message]';
        $message = preg_replace('/\b(?:Bearer|Basic)\s+\S+/i', '[redacted]', $message) ?? '[invalid message]';
        $message = preg_replace('~[a-z][a-z0-9+.-]*://[^\s]+~i', '[redacted-url]', $message) ?? '[invalid message]';

        $message = preg_replace('/\b(password|passwd|secret|token|api[_-]?key|authorization|cookie)\b\s*[=:]\s*(?:"[^"]*"|\'[^\']*\'|\S+)/i', '$1=[redacted]', $message) ?? '[invalid message]';

        return substr($message, 0, 2048);
    }
}
