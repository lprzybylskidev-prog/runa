<?php

namespace Runa\ModuleFramework\Events;

use Runa\Contracts\Modules\EventDefinition;
use Runa\Contracts\Modules\EventListener;
use Runa\Contracts\Modules\EventProvider;
use Runa\Contracts\Modules\ListenerDefinition;
use Runa\ModuleFramework\Api\ModuleDeclarations;
use Runa\ModuleFramework\Work\WorkDefinitions;
use Runa\Support\Modules\ModuleRecord;
use RuntimeException;

final readonly class EventDefinitions
{
    public function __construct(private ModuleDeclarations $declarations, private WorkDefinitions $jobs) {}

    /** @return array{array<string, EventDefinition>, array<string, ListenerDefinition>} */
    public function for(ModuleRecord $record): array
    {
        $events = $listeners = [];
        foreach ($this->declarations->classes($record, 'events') as $class) {
            $provider = $this->declarations->make($record, $class, EventProvider::class);
            if (! $provider instanceof EventProvider) {
                throw new RuntimeException('Invalid event provider.');
            }
            foreach ($provider->events() as $definition) {
                $key = $definition->key.':'.$definition->version;
                if (isset($events[$key])) {
                    throw new RuntimeException('Duplicate event declaration.');
                }
                $events[$key] = $definition;
                if (count($events) > 256) {
                    throw new RuntimeException('Too many public events.');
                }
            }
            foreach ($provider->listeners() as $definition) {
                if (isset($listeners[$definition->key]) || (str_starts_with($record->id, 'runa.') && str_starts_with($definition->publisher, 'app.'))) {
                    throw new RuntimeException('Invalid listener identity or ownership direction.');
                }
                if ($definition->mode === 'async') {
                    $this->jobs->resolve($record->id, $definition->target);
                } else {
                    $type = $this->declarations->inspect($record, $definition->target);
                    if (! $type->isInstantiable() || ! $type->implementsInterface(EventListener::class)) {
                        throw new RuntimeException('Invalid event listener.');
                    }
                }
                $listeners[$definition->key] = $definition;
                if (count($listeners) > 256) {
                    throw new RuntimeException('Too many event listeners.');
                }
            }
        }

        return [$events, $listeners];
    }
}
