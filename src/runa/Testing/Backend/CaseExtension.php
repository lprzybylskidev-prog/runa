<?php

namespace Runa\Testing\Backend;

use PHPUnit\Event\EventFacadeIsSealedException;
use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;

final readonly class CaseExtension implements Extension
{
    public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
    {
        $evidence = CaseEvidence::current();
        if ($evidence !== null) {
            $evidence->append('process_started');
            try {
                $facade->registerTracer(new CaseEvents($evidence));
            } catch (EventFacadeIsSealedException) {
                // Pest seals parent dispatch before bootstrapping extensions; workers remain traceable.
                $evidence->append('trace_unavailable');
            }
        }
    }
}
