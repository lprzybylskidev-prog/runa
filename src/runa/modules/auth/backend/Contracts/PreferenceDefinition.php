<?php

namespace Runa\Modules\Auth\Contracts;

use Runa\ModuleFramework\Api\BoundaryValues;

final readonly class PreferenceDefinition
{
    /** Source-owned validation rules only; never supplied by the browser. */
    public function __construct(public string $view, public string $key, public string $type,
        public mixed $default, public int $version = 1, public array $rules = [], public array $names = [])
    {
        new PreferenceScope('runa', 'runa.auth', $view, $key);
        BoundaryValues::check($default, "\0");
        if ($version < 1 || ! in_array($type, ['boolean', 'integer', 'string', 'array', 'object'], true)
            || count($rules) > 32 || strlen(json_encode([$default, $rules, $names], JSON_THROW_ON_ERROR)) > 32768
            || ($names !== [] && array_keys($names) !== ['pl', 'en'])) {
            throw new \InvalidArgumentException('Invalid preference declaration.');
        }
    }
}
