<?php

namespace Runa\ModuleFramework\Settings;

use Runa\Contracts\Modules\ApiOperation;
use Runa\Contracts\Modules\SettingDefinition;
use Runa\Contracts\Modules\SettingType;
use RuntimeException;

/** Explicit trusted bootstrap registration, never DB-supplied executable names. */
final class SettingTypes
{
    /** @var array<string, SettingType> */
    private array $custom = [];

    public function register(string $key, SettingType $type): void
    {
        new ApiOperation($key);
        if (in_array($key, ['string', 'integer', 'decimal', 'boolean', 'enum', 'array'], true) || isset($this->custom[$key])) {
            throw new RuntimeException('Setting type already registered.');
        }
        $this->custom[$key] = $type;
    }

    public function accepts(SettingDefinition $definition, mixed $value): bool
    {
        if (! in_array($definition->type, ['string', 'integer', 'decimal', 'boolean', 'enum', 'array'], true) && ! isset($this->custom[$definition->type])) {
            throw new RuntimeException('Unsupported setting type.');
        }
        $choices = $definition->options['choices'] ?? [];
        if ($definition->type === 'enum' && (! is_array($choices) || ! array_is_list($choices) || $choices === [])) {
            throw new RuntimeException('Enum choices required.');
        }
        if ($value === null) {
            return $definition->nullable;
        }

        return match ($definition->type) {
            'string' => is_string($value),
            'integer' => is_int($value),
            // Exact decimals stay strings; no binary float or implicit coercion.
            'decimal' => is_string($value) && preg_match('/\A-?(?:0|[1-9][0-9]*)(?:\.[0-9]+)?\z/D', $value) === 1,
            'boolean' => is_bool($value),
            'enum' => in_array($value, is_array($choices) ? $choices : [], true),
            'array' => is_array($value),
            default => $this->custom[$definition->type]->accepts($value, $definition->options),
        };
    }
}
