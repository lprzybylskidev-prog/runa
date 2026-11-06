<?php

namespace Runa\Contracts\Modules;

use InvalidArgumentException;
use Runa\ModuleFramework\Api\BoundaryValues;
use Runa\Support\Modules\ModuleIdentity;

final readonly class IntegrationDefinition
{
    /** Source-owned non-sensitive configuration only.
     * @param  array<string, string>  $names
     * @param  array<string, mixed>  $config
     */
    public function __construct(public string $key, public string $target, public bool $enabled, public array $names, public array $config = [], public int $schemaVersion = 2)
    {
        new ApiOperation($key);
        new ModuleIdentity($target);
        BoundaryValues::check($config, "\0");
        if ($schemaVersion !== 2 || array_keys($names) !== ['pl', 'en'] || strlen(json_encode($config, JSON_THROW_ON_ERROR)) > 8192) {
            throw new InvalidArgumentException('Invalid integration metadata.');
        }
        foreach ($names as $name) {
            if (trim($name) === '' || strlen($name) > 256 || preg_match('/[\x00-\x1f]/', $name)) {
                throw new InvalidArgumentException('Invalid integration name.');
            }
        }
    }
}
