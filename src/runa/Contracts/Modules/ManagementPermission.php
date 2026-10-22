<?php

namespace Runa\Contracts\Modules;

final readonly class ManagementPermission
{
    /** @param array<string, string> $names
     * @param  array<string, string>  $descriptions
     */
    public function __construct(public string $key, public array $names, public array $descriptions, public int $schemaVersion = 1)
    {
        new ApiOperation($key);
        ManagementAction::localization($names, $descriptions, $schemaVersion);
    }
}
