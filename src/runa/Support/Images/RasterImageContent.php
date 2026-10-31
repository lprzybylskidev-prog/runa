<?php

namespace Runa\Support\Images;

final class RasterImageContent
{
    public static function safe(string $bytes, string $mime): bool
    {
        return \Runa\Images\RasterImageContent::safe($bytes, $mime);
    }
}
