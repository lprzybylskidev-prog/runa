<?php

namespace Runa\Contracts\Settings;

use InvalidArgumentException;
use Runa\Support\Images\RasterImageContent;

final readonly class ApplicationImages
{
    /** @param array<string,string> $png Raw rasterized PNG bytes, never original uploads. */
    public function __construct(public array $png)
    {
        if ($png === [] || array_diff(array_keys($png), ['logo', 'logo_icon', 'favicon']) !== []
            || isset($png['logo']) !== isset($png['logo_icon'])) {
            throw new InvalidArgumentException('Invalid branding image selection.');
        }
        foreach ($png as $key => $bytes) {
            $logo = $key === 'logo';
            $size = is_string($bytes) ? @getimagesizefromstring($bytes) : false;
            if (! is_string($bytes) || strlen($bytes) > ($logo ? 524288 : 32768) || $size === false
                || ($size['mime'] ?? null) !== 'image/png' || ! RasterImageContent::safe($bytes, 'image/png')
                || ($logo ? ($size[0] < 1 || $size[1] < 1 || max($size[0], $size[1]) > 1024) : ($size[0] !== 64 || $size[1] !== 64))) {
                throw new InvalidArgumentException('Invalid branding PNG.');
            }
        }
    }
}
