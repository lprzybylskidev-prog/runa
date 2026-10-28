<?php

namespace Runa\Logging;

use Illuminate\Contracts\Queue\Job;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Str;
use Runa\ModuleFramework\Api\OperationScope;
use Runa\Support\Logging\LogContext;
use WeakMap;

/** Carries diagnostic identity only, independent of serialized job arguments. */
final class QueueLogContext
{
    /** @var WeakMap<Job, string> */
    private WeakMap $tokens;

    /** @var array<int, array<string, string>> */
    private array $identities = [];

    /** Safe execution identities only, no serialized job data.
     * @return array<string, string>
     */
    public function current(): array
    {
        return $this->identities === [] ? [] : $this->identities[array_key_last($this->identities)];
    }

    public function __construct(private readonly LogContext $context, private readonly OperationScope $operations)
    {
        $this->tokens = new WeakMap;
    }

    /** @return array{runa_log: array{correlation_id: string}} */
    public function payload(): array
    {
        $operation = $this->operations->current();
        $correlation = $operation === null ? ($this->context->current()['correlation_id'] ?? (string) Str::uuid7()) : $operation->correlationId;

        return ['runa_log' => ['correlation_id' => $correlation]];
    }

    public function before(JobProcessing $event): void
    {
        $payload = $event->job->payload();
        $metadata = $payload['runa_log'] ?? null;
        $candidate = is_array($metadata) ? ($metadata['correlation_id'] ?? null) : null;
        $correlation = is_string($candidate) && preg_match('/\A[a-zA-Z0-9_-]{1,64}\z/D', $candidate) === 1 ? $candidate : (string) Str::uuid7();
        $name = $payload['displayName'] ?? '';
        $identity = ['kind' => 'queue'];
        foreach (['job_class' => $name, 'job_id' => $event->job->getJobId(), 'queue' => $event->job->getQueue()] as $key => $value) {
            if (is_string($value) && strlen($value) <= 160 && preg_match('/\A[a-zA-Z0-9_\\\\.:-]+\z/D', $value) === 1) {
                $identity[$key] = $value;
            }
        }
        $application = config('branding.application_id');
        $this->tokens[$event->job] = $this->context->begin([
            'origin' => is_string($name) && str_starts_with($name, 'App\\') && is_string($application) ? 'application:'.$application : 'platform',
            'operation_id' => (string) Str::uuid7(), 'correlation_id' => $correlation,
        ]);
        $this->identities[spl_object_id($event->job)] = $identity;
    }

    public function after(JobProcessed|JobExceptionOccurred|JobFailed $event): void
    {
        if (isset($this->tokens[$event->job])) {
            $this->context->end($this->tokens[$event->job]);
            unset($this->tokens[$event->job], $this->identities[spl_object_id($event->job)]);
        }
    }
}
