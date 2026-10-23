<?php

namespace Runa\Support\Logging;

use Illuminate\Log\Logger;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\FormattableHandlerInterface;
use Monolog\Logger as Monolog;

final readonly class ConfigureStructuredLogging
{
    public function __construct(private SafeLogProcessor $processor) {}

    public function __invoke(Logger $logger): void
    {
        $monolog = $logger->getLogger();
        if (! $monolog instanceof Monolog) {
            return;
        }
        $formatter = new JsonFormatter;
        $formatter->setDateFormat('Y-m-d\TH:i:s.u\Z');
        foreach ($monolog->getHandlers() as $handler) {
            if ($handler instanceof FormattableHandlerInterface) {
                $handler->setFormatter($formatter);
            }
        }
        $monolog->pushProcessor($this->processor);
    }
}
