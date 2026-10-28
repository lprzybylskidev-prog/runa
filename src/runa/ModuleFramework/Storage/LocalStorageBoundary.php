<?php

namespace Runa\ModuleFramework\Storage;

use RuntimeException;

final class LocalStorageBoundary
{
    public static function root(mixed $root): string
    {
        if (! is_string($root) || ! str_starts_with($root, '/') || str_contains($root, '//') || preg_match('~/\.{1,2}(/|$)~', $root)
            || preg_match('/[\x00-\x1f\x7f]/', $root)) {
            throw new RuntimeException('Module local storage requires a canonical absolute root.');
        }
        self::check($root);

        return rtrim($root, '/');
    }

    public static function check(string $path): void
    {
        $current = '';
        clearstatcache();
        foreach (explode('/', $path) as $segment) {
            if ($segment === '') {
                continue;
            }
            $current .= '/'.$segment;
            if (is_link($current)) {
                throw new RuntimeException('Module storage cannot traverse symbolic links.');
            }
        }
    }
}
