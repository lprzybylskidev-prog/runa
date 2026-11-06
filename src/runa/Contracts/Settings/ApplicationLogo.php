<?php

namespace Runa\Contracts\Settings;

use InvalidArgumentException;
use Runa\Contracts\Modules\ApiOperation;

final readonly class ApplicationLogo
{
    public function __construct(public string $key, public string $src, public string $accessibleName)
    {
        new ApiOperation($key);
        // Deliberately accept literal same-origin asset paths only: no URL decoding,
        // arbitrary filesystem selection, queries, fragments or remote fetch policy.
        if (strlen($src) > 2048 || preg_match('~\A/(?:[a-zA-Z0-9_-]+/)*[a-zA-Z0-9_-]+(?:[.][a-zA-Z0-9_-]+)*[.](?:png|jpg|jpeg|webp|svg)\z~D', $src) !== 1
            || trim($accessibleName) === '' || mb_strlen($accessibleName) > 160
            || ! mb_check_encoding($accessibleName, 'UTF-8') || preg_match('/[\p{C}]/u', $accessibleName)) {
            throw new InvalidArgumentException('Invalid application logo declaration.');
        }
    }
}
