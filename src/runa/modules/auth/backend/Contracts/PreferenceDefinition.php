<?php

namespace Runa\Modules\Auth\Contracts;

use Runa\ModuleFramework\Api\BoundaryValues;

final readonly class PreferenceDefinition
{
    /** @var array{}|array{pl: string, en: string} */
    public array $names;

    /** Source-owned validation rules only; never supplied by the browser.
     *
     * @param  list<string>  $rules
     * @param  array<array-key, mixed>  $names
     */
    public function __construct(public string $view, public string $key, public string $type,
        public mixed $default, public int $version = 1, public array $rules = [], array $names = [])
    {
        new PreferenceScope('runa', 'runa.auth', $view, $key);
        BoundaryValues::check($default, "\0");
        if ($version < 1 || ! in_array($type, ['boolean', 'integer', 'string', 'array', 'object'], true)
            || count($rules) > 32 || strlen(json_encode([$default, $rules, $names], JSON_THROW_ON_ERROR)) > 32768
            || ($names !== [] && (array_keys($names) !== ['pl', 'en'] || ! is_string($names['pl']) || ! is_string($names['en'])))) {
            throw new \InvalidArgumentException('Invalid preference declaration.');
        }
        $this->names = $names === [] ? [] : ['pl' => $names['pl'], 'en' => $names['en']];
    }
}
