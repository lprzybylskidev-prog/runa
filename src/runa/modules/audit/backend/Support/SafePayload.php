<?php

namespace Runa\Modules\Audit\Support;

/** Redact before persistence, reject unsupported values rather than serializing objects. */
final class SafePayload
{
    public static function secretName(string $key): bool
    {
        $key = strtolower(preg_replace('/[^a-z0-9]/i', '', $key) ?? '');

        return preg_match('/password|passwd|pwd|hash|secret|otp|session|token|recovery|backupcode|cookie|authorization|credential|privatekey|apikey|remember|trust.*device|invitation/', $key) === 1;
    }

    /** @param array<array-key, mixed> $value
     * @param  array<array-key, mixed>  $sensitiveFields
     * @return array<array-key, mixed>
     */
    public static function clean(array $value, array $sensitiveFields = []): array
    {
        if (! array_is_list($sensitiveFields) || count($sensitiveFields) > 64) {
            throw new \InvalidArgumentException('Invalid Audit sensitive aliases.');
        }
        $approved = [];
        foreach ($sensitiveFields as $field) {
            if (! is_string($field) || strlen($field) > 128 || preg_match('/\A[a-zA-Z0-9_.-]+\z/D', $field) !== 1) {
                throw new \InvalidArgumentException('Invalid Audit sensitive alias.');
            }
            $approved[] = $field;
        }
        $nodes = 0;
        $result = self::walk($value, 0, $nodes, $approved);
        if (strlen(json_encode($result, JSON_THROW_ON_ERROR)) > 65536) {
            throw new \InvalidArgumentException('Audit payload exceeds 64 KiB.');
        }

        if (! is_array($result)) {
            throw new \LogicException('Audit root must remain an array.');
        }

        return $result;
    }

    /** @param list<string> $sensitiveFields */
    private static function walk(mixed $value, int $depth, int &$nodes, array $sensitiveFields): mixed
    {
        if (++$nodes > 1024 || $depth > 8) {
            throw new \InvalidArgumentException('Audit payload exceeds structural limits.');
        }
        if (is_array($value)) {
            $result = [];
            foreach ($value as $key => $entry) {
                if (is_string($key) && (strlen($key) > 128 || preg_match('/\A[a-zA-Z0-9_.-]+\z/D', $key) !== 1)) {
                    throw new \InvalidArgumentException('Invalid Audit payload key.');
                }
                $result[$key] = is_string($key) && (self::secretName($key) || in_array($key, $sensitiveFields, true)) ? '[REDACTED]' : self::walk($entry, $depth + 1, $nodes, $sensitiveFields);
            }

            return $result;
        }
        if (is_string($value)) {
            if (strlen($value) > 8192 || ! mb_check_encoding($value, 'UTF-8')) {
                throw new \InvalidArgumentException('Invalid Audit text size or encoding.');
            }

            // Defense for recognisable credentials under an aliased field name.
            return preg_match('/(?:bearer\s|basic\s|-----BEGIN|\$2[aby]\$|\$argon2|(?:password|token|secret|otp|cookie|authorization)\s*[=:])/i', $value) ? '[REDACTED]' : $value;
        }
        if ($value === null || is_bool($value) || is_int($value) || (is_float($value) && is_finite($value))) {
            return $value;
        }
        throw new \InvalidArgumentException('Audit payload must contain bounded JSON values.');
    }
}
