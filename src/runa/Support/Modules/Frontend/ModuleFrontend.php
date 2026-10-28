<?php

namespace Runa\Support\Modules\Frontend;

use Illuminate\Database\Connection;
use Runa\Contracts\Modules\ApiOperation;
use Runa\Contracts\Modules\FrontendPage;
use Runa\Contracts\Modules\FrontendProvider;
use Runa\Contracts\Modules\OperationAuthorizer;
use Runa\Contracts\Modules\OperationContext;
use Runa\Contracts\Modules\SlotContribution;
use Runa\Contracts\Modules\SlotHost;
use Runa\Contracts\Modules\SlotProvider;
use Runa\ModuleFramework\Api\ModuleDeclarations;
use Runa\ModuleFramework\ModuleAvailability;
use Runa\ModuleFramework\ModuleRegistry;
use Runa\Support\Modules\ModuleRecord;
use RuntimeException;
use Throwable;

/** Optional, sanitized presentation adapter. Business requests still use OperationGuard.
 * @phpstan-type Projection array{schemaVersion: int, generation: int, pages: list<array{routeKey: string, moduleKey: string, areaKey: ?string, href: string, serverRouteName: string, names: array{pl: string, en: string}, icon: string, component: string, manifestHash: string, availability: string, reason: ?string}>, slots: object, diagnostics: object}
 */
final class ModuleFrontend
{
    /** @var array<string, SlotHost> */
    private array $applicationHosts = [];

    public function __construct(private readonly Connection $connection, private readonly ModuleRegistry $registry, private readonly ModuleDeclarations $declarations, private readonly ModuleAvailability $availability, private readonly OperationAuthorizer $authorizer) {}

    /** Trusted application bootstrap, independent of module source. */
    public function applicationHost(SlotHost $host): void
    {
        if (! str_starts_with($host->id, 'application.') || isset($this->applicationHosts[$host->id])) {
            throw new RuntimeException('Invalid application slot host.');
        }
        $this->applicationHosts[$host->id] = $host;
    }

    /** @return list<FrontendPage> */
    public function pages(ModuleRecord $record): array
    {
        $pages = [];
        foreach ($this->declarations->classes($record, 'frontend') as $class) {
            $provider = $this->declarations->make($record, $class, FrontendProvider::class);
            if (! $provider instanceof FrontendProvider) {
                throw new RuntimeException('Invalid frontend provider.');
            }
            foreach ($provider->pages() as $page) {
                if (isset($pages[$page->key]) || ! str_starts_with($page->component, $record->id.'.')) {
                    throw new RuntimeException('Invalid frontend page ownership.');
                }
                $pages[$page->key] = $page;
                if (count($pages) > 128) {
                    throw new RuntimeException('Too many frontend pages.');
                }
            }
        }
        ksort($pages);

        return array_values($pages);
    }

    /** @return Projection */
    public function projection(): array
    {
        $snapshot = $this->registry->snapshot();
        $statuses = $this->availability->statuses();
        $pages = $contributions = $diagnostics = [];
        $hosts = $this->applicationHosts;
        $hostModules = array_fill_keys(array_keys($hosts), null);
        foreach ($snapshot->modules as $record) {
            if (! $record->registered || ! $record->sourcePresent) {
                continue;
            }
            try {
                $localPages = $this->pages($record);
                $localHosts = $localContributions = [];
                foreach ($this->declarations->classes($record, 'slots') as $class) {
                    $provider = $this->declarations->make($record, $class, SlotProvider::class);
                    if (! $provider instanceof SlotProvider) {
                        throw new RuntimeException;
                    }
                    foreach ($provider->hosts() as $host) {
                        if (! str_starts_with($host->id, $record->id.'.') || isset($localHosts[$host->id])) {
                            throw new RuntimeException;
                        }
                        $localHosts[$host->id] = $host;
                    }
                    foreach ($provider->contributions() as $item) {
                        if (! str_starts_with($item->id, $record->id.'.') || ! str_starts_with($item->component, $record->id.'.') || isset($localContributions[$item->id])) {
                            throw new RuntimeException;
                        }
                        // Runa source cannot acquire an application-owned host dependency.
                        if (str_starts_with($record->id, 'runa.') && ! str_starts_with($item->target->id, 'runa.')) {
                            throw new RuntimeException;
                        }
                        $localContributions[$item->id] = $item;
                    }
                    if (max(count($localHosts), count($localContributions)) > 128) {
                        throw new RuntimeException;
                    }
                }
            } catch (Throwable) {
                $diagnostics[$record->id] = 'frontend_declaration_unavailable';

                continue;
            }
            $status = $statuses[$record->id];
            foreach ($localPages as $page) {
                if (! $this->allowed($record->id, 'page.'.$page->key, $page->permission, $snapshot->generation)) {
                    continue;
                }
                $pages[] = ['routeKey' => $record->id.'.'.$page->key, 'moduleKey' => $record->id, 'areaKey' => $page->area,
                    'href' => '/modules/'.str_replace('.', '/', $record->id).'/'.$page->key, 'serverRouteName' => $record->id.'.pages.'.$page->key,
                    'names' => $page->names, 'icon' => $page->icon, 'component' => $page->component, 'manifestHash' => $record->manifestHash,
                    'availability' => ! $status->enabled ? 'hidden' : ($status->available ? 'available' : 'disabled'),
                    'reason' => $status->available ? null : 'module_unavailable'];
            }
            foreach ($localHosts as $key => $host) {
                $hosts[$key] = $host;
                $hostModules[$key] = $record->id;
            }
            foreach ($localContributions as $item) {
                if ($status->available && $this->allowed($record->id, 'slot.'.$item->id, $item->permission, $snapshot->generation)) {
                    $contributions[$item->id] = $item;
                }
            }
        }
        /** @var array<string, list<string>> $orders */
        $orders = $this->orders($this->connection->table('shared.module_registry_state')->where('id', 1)->value('slot_order'));
        $slots = [];
        foreach ($hosts as $id => $host) {
            $owner = $hostModules[$id];
            if ($owner !== null && ! ($statuses[$owner]->available ?? false)) {
                continue;
            }
            $items = [];
            foreach ($contributions as $item) {
                if ($item->target->id !== $id) {
                    continue;
                }
                if ($item->target->contract !== $host->contract || $item->target->version !== $host->version || $item->target->props != $host->props) {
                    $diagnostics[$item->id] = 'slot_contract_incompatible';

                    continue;
                }
                $items[] = $item;
            }
            $order = array_flip($orders[$id] ?? []);
            usort($items, fn (SlotContribution $a, SlotContribution $b) => (($order[$a->id] ?? PHP_INT_MAX) <=> ($order[$b->id] ?? PHP_INT_MAX)) ?: ($a->priority <=> $b->priority) ?: strcmp($a->id, $b->id));
            $slots[$id] = ['contract' => $host, 'contributions' => array_map(function (SlotContribution $item) use ($snapshot): array {
                $parts = explode('.', $item->id);
                $module = $parts[0].'.'.$parts[1];

                return ['id' => $item->id, 'target' => $item->target, 'component' => $item->component, 'manifestHash' => $snapshot->modules[$module]->manifestHash];
            }, $items)];
        }
        if ($this->registry->snapshot()->generation !== $snapshot->generation) {
            throw new RuntimeException('Frontend snapshot changed; retry.');
        }
        foreach ($statuses as $status) {
            if ($status->generation !== $snapshot->generation) {
                throw new RuntimeException('Frontend snapshot changed; retry.');
            }
        }

        return ['schemaVersion' => 1, 'generation' => $snapshot->generation, 'pages' => $pages, 'slots' => (object) $slots, 'diagnostics' => (object) $diagnostics];
    }

    /** Trusted management seam, no unauthenticated write route. Missing IDs remain inactive.
     * @param  array<array-key, string>  $ids
     */
    public function order(string $host, array $ids, int $generation): int
    {
        new ApiOperation($host);
        if (! array_is_list($ids) || count($ids) > 128 || count(array_unique($ids)) !== count($ids)) {
            throw new RuntimeException('Invalid slot order.');
        }
        foreach ($ids as $id) {
            new ApiOperation($id);
        }

        return $this->connection->transaction(function () use ($host, $ids, $generation): int {
            $state = $this->connection->table('shared.module_registry_state')->where('id', 1)->lockForUpdate()->first();
            if ($state === null || filter_var($state->generation, FILTER_VALIDATE_INT) !== $generation) {
                throw new RuntimeException('Stale slot order.');
            }
            /** @var array<string, list<string>> $orders */
            $orders = $this->orders($state->slot_order);
            if ($ids === []) {
                unset($orders[$host]);
            } else {
                $orders[$host] = $ids;
            }
            $json = json_encode((object) $orders, JSON_THROW_ON_ERROR);
            if (strlen($json) > 65536) {
                throw new RuntimeException('Slot order limit.');
            }
            $this->connection->table('shared.module_registry_state')->where('id', 1)->update(['slot_order' => $json, 'generation' => $generation + 1]);

            return $generation + 1;
        });
    }

    /** @return array<string, list<string>> */
    private function orders(mixed $value): array
    {
        if (! is_string($value)) {
            throw new RuntimeException('Invalid stored slot order.');
        }
        $data = json_decode($value, true, flags: JSON_THROW_ON_ERROR);
        if (! is_array($data)) {
            throw new RuntimeException('Invalid stored slot order.');
        }
        $result = [];
        foreach ($data as $key => $ids) {
            if (! is_string($key) || ! is_array($ids) || ! array_is_list($ids)) {
                throw new RuntimeException('Invalid stored slot order.');
            }
            foreach ($ids as $id) {
                if (! is_string($id)) {
                    throw new RuntimeException('Invalid stored slot order.');
                }
            }
            $result[$key] = $ids;
        }

        return $result;
    }

    private function allowed(string $module, string $operation, ?string $permission, int $generation): bool
    {
        return $permission === null || $this->authorizer->allows(new OperationContext($module, $operation, 'frontend', 'frontend', $generation), $permission);
    }
}
