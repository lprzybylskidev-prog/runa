<?php

namespace Runa\Modules\Auth\Support;

/** Inspect container metadata, never interpret compressed pixels as source code. */
final class AvatarImageContent
{
    public static function safe(string $bytes, string $mime): bool
    {
        return match ($mime) {
            'image/png' => self::png($bytes),
            'image/jpeg' => self::jpeg($bytes),
            'image/webp' => self::webp($bytes),
            default => false,
        };
    }

    private static function number(string $format, string $bytes): int
    {
        $values = unpack($format, $bytes);
        $number = $values[1] ?? null;

        return is_int($number) ? $number : -1;
    }

    private static function metadata(string $bytes): bool
    {
        // XML/XMP processing instructions are ordinary image metadata.
        return ! preg_match('/<\?(?!(?:xml|xpacket)\b)|<\s*(?:svg|script|html)\b/i', $bytes);
    }

    private static function png(string $bytes): bool
    {
        $size = strlen($bytes);
        if (! str_starts_with($bytes, "\x89PNG\r\n\x1a\n")) {
            return false;
        }
        for ($offset = 8; $offset + 12 <= $size;) {
            $length = self::number('N', substr($bytes, $offset, 4));
            $kind = substr($bytes, $offset + 4, 4);
            if ($length < 0 || $length > $size - $offset - 12) {
                return false;
            }
            $data = substr($bytes, $offset + 8, $length);
            if (! in_array($kind, ['IHDR', 'PLTE', 'IDAT', 'tRNS'], true) && ! self::metadata($data)) {
                return false;
            }
            $offset += 12 + $length;
            if ($kind === 'IEND') {
                return $length === 0 && $offset === $size;
            }
        }

        return false;
    }

    private static function jpeg(string $bytes): bool
    {
        $size = strlen($bytes);
        if (! str_starts_with($bytes, "\xff\xd8")) {
            return false;
        }
        $offset = 2;
        while ($offset < $size) {
            if ($bytes[$offset++] !== "\xff") {
                return false;
            }
            while ($offset < $size && $bytes[$offset] === "\xff") {
                $offset++;
            }
            if ($offset >= $size) {
                return false;
            }
            $marker = ord($bytes[$offset++]);
            if ($marker === 0xD9) {
                return $offset === $size;
            }
            if ($offset + 2 > $size) {
                return false;
            }
            $length = self::number('n', substr($bytes, $offset, 2));
            if ($length < 2 || $length > $size - $offset) {
                return false;
            }
            if (($marker >= 0xE0 && $marker <= 0xEF || $marker === 0xFE) && ! self::metadata(substr($bytes, $offset + 2, $length - 2))) {
                return false;
            }
            $offset += $length;
            if ($marker === 0xDA) {
                // Entropy-coded scans contain escaped FF bytes and restart markers.
                while ($offset < $size) {
                    if ($bytes[$offset] !== "\xff") {
                        $offset++;

                        continue;
                    }
                    $next = $offset + 1;
                    while ($next < $size && $bytes[$next] === "\xff") {
                        $next++;
                    }
                    if ($next >= $size) {
                        return false;
                    }
                    $code = ord($bytes[$next]);
                    if ($code === 0 || $code >= 0xD0 && $code <= 0xD7) {
                        $offset = $next + 1;

                        continue;
                    }
                    break;
                }
            }
        }

        return false;
    }

    private static function webp(string $bytes): bool
    {
        $size = strlen($bytes);
        if ($size < 12 || substr($bytes, 0, 4) !== 'RIFF' || substr($bytes, 8, 4) !== 'WEBP' || self::number('V', substr($bytes, 4, 4)) !== $size - 8) {
            return false;
        }
        $offset = 12;
        while ($offset + 8 <= $size) {
            $kind = substr($bytes, $offset, 4);
            $length = self::number('V', substr($bytes, $offset + 4, 4));
            if ($length < 0 || $length > $size - $offset - 8) {
                return false;
            }
            if (! in_array($kind, ['VP8 ', 'VP8L', 'VP8X', 'ALPH', 'ANIM', 'ANMF'], true) && ! self::metadata(substr($bytes, $offset + 8, $length))) {
                return false;
            }
            $offset += 8 + $length + $length % 2;
        }

        return $offset === $size;
    }
}
