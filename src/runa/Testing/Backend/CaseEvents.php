<?php

namespace Runa\Testing\Backend;

use PHPUnit\Event\Code\Test;
use PHPUnit\Event\Code\TestMethod;
use PHPUnit\Event\Event;
use PHPUnit\Event\TestSuite\Filtered;
use PHPUnit\Event\Tracer\Tracer;

/** Records IDs/outcomes only; assertion values and exception messages stay native. */
final readonly class CaseEvents implements Tracer
{
    public function __construct(private CaseEvidence $evidence) {}

    public function trace(Event $event): void
    {
        if ($event instanceof Filtered) {
            $count = 0;
            foreach ($event->testSuite()->tests() as $test) {
                $this->evidence->append('discovered', ['case' => $test->id(), 'filter_name' => $test instanceof TestMethod ? $test->sortId() : null, 'file' => $this->evidence->file($test->file())]);
                $count++;
            }
            $this->evidence->append('discovery_finished', ['count' => $count]);

            return;
        }
        $class = $event::class;
        if (! str_starts_with($class, 'PHPUnit\\Event\\Test\\')) {
            return;
        }
        $name = substr($class, strrpos($class, '\\') + 1);
        if (! in_array($name, ['PreparationStarted', 'Prepared', 'Finished', 'Passed', 'Failed', 'Errored', 'Skipped', 'MarkedIncomplete', 'ConsideredRisky', 'WarningTriggered', 'PhpWarningTriggered', 'PhpunitWarningTriggered', 'DeprecationTriggered', 'PhpDeprecationTriggered', 'PhpunitDeprecationTriggered', 'ErrorTriggered', 'PhpunitErrorTriggered', 'PreparationFailed', 'PreparationErrored', 'BeforeFirstTestMethodErrored', 'AfterLastTestMethodErrored'], true)) {
            return;
        }
        $test = method_exists($event, 'test') ? $event->test() : null;
        $this->evidence->append($name, $test instanceof Test
            ? ['case' => $test->id(), 'file' => $this->evidence->file($test->file())] : []);
    }
}
