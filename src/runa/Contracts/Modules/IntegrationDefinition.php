<?php

namespace Runa\Contracts\Modules;

use InvalidArgumentException;
use Runa\ModuleFramework\Api\BoundaryValues;
use Runa\Support\Modules\ModuleIdentity;

final readonly class IntegrationDefinition
{
    /** Non-sensitive configuration only.
     * @param  array<string, string>  $names
     * @param  array<string, mixed>  $defaults
     */
    public function __construct(public string $key, public string $target, public bool $defaultEnabled, public array $names, public array $defaults = [], public int $schemaVersion = 1)
    {
        new ApiOperation($key);
        new ModuleIdentity($target);
        BoundaryValues::check($defaults, "\0");
        if ($schemaVersion !== 1 || array_keys($names) !== ['pl', 'en'] || strlen(json_encode($defaults, JSON_THROW_ON_ERROR)) > 8192) {
            throw new InvalidArgumentException('Invalid integration metadata.');
        }
        foreach ($names as $name) {
            if (trim($name) === '' || strlen($name) > 256 || preg_match('/[\x00-\x1f]/', $name)) {
                throw new InvalidArgumentException('Invalid integration name.');
            }
        }
    }
}
