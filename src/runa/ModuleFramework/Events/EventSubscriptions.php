<?php

namespace Runa\ModuleFramework\Events;

use Runa\Contracts\Modules\ListenerDefinition;
use Runa\Contracts\Modules\PublicEvent;
use Runa\ModuleFramework\ModuleRegistry;
use RuntimeException;

/** Reacquire owned declarations and optional integration state at actual invocation, including retries. */
final readonly class EventSubscriptions
{
    public function __construct(private ModuleRegistry $registry, private EventDefinitions $definitions, private ModuleIntegrations $integrations) {}

    public function resolve(string $module, string $key, PublicEvent $event, bool $checkAvailability = true): ListenerDefinition
    {
        $snapshot = $this->registry->snapshot();
        $record = $snapshot->modules[$module] ?? throw new RuntimeException('Listener module missing.');
        $publisher = $snapshot->modules[$event->module] ?? throw new RuntimeException('Event publisher missing.');
        [$events] = $this->definitions->for($publisher);
        [, $listeners] = $this->definitions->for($record);
        $definition = $listeners[$key] ?? throw new RuntimeException('Listener source missing.');
        if (! isset($events[$event->event.':'.$event->version]) || $definition->publisher !== $event->module || $definition->event !== $event->event || $definition->version !== $event->version) {
            throw new RuntimeException('Event contract changed.');
        }
        if ($definition->integration !== null) {
            $target = $checkAvailability ? $this->integrations->requireAvailable($module, $definition->integration) : $this->integrations->requireConfigured($module, $definition->integration);
            if ($target !== $event->module) {
                throw new RuntimeException('Integration does not target publisher.');
            }
        }

        return $definition;
    }

    public function assertOrdinaryJob(string $module, string $operation): void
    {
        $record = $this->registry->snapshot()->modules[$module] ?? throw new RuntimeException('Module missing.');
        [, $listeners] = $this->definitions->for($record);
        foreach ($listeners as $listener) {
            if ($listener->mode === 'async' && $listener->target === $operation) {
                throw new RuntimeException('Listener jobs require event dispatch.');
            }
        }
    }

    public static function key(string $module, string $listener, PublicEvent $event): string
    {
        return hash('sha256', $event->module.'|'.$event->id.'|'.$module.'|'.$listener);
    }
}
