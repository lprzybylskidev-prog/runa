<?php

namespace Runa\Contracts\Modules;

use InvalidArgumentException;

/** These are execution protocol outcomes, not a catalogue of business workflows. */
final readonly class WorkDecision
{
    public function __construct(public string $action, public string $code, public ?int $retrySeconds = null)
    {
        if (! in_array($action, ['run', 'retry', 'cancel', 'refuse'], true) || preg_match('/\A[a-z][a-z0-9_]{0,63}\z/D', $code) !== 1 || ($retrySeconds !== null && ($action !== 'retry' || $retrySeconds < 1 || $retrySeconds > 86400))) {
            throw new InvalidArgumentException('Invalid module work decision.');
        }
    }
}
