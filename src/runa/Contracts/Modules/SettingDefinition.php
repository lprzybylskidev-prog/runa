<?php

namespace Runa\Contracts\Modules;

use InvalidArgumentException;
use Runa\ModuleFramework\Api\BoundaryValues;

final readonly class SettingDefinition
{
    /** @param array<string, string> $names
     * @param  array<string, string>  $descriptions
     * @param  list<string>  $rules
     * @param  array<string, mixed>  $options
     */
    public function __construct(
        public string $key, public string $type, public mixed $default,
        public array $names, public array $descriptions,
        public bool $sensitive = false, public string $application = 'immediate',
        public bool $nullable = false, public array $rules = [], public array $options = [],
        public int $schemaVersion = 1,
    ) {
        new ApiOperation($key);
        new ApiOperation($type);
        BoundaryValues::check($default, "\0");
        BoundaryValues::check($options, "\0");
        if ($schemaVersion !== 1 || ! in_array($application, ['immediate', 'reinitialize'], true) || ($sensitive && ($default !== null || ! $nullable)) || strlen(json_encode([$default, $options, $rules], JSON_THROW_ON_ERROR)) > 8192 || count($rules) > 32) {
            throw new InvalidArgumentException('Invalid setting declaration.');
        }
        foreach ([$names, $descriptions] as $localized) {
            if (array_keys($localized) !== ['pl', 'en']) {
                throw new InvalidArgumentException('Setting requires Polish and English metadata.');
            }
            foreach ($localized as $text) {
                if (trim($text) === '' || strlen($text) > 1024 || preg_match('/[\x00-\x1f]/', $text)) {
                    throw new InvalidArgumentException('Invalid setting localization.');
                }
            }
        }
    }
}
