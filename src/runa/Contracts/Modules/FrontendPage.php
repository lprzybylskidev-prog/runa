<?php

namespace Runa\Contracts\Modules;

use InvalidArgumentException;

final readonly class FrontendPage
{
    /** @param array{pl: string, en: string} $names */
    public function __construct(public string $key, public string $component, public array $names, public ?string $permission = null, public ?string $area = null, public string $icon = 'layers-intersect', public int $schemaVersion = 1)
    {
        new ApiOperation($key, $permission);
        new ApiOperation($component);
        if ($schemaVersion !== 1 || preg_match('/\A[a-z][a-z0-9_]{0,63}\z/D', $key) !== 1 || ! in_array($icon, ['layers-intersect', 'adjustments-horizontal', 'sun', 'moon', 'device-desktop', 'file-type-csv', 'file-type-xls', 'file-type-pdf', 'printer'], true) || ($area !== null && preg_match('/\A[a-z][a-z0-9_]{0,63}\z/D', $area) !== 1)) {
            throw new InvalidArgumentException('Invalid frontend page.');
        }
        foreach (['pl', 'en'] as $locale) {
            if (trim($names[$locale]) === '' || strlen($names[$locale]) > 160) {
                throw new InvalidArgumentException('Invalid frontend names.');
            }
        }
    }
}
