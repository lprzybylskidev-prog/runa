<?php

namespace Runa\ModuleFramework\Management;

use InvalidArgumentException;
use Runa\Contracts\Modules\ApiOperation;

/** Internal validation shared by the additive readonly management declarations. */
final class DeclarationRules
{
    public static function key(string $key): void
    {
        new ApiOperation($key);
    }

    public static function permission(string $permission): void
    {
        self::key($permission);
        if (! str_contains($permission, '.')) {
            throw new InvalidArgumentException('Management requires a native qualified permission.');
        }
    }

    /** @param string|array{pl:string,en:string} $text */
    public static function text(string|array $text): void
    {
        if (is_array($text)) {
            $keys = array_keys($text);
            sort($keys);
            if ($keys !== ['en', 'pl'] || ! is_string($text['pl']) || ! is_string($text['en'])) {
                throw new InvalidArgumentException('Management text requires Polish and English.');
            }
            self::text($text['pl']);
            self::text($text['en']);

            return;
        }
        if (trim($text) === '' || mb_strlen($text) > 160 || preg_match('/[\p{C}]/u', $text) !== 0) {
            throw new InvalidArgumentException('Invalid management text.');
        }
    }

    public static function presentation(int $order, ?string $component, int $schema = 1): void
    {
        if (abs($order) > 1000000 || $schema !== 1) {
            throw new InvalidArgumentException('Unsupported management declaration.');
        }
        if ($component !== null) {
            self::key($component);
        }
    }

    /** @param array<mixed> $items */
    public static function collection(array $items, string $type, int $limit, string $key = 'key'): void
    {
        if (! array_is_list($items) || count($items) > $limit) {
            throw new InvalidArgumentException('Management declaration limit.');
        }
        $seen = [];
        foreach ($items as $item) {
            if (! $item instanceof $type) {
                throw new InvalidArgumentException('Invalid management declaration type.');
            }
            $identity = $key === 'field' ? $item->field->key : $item->$key;
            if (isset($seen[$identity])) {
                throw new InvalidArgumentException('Invalid or duplicate management declaration.');
            }
            $seen[$identity] = true;
        }
    }

    public static function className(string $class): void
    {
        if (preg_match('/\A[A-Za-z_][A-Za-z0-9_]*(?:\\\\[A-Za-z_][A-Za-z0-9_]*)+\z/D', $class) !== 1) {
            throw new InvalidArgumentException('Invalid management class identity.');
        }
    }

    public static function publicId(?string $id): void
    {
        if ($id !== null && preg_match('/\A[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}\z/Di', $id) !== 1) {
            throw new InvalidArgumentException('Management requires a public UUIDv7.');
        }
    }

    public static function revision(string $revision): void
    {
        if ($revision === '' || strlen($revision) > 256 || preg_match('/[\x00-\x1f\x7f]/', $revision)) {
            throw new InvalidArgumentException('Invalid management revision.');
        }
    }

    /** @param array<array-key, mixed> $values */
    public static function json(array $values): void
    {
        foreach (array_keys($values) as $key) {
            if (! is_string($key)) {
                throw new InvalidArgumentException('Management values require a field map.');
            }
            self::key($key);
        }
        self::value($values, 0);
        try {
            $encoded = json_encode($values, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new InvalidArgumentException('Management requires valid JSON values.');
        }
        if (strlen($encoded) > 65536) {
            throw new InvalidArgumentException('Management payload limit.');
        }
    }

    private static function value(mixed $value, int $depth): void
    {
        if ($depth > 8 || is_object($value) || is_resource($value) || (is_float($value) && ! is_finite($value))) {
            throw new InvalidArgumentException('Management requires bounded JSON values.');
        }
        if (is_array($value)) {
            foreach ($value as $child) {
                self::value($child, $depth + 1);
            }
        }
    }
}
