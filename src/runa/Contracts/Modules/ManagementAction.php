<?php

namespace Runa\Contracts\Modules;

use InvalidArgumentException;

/** Stable action identity; a future authorized adapter binds execution separately. */
final readonly class ManagementAction
{
    /** @param array<string, string> $names
     * @param  array<string, string>  $descriptions
     */
    public function __construct(public string $key, public string $permission, public array $names, public array $descriptions, public string $renderer = 'action', public int $rendererVersion = 1, public int $schemaVersion = 1)
    {
        new ApiOperation($key, $permission);
        new ApiOperation($renderer);
        self::localization($names, $descriptions, $schemaVersion);
        if ($rendererVersion < 1) {
            throw new InvalidArgumentException('Invalid renderer version.');
        }
    }

    /** @param array<string, string> $names
     * @param  array<string, string>  $descriptions
     */
    public static function localization(array $names, array $descriptions, int $version): void
    {
        if ($version !== 1) {
            throw new InvalidArgumentException('Unsupported management schema.');
        }
        foreach ([$names, $descriptions] as $localized) {
            if (array_keys($localized) !== ['pl', 'en']) {
                throw new InvalidArgumentException('Management metadata requires Polish and English.');
            }
            foreach ($localized as $text) {
                if (trim($text) === '' || strlen($text) > 1024 || preg_match('/[\x00-\x1f]/', $text)) {
                    throw new InvalidArgumentException('Invalid management localization.');
                }
            }
        }
    }
}
