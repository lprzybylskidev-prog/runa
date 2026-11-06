<?php

namespace Runa\Modules\Auth\Support;

use Illuminate\Contracts\Validation\Factory;
use Runa\ModuleFramework\Api\BoundaryValues;
use Runa\ModuleFramework\Api\ModuleDeclarations;
use Runa\ModuleFramework\ModuleAvailability;
use Runa\ModuleFramework\ModuleRegistry;
use Runa\Modules\Auth\Contracts\PreferenceDefinition;
use Runa\Modules\Auth\Contracts\PreferenceProvider;
use Runa\Modules\Auth\Contracts\PreferenceScope;
use Runa\Support\Modules\Api\OperationRejected;

final readonly class PreferenceDeclarations
{
    public function __construct(private ModuleRegistry $registry, private ModuleAvailability $availability,
        private ModuleDeclarations $declarations, private Factory $validator) {}

    /** @return array{PreferenceProvider, PreferenceDefinition} */
    public function resolve(PreferenceScope $scope): array
    {
        foreach ($this->forModule($scope->application, $scope->module) as [$provider, $definition]) {
            if ($definition->view === $scope->view && $definition->key === $scope->key) {
                return [$provider, $definition];
            }
        }
        throw new OperationRejected('preference_undeclared', 'runa.auth', 'preferences', 422);
    }

    /** Current, validated declarations for one read; never retained across operations.
     * @return list<array{PreferenceProvider, PreferenceDefinition}>
     */
    public function forModule(string $application, string $module): array
    {
        if (! in_array($application, ['runa', config('branding.application_id')], true)) {
            throw new OperationRejected('preference_application_unknown', 'runa.auth', 'preferences', 422);
        }
        $record = $this->registry->snapshot()->modules[$module] ?? null;
        if ($record === null || ! $this->availability->statuses($module)[$module]->available) {
            throw new OperationRejected('preference_module_unavailable', 'runa.auth', 'preferences', 409);
        }
        $result = [];
        foreach ($this->declarations->classes($record, 'settings') as $class) {
            $provider = $this->declarations->make($record, $class);
            if (! $provider instanceof PreferenceProvider) {
                continue;
            }
            $seen = [];
            /** @var array<array-key, mixed> $preferences Native provider arrays are validated before use. */
            $preferences = $provider->preferences();
            foreach ($preferences as $definition) {
                if (! $definition instanceof PreferenceDefinition || count($seen) >= 256 || isset($seen[$definition->view.':'.$definition->key])) {
                    throw new \RuntimeException('Invalid preference declarations.');
                }
                $seen[$definition->view.':'.$definition->key] = true;
                $this->validate($provider, $definition, $definition->default);
                $result[] = [$provider, $definition];
            }
        }

        return $result;
    }

    public function validate(PreferenceProvider $provider, PreferenceDefinition $definition, mixed $value): void
    {
        BoundaryValues::check($value, "\0");
        $validType = match ($definition->type) {
            'boolean' => is_bool($value), 'integer' => is_int($value), 'string' => is_string($value),
            'array' => is_array($value) && array_is_list($value),
            'object' => is_array($value) && ($value === [] || ! array_is_list($value)),
            default => throw new \RuntimeException('Invalid preference declaration type.'),
        };
        if (! $validType || strlen(json_encode($value, JSON_THROW_ON_ERROR)) > 32768
            || $this->validator->make(['value' => $value], ['value' => $definition->rules])->fails()
            || ! $provider->validPreference($definition->view, $definition->key, $value)) {
            throw new OperationRejected('preference_invalid', 'runa.auth', 'preferences', 422);
        }
    }
}
