<?php

namespace Runa\ModuleFramework\Management;

use InvalidArgumentException;

/** Internal structural decoding; owning declarations and Actions retain semantic validation. */
final class InputValues
{
    public static function string(mixed $value): string
    {
        return is_string($value) ? $value : throw new InvalidArgumentException('Expected a string.');
    }

    public static function nullableString(mixed $value): ?string
    {
        return $value === null ? null : self::string($value);
    }

    public static function integer(mixed $value): int
    {
        return is_int($value) ? $value : throw new InvalidArgumentException('Expected an integer.');
    }

    public static function nullableInteger(mixed $value): ?int
    {
        return $value === null ? null : self::integer($value);
    }

    public static function boolean(mixed $value): bool
    {
        return is_bool($value) ? $value : throw new InvalidArgumentException('Expected a boolean.');
    }

    public static function nullableBoolean(mixed $value): ?bool
    {
        return $value === null ? null : self::boolean($value);
    }

    /** @return array<string,mixed> */
    public static function map(mixed $value): array
    {
        if (! is_array($value) || array_any(array_keys($value), static fn ($key): bool => ! is_string($key))) {
            throw new InvalidArgumentException('Expected a field map.');
        }

        /** @var array<string,mixed> $value Validated string keys. */
        return $value;
    }

    /** @return list<mixed> */
    public static function list(mixed $value): array
    {
        if (! is_array($value) || ! array_is_list($value)) {
            throw new InvalidArgumentException('Expected a list.');
        }

        return $value;
    }

    /** @return list<string> */
    public static function strings(mixed $value): array
    {
        if (! is_array($value) || ! array_is_list($value)) {
            throw new InvalidArgumentException('Expected a string list.');
        }

        return array_map(self::string(...), $value);
    }

    /** @return array<string,scalar|null> */
    public static function filters(mixed $value): array
    {
        $items = self::map($value);
        foreach ($items as $item) {
            if ($item !== null && ! is_scalar($item)) {
                throw new InvalidArgumentException('Invalid filter value.');
            }
        }

        /** @var array<string,scalar|null> $items Validated every scalar. */
        return $items;
    }

    /** @return list<array{key:string,direction:string}> */
    public static function sort(mixed $value): array
    {
        if (! is_array($value) || ! array_is_list($value)) {
            throw new InvalidArgumentException('Invalid sort list.');
        }
        $seen = [];
        $result = [];
        foreach ($value as $item) {
            if (! is_array($item) || count($item) !== 2 || ! is_string($item['key'] ?? null) || ! in_array($item['direction'] ?? null, ['asc', 'desc'], true) || isset($seen[$item['key']])) {
                throw new InvalidArgumentException('Invalid sort entry.');
            }
            DeclarationRules::key($item['key']);
            $seen[$item['key']] = true;
            $result[] = ['key' => $item['key'], 'direction' => $item['direction']];
        }

        return $result;
    }

    public static function options(mixed $items): void
    {
        if (! is_array($items) || ! array_is_list($items)) {
            throw new InvalidArgumentException('Invalid options list.');
        }
        foreach ($items as $item) {
            if (! is_array($item) || count($item) !== 2 || ! is_string($item['value'] ?? null) || ! is_string($item['label'] ?? null) || strlen($item['value']) > 256) {
                throw new InvalidArgumentException('Invalid management option.');
            }
            DeclarationRules::text($item['label']);
        }
    }

    /** @param class-string $type */
    public static function objects(mixed $items, string $type, int $limit): void
    {
        if (! is_array($items) || ! array_is_list($items) || count($items) > $limit) {
            throw new InvalidArgumentException('Invalid declaration list.');
        }
        foreach ($items as $item) {
            if (! $item instanceof $type) {
                throw new InvalidArgumentException('Invalid declaration item.');
            }
        }
    }
}
