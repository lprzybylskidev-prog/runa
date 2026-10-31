<?php

namespace Runa\Integration\Core;

use Closure;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Validation\ValidationException;
use Runa\Contracts\Modules\AdministrativePreview;
use Runa\Contracts\Modules\ApiOperation;
use Runa\Contracts\Modules\SettingsActor;
use Runa\Identity\ApplicationIdentity;
use Runa\ModuleFramework\Api\OperationGuard;
use Runa\ModuleFramework\Api\PrincipalScope;
use Runa\ModuleFramework\Events\ModuleIntegrations;
use Runa\ModuleFramework\HealthPolicies;
use Runa\ModuleFramework\ModuleHealth;
use Runa\ModuleFramework\ModuleOperationHistory;
use Runa\ModuleFramework\ModuleRegistry;
use Runa\ModuleFramework\ModuleTransitions;
use Runa\Settings\SettingStore;
use Runa\Support\Modules\Api\OperationRejected;
use Runa\Support\Modules\Settings\ModuleSettings;
use Runa\Support\Modules\Work\ModuleWork;
use Runa\Support\Settings\ApplicationSettings;

/** Fixed trusted integration. Never accepts an engine method, actor or executable metadata. */
final readonly class AdminOperations
{
    public function __construct(private AdminEntry $entry, private OperationGuard $guard,
        private PrincipalScope $principals, private ModuleRegistry $registry,
        private AdminDiagnostics $diagnostics, private ApplicationIdentity $identity,
        private ModuleHealth $health, private HealthPolicies $healthPolicies,
        private ModuleTransitions $transitions, private ModuleSettings $settings,
        private ModuleIntegrations $integrations, private ModuleWork $work, private Encrypter $encrypter, private AdminOperationalAuthority $authority, private ModuleOperationHistory $operationHistory) {}

    /** @param array<string,mixed> $input @return array<string,mixed> */
    public function read(string $view, array $input = []): array
    {
        return $this->safe(function () use ($view, $input): array {
            [$permission, $keys] = match ($view) {
                'capabilities' => ['enter', []],
                'application' => ['enter', []],
                'diagnostics' => ['diagnostics.read', ['filter']],
                'module' => ['diagnostics.read', ['module']],
                'settings' => ['settings.read', ['module']],
                'integrations' => ['integrations.manage', ['module']],
                'work' => ['work.read', ['module', 'limit']],
                'history' => ['enter', ['module', 'cursor', 'limit']],
                'work.status' => ['work.read', ['id']],
                'modules.status' => ['modules.manage', ['id']],
                'settings.status' => ['settings.manage', ['id']],
                default => throw $this->invalid(),
            };
            $this->keys($input, $keys);

            return $this->admit($view, $permission, function () use ($view, $input): array {
                return match ($view) {
                    'capabilities' => ['capabilities' => $this->capabilities()],
                    'history' => $this->operationHistory($input),
                    'application' => app(ApplicationSettings::class)->read() + ['definitions' => app(SettingStore::class)->definitions()],
                    'diagnostics' => $this->diagnostics->snapshot($this->text($input, 'filter', 'enabled')) + [
                        'platform_version' => $this->identity->platformVersion,
                        'application_version' => $this->identity->applicationVersion,
                        'runtime' => ['php' => PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION.'.'.PHP_RELEASE_VERSION],
                        'capabilities' => $this->capabilities(),
                    ],
                    'module' => $this->module($this->moduleId($input)),
                    'settings' => $this->settingsView($this->moduleId($input)),
                    'integrations' => ['generation' => $this->registry->snapshot()->generation, 'integrations' => $this->integrations->metadata($this->moduleId($input))],
                    'work' => $this->active($this->moduleId($input), $this->integer($input, 'limit', 100, 1000)),
                    'work.status' => ['work' => $this->work->status($this->uuid($input))],
                    'modules.status' => ['transition' => $this->transitions->status($this->uuid($input))],
                    'settings.status' => ['transition' => $this->settings->status($this->uuid($input))],
                };
            });
        });
    }

    /** @param array<string,mixed> $input @return array<string,mixed> */
    public function execute(string $command, array $input): array
    {
        return $this->safe(function () use ($command, $input): array {
            [$permission, $keys, $independent] = match ($command) {
                'application.update' => ['enter', ['revision', 'patch'], false],
                'health.diagnose' => ['health.diagnose', ['module'], true],
                'health.configure' => ['health.configure', ['module', 'generation', 'overrides'], false],
                'modules.preview' => ['modules.manage', ['module', 'intent'], false],
                'modules.apply' => ['modules.manage', ['review', 'confirmed'], true],
                'modules.resume', 'modules.cancel' => ['modules.manage', ['id'], true],
                'settings.update' => ['settings.manage', ['module', 'generation', 'revision', 'patch'], true],
                'settings.recover' => ['settings.manage', ['id'], true],
                'integrations.configure' => ['integrations.manage', ['module', 'key', 'enabled', 'config', 'generation'], false],
                'work.cancel' => ['work.cancel', ['id'], false],
                default => throw $this->invalid(),
            };
            $this->keys($input, $keys);

            return $this->admit($command, $permission, function () use ($command, $input): array {
                switch ($command) {
                    case 'application.update':
                        return app(ApplicationSettings::class)->update($this->map($input, 'patch'), $this->integer($input, 'revision'));
                    case 'health.diagnose':
                        $module = $this->moduleId($input);
                        $result = $this->health->diagnose($module);

                        return ['module' => $module, 'health' => $result['state'], 'blocked' => $result['blocked'],
                            'reasons' => array_values(array_unique(array_map(static fn ($reason): string => $reason->code, $result['reasons'])))];
                    case 'health.configure':
                        $this->health->configure($this->moduleId($input), $this->map($input, 'overrides'), $this->integer($input, 'generation'));

                        return ['status' => 'committed'];
                    case 'modules.preview':
                        return $this->preview($input);
                    case 'modules.apply':
                        if (($input['confirmed'] ?? null) !== true) {
                            throw $this->invalid();
                        }
                        $review = json_decode($this->encrypter->decrypt($this->text($input, 'review'), false), true, 16, JSON_THROW_ON_ERROR);
                        if (! is_array($review) || ($review['actor'] ?? null) !== $this->principals->current()?->publicId
                            || ! is_int($review['expires'] ?? null) || $review['expires'] <= now()->getTimestamp()) {
                            throw $this->invalid();
                        }
                        $this->optional($review['module']);
                        $preview = $this->transitions->preview($review['intent'], $review['module']);
                        if (! hash_equals($review['fingerprint'], $this->fingerprint($preview))) {
                            throw $this->stale();
                        }

                        return ['transition' => $this->transitions->apply($preview)];
                    case 'modules.resume':
                        return ['transition' => $this->transitions->resume($this->uuid($input))];
                    case 'modules.cancel':
                        return ['transition' => $this->transitions->cancel($this->uuid($input))];
                    case 'settings.update':
                        $module = $this->moduleId($input);
                        // Auth publication has its own reviewed consequences/proofs/restore workflow.
                        if ($module === 'runa.auth') {
                            throw new OperationRejected('admin_owner_workflow_required', 'runa.admin', $command, 403);
                        }

                        return ['transition' => $this->settings->update($module, $this->map($input, 'patch'), $this->integer($input, 'revision', null, PHP_INT_MAX, 0), $this->integer($input, 'generation'),
                            new SettingsActor('actor', $this->principals->current()->publicId))];
                    case 'settings.recover':
                        return ['transition' => $this->settings->recover($this->uuid($input))];
                    case 'integrations.configure':
                        if (! is_bool($input['enabled'] ?? null)) {
                            throw $this->invalid();
                        }

                        return ['generation' => $this->integrations->configure($this->moduleId($input), $this->text($input, 'key'), $input['enabled'], $this->map($input, 'config'), $this->integer($input, 'generation'))];
                    case 'work.cancel':
                        return ['work' => $this->work->requestCancellation($this->uuid($input))];
                }
                throw $this->invalid();
            }, $independent, true);
        });
    }

    /** @template T @param Closure():T $work @return T */
    private function admit(string $operation, string $permission, Closure $work, bool $independent = false, bool $write = false): mixed
    {
        $check = function () use ($operation, $permission): void {
            $this->guard->enter('runa.admin', new ApiOperation($operation, 'runa.admin.'.$permission));
        };
        // Prepare exact-operation health before entry's root transaction.
        $check();
        if (in_array($operation, ['application', 'application.update'], true)) {
            app(SettingStore::class)->prepare($operation === 'application.update');
        }
        if ($independent) {
            $this->entry->read(function () use ($check): void {
                $check();
            });
            $check();

            // Required native participants re-admit while the actual reservation/publication
            // transaction holds registry/authority/session locks, closing the preflight race.
            return $this->authority->run(function () use ($check): void {
                $this->entry->read(function () use ($check): void {
                    $check();
                });
            }, $work);
        }
        $operation = function () use ($check, $work): mixed {
            $check();

            return $work();
        };

        return $write ? $this->entry->write($operation) : $this->entry->read($operation);
    }

    /** @return list<string> */
    private function capabilities(): array
    {
        $result = [];
        foreach (['diagnostics.read', 'health.diagnose', 'health.configure', 'modules.manage', 'settings.read', 'settings.manage', 'integrations.manage', 'work.read', 'work.cancel', 'application.read', 'application.manage'] as $permission) {
            try {
                $this->guard->enter('runa.admin', new ApiOperation('operations.read', 'runa.admin.'.$permission));
                $result[] = $permission;
            } catch (OperationRejected) { /* Advertisement never substitutes for command authorization. */
            }
        }

        return $result;
    }

    /** @param array<string,mixed> $input @return array<string,mixed> */
    private function operationHistory(array $input): array
    {
        $module = $this->moduleId($input);
        $contexts = [];
        $intents = [];
        foreach (['modules.manage' => ['enable', 'disable', 'unregister'], 'settings.manage' => ['settings']] as $permission => $allowed) {
            try {
                $contexts[] = $this->guard->enter('runa.admin', new ApiOperation('history', 'runa.admin.'.$permission));
                array_push($intents, ...$allowed);
            } catch (OperationRejected) {
                // Each kind retains its existing native status/recovery authority.
            }
        }
        if ($intents === []) {
            throw new OperationRejected('admin_history_forbidden', 'runa.admin', 'history', 403);
        }
        $cursor = array_key_exists('cursor', $input) ? $this->uuid(['id' => $input['cursor']]) : null;
        $result = $this->operationHistory->read($module, $intents, $cursor, $this->integer($input, 'limit', 25, 100));
        foreach ($contexts as $context) {
            $this->guard->assertCurrent($context);
        }

        return $result;
    }

    /** @return array<string,mixed> */
    private function module(string $module): array
    {
        $record = $this->registry->snapshot()->modules[$module];
        $result = $this->diagnostics->module($module);
        try {
            $policy = $this->healthPolicies->resolve($record);
            $definition = $policy?->definition($record->healthOverrides);
            $result['health_policy'] = $definition;
            $result['health_overrides'] = $record->healthOverrides;
        } catch (\Throwable) {
            $result['health_diagnostic'] = 'health_policy_unavailable';
        }

        return $result;
    }

    /** @return array<string,mixed> */
    private function settingsView(string $module): array
    {
        return $this->settings->read($module) + ['editable' => $module !== 'runa.auth'];
    }

    /** @return array<string,mixed> */
    private function active(string $module, int $limit): array
    {
        $items = $this->work->active($module, min(1000, $limit + 1));

        // The native cap itself is explicitly reported as potentially truncated.
        return ['work' => array_slice($items, 0, $limit), 'truncated' => count($items) > $limit || count($items) === 1000];
    }

    /** @param array<string,mixed> $input @return array<string,mixed> */
    private function preview(array $input): array
    {
        $module = $this->moduleId($input);
        $this->optional($module);
        $intent = $this->text($input, 'intent');
        if (! in_array($intent, ['enable', 'disable', 'unregister'], true)) {
            throw $this->invalid();
        }
        $preview = $this->transitions->preview($intent, $module);
        $token = $preview->ready() ? $this->encrypter->encrypt(json_encode(['module' => $module, 'intent' => $intent,
            'actor' => $this->principals->current()->publicId, 'expires' => now()->getTimestamp() + 600, 'fingerprint' => $this->fingerprint($preview)], JSON_THROW_ON_ERROR), false) : null;

        return ['ready' => $preview->ready(), 'review' => $token, 'generation' => $preview->graph->generation,
            'affected' => $preview->graph->affected, 'changes' => $preview->graph->changes,
            'blockers' => [...array_map(static fn ($blocker): string => $blocker->code, $preview->graph->blockers), ...array_values($preview->blockers)], 'active_work' => $preview->activeWork];
    }

    private function fingerprint(AdministrativePreview $preview): string
    {
        return hash('sha256', json_encode($preview, JSON_THROW_ON_ERROR));
    }

    private function optional(string $module): void
    {
        $record = $this->registry->snapshot()->modules[$module] ?? null;
        if ($record === null || $record->kind !== 'optional') {
            throw $this->invalid();
        }
    }

    /** @param array<string,mixed> $input */
    private function moduleId(array $input): string
    {
        $module = $this->text($input, 'module');
        if (! isset($this->registry->snapshot()->modules[$module])) {
            throw new OperationRejected('admin_module_unknown', 'runa.admin', 'operations', 404);
        }

        return $module;
    }

    /** @param array<string,mixed> $input @param list<string> $keys */
    private function keys(array $input, array $keys): void
    {
        if (array_diff(array_keys($input), $keys) !== [] || strlen(json_encode($input, JSON_THROW_ON_ERROR)) > 65536) {
            throw $this->invalid();
        }
    }

    /** @param array<string,mixed> $input */
    private function text(array $input, string $key, ?string $default = null): string
    {
        $value = $input[$key] ?? $default;
        if (! is_string($value) || $value === '' || strlen($value) > 32768) {
            throw $this->invalid();
        }

        return $value;
    }

    /** @param array<string,mixed> $input */
    private function integer(array $input, string $key, ?int $default = null, int $max = PHP_INT_MAX, int $min = 1): int
    {
        $value = $input[$key] ?? $default;
        if (! is_int($value) || $value < $min || $value > $max) {
            throw $this->invalid();
        }

        return $value;
    }

    /** @param array<string,mixed> $input @return array<string,mixed> */
    private function map(array $input, string $key): array
    {
        $value = $input[$key] ?? null;
        if (! is_array($value) || ($value !== [] && array_is_list($value))) {
            throw $this->invalid();
        }

        return $value;
    }

    /** @param array<string,mixed> $input */
    private function uuid(array $input): string
    {
        $id = $this->text($input, 'id');
        if (preg_match('/\A[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}\z/D', $id) !== 1) {
            throw $this->invalid();
        }

        return $id;
    }

    /** @param Closure():array<string,mixed> $work @return array<string,mixed> */
    private function safe(Closure $work): array
    {
        try {
            $result = $work();
            if (strlen(json_encode($result, JSON_THROW_ON_ERROR)) > 1048576) {
                throw new OperationRejected('admin_projection_limit', 'runa.admin', 'operations', 409);
            }
            /** @var array<string,mixed> $projection */
            $projection = json_decode(json_encode($result, JSON_THROW_ON_ERROR), true, 32, JSON_THROW_ON_ERROR);

            return $projection;
        } catch (OperationRejected $error) {
            throw $error;
        } catch (ValidationException) {
            throw $this->invalid();
        } catch (\Throwable) {
            throw new OperationRejected('admin_operation_failed', 'runa.admin', 'operations', 409);
        }
    }

    private function invalid(): OperationRejected
    {
        return new OperationRejected('admin_operation_invalid', 'runa.admin', 'operations', 422);
    }

    private function stale(): OperationRejected
    {
        return new OperationRejected('admin_revision_changed', 'runa.admin', 'operations', 409);
    }
}
