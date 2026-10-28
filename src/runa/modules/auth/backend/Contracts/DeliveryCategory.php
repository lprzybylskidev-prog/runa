<?php

namespace Runa\Modules\Auth\Contracts;

use Runa\Contracts\Modules\ApiOperation;

final readonly class DeliveryCategory
{
    /** @var array{pl: string, en: string} */
    public array $names;

    /**
     * @param  array<array-key, mixed>  $names
     */
    public function __construct(public string $key, array $names, public bool $mandatory = false, public bool $sensitive = false)
    {
        new ApiOperation($key);
        if (array_keys($names) !== ['pl', 'en'] || ! is_string($names['pl']) || ! is_string($names['en'])
            || trim($names['pl']) === '' || trim($names['en']) === '' || strlen($names['pl']) > 256 || strlen($names['en']) > 256) {
            throw new \InvalidArgumentException('Invalid delivery category.');
        }
        $this->names = ['pl' => $names['pl'], 'en' => $names['en']];
    }
}
