<?php

namespace Runa\Modules\Auth\Support;

use Illuminate\Contracts\Validation\Factory;
use Runa\Modules\Auth\Contracts\PreferenceDefinition;
use Runa\Modules\Auth\Contracts\PreferenceProvider;
use Runa\Modules\Auth\Contracts\PreferenceScope;
use Runa\Support\Modules\Api\BoundaryValues;
use Runa\Support\Modules\Api\ModuleDeclarations;
use Runa\Support\Modules\Api\OperationRejected;
use Runa\Support\Modules\ModuleAvailability;
use Runa\Support\Modules\ModuleRegistry;

final readonly class PreferenceDeclarations
{
    public function __construct(private ModuleRegistry $registry, private ModuleAvailability $availability,
        private ModuleDeclarations $declarations, private Factory $validator) {}

    public function resolve(PreferenceScope $scope): array
    {
        if (! in_array($scope->application, ['runa', config('branding.application_id')], true)) {
            throw new OperationRejected('preference_application_unknown', 'runa.auth', 'preferences', 422);
        }
        $record = $this->registry->snapshot()->modules[$scope->module] ?? null;
        if ($record === null || ! $this->availability->statuses($scope->module)[$scope->module]->available) {
            throw new OperationRejected('preference_module_unavailable', 'runa.auth', 'preferences', 409);
        }
        foreach ($this->declarations->classes($record, 'settings') as $class) {
            $provider = $this->declarations->make($record, $class);
            if (! $provider instanceof PreferenceProvider) {
                continue;
            }
            $found = null;
            $seen = [];
            foreach ($provider->preferences() as $definition) {
                if (! $definition instanceof PreferenceDefinition || count($seen) >= 256 || isset($seen[$definition->view.':'.$definition->key])) {
                    throw new \RuntimeException('Invalid preference declarations.');
                }
                $seen[$definition->view.':'.$definition->key] = true;
                $this->validate($provider, $definition, $definition->default);
                if ($definition->view === $scope->view && $definition->key === $scope->key) {
                    $found = $definition;
                }
            }
            if ($found !== null) {
                return [$provider, $found];
            }
        }
        throw new OperationRejected('preference_undeclared', 'runa.auth', 'preferences', 422);
    }

    public function validate(PreferenceProvider $provider, PreferenceDefinition $definition, mixed $value): void
    {
        BoundaryValues::check($value, "\0");
        $validType = match ($definition->type) {
            'boolean' => is_bool($value), 'integer' => is_int($value), 'string' => is_string($value),
            'array' => is_array($value) && array_is_list($value),
            'object' => is_array($value) && ($value === [] || ! array_is_list($value)),
        };
        if (! $validType || strlen(json_encode($value, JSON_THROW_ON_ERROR)) > 32768
            || $this->validator->make(['value' => $value], ['value' => $definition->rules])->fails()
            || ! $provider->validPreference($definition->view, $definition->key, $value)) {
            throw new OperationRejected('preference_invalid', 'runa.auth', 'preferences', 422);
        }
    }
}
