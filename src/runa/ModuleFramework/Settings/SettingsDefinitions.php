<?php

namespace Runa\ModuleFramework\Settings;

use Illuminate\Contracts\Validation\Factory;
use Runa\Contracts\Modules\SettingDefinition;
use Runa\Contracts\Modules\SettingsHandler;
use Runa\Contracts\Modules\SettingsProvider;
use Runa\ModuleFramework\Api\BoundaryValues;
use Runa\ModuleFramework\Api\ModuleDeclarations;
use Runa\Support\Modules\ModuleRecord;
use RuntimeException;
use Throwable;

final readonly class SettingsDefinitions
{
    public function __construct(private ModuleDeclarations $declarations, private SettingTypes $types, private Factory $validator) {}

    /** One provider owns cross-field validation and the reversible application boundary.
     * @return array{array<string, SettingDefinition>, SettingsProvider, string}
     */
    public function for(ModuleRecord $record): array
    {
        try {
            $classes = $this->declarations->classes($record, 'settings');
            if (count($classes) !== 1) {
                throw new RuntimeException('Exactly one settings provider required.');
            }
            $provider = $this->declarations->make($record, $classes[0], SettingsProvider::class);
            if (! $provider instanceof SettingsProvider) {
                throw new RuntimeException;
            }
            $definitions = $defaults = [];
            foreach ($provider->settings() as $definition) {
                if (isset($definitions[$definition->key]) || count($definitions) >= 128) {
                    throw new RuntimeException;
                }
                $definitions[$definition->key] = $definition;
                $defaults[$definition->key] = $definition->default;
            }
            $handler = $provider->handler();
            $type = $this->declarations->inspect($record, $handler);
            if (! $type->isInstantiable() || ! $type->implementsInterface(SettingsHandler::class)) {
                throw new RuntimeException;
            }
            $this->validate($definitions, $provider, $defaults);

            return [$definitions, $provider, $handler];
        } catch (Throwable) {
            throw new RuntimeException('Invalid settings declaration or defaults.');
        }
    }

    /** @param array<string, SettingDefinition> $definitions
     * @param  array<string, mixed>  $values
     */
    public function validate(array $definitions, SettingsProvider $provider, #[\SensitiveParameter] array $values): void
    {
        try {
            BoundaryValues::check($values, "\0");
            if (strlen(json_encode($values, JSON_THROW_ON_ERROR)) > 32768 || array_diff_key($values, $definitions) !== [] || array_diff_key($definitions, $values) !== []) {
                throw new RuntimeException;
            }
            foreach ($definitions as $key => $definition) {
                $rules = match ($definition->type) {
                    'integer' => ['integer'],
                    'decimal' => ['numeric'],
                    default => [],
                };
                if ($definition->nullable) {
                    $rules[] = 'nullable';
                }
                if (! $this->types->accepts($definition, $values[$key]) || $this->validator->make(['value' => $values[$key]], ['value' => [...$rules, ...$definition->rules]])->fails()) {
                    throw new RuntimeException;
                }
            }
            if (! $provider->valid($values)) {
                throw new RuntimeException;
            }
        } catch (Throwable) {
            // Never chain a validator/provider exception containing candidate secrets.
            throw new RuntimeException('Invalid settings values.');
        }
    }

    public function handler(ModuleRecord $record, string $class): SettingsHandler
    {
        $handler = $this->declarations->make($record, $class, SettingsHandler::class);
        if (! $handler instanceof SettingsHandler) {
            throw new RuntimeException('Invalid settings handler.');
        }

        return $handler;
    }
}
