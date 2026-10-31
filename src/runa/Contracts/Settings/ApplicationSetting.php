<?php

namespace Runa\Contracts\Settings;

use InvalidArgumentException;
use Runa\Contracts\Modules\ApiOperation;

final readonly class ApplicationSetting
{
    /** @param list<string> $choices */
    public function __construct(public string $key, public string $type, public string|int|bool|null $default,
        public string $title, public string $readPermission, public string $writePermission,
        public ?int $maxLength = null, public array $choices = [])
    {
        new ApiOperation($key);
        new ApiOperation($readPermission);
        new ApiOperation($writePermission);
        if ($readPermission === '' || $writePermission === '' || ! in_array($type, ['string', 'integer', 'boolean', 'enum', 'timezone'], true)
            || ! self::text($title, 160) || ! str_contains($readPermission, '.') || ! str_contains($writePermission, '.') || ! array_is_list($choices) || count($choices) > 64
            || count(array_unique($choices, SORT_REGULAR)) !== count($choices)
            || ($type === 'string' ? ($maxLength === null || $maxLength < 1 || $maxLength > 4096) : $maxLength !== null)
            || ($type !== 'enum' && $choices !== [])) {
            throw new InvalidArgumentException('Invalid application setting declaration.');
        }
        foreach ($choices as $choice) {
            if (! is_string($choice)) {
                throw new InvalidArgumentException('Invalid setting choice.');
            }
            new ApiOperation($choice);
        }
        if (! $this->accepts($default)) {
            throw new InvalidArgumentException('Invalid application setting default.');
        }
    }

    public function accepts(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        return match ($this->type) {
            'string' => is_string($value) && self::text($value, $this->maxLength ?? 4096),
            'timezone' => is_string($value) && in_array($value, \DateTimeZone::listIdentifiers(\DateTimeZone::ALL_WITH_BC), true),
            'integer' => is_int($value) && abs($value) <= 9007199254740991,
            'boolean' => is_bool($value),
            'enum' => is_string($value) && in_array($value, $this->choices, true),
            default => false,
        };
    }

    private static function text(string $value, int $max): bool
    {
        return trim($value) !== '' && mb_check_encoding($value, 'UTF-8') && mb_strlen($value) <= $max
            && preg_match('/[\p{C}]/u', $value) === 0;
    }
}
