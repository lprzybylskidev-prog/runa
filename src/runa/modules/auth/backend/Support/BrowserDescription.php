<?php

namespace Runa\Modules\Auth\Support;

/** Presentation hints only. Never fingerprint, authenticate or authorize a browser. */
final class BrowserDescription
{
    /** @return array{browser: ?string, platform: ?string} */
    public static function fromUserAgent(?string $userAgent): array
    {
        $agent = substr($userAgent ?? '', 0, 2048);
        $browser = self::match($agent, [
            'Edg/' => 'Edge', 'EdgA/' => 'Edge', 'EdgiOS/' => 'Edge',
            'OPR/' => 'Opera', 'OPiOS/' => 'Opera', 'SamsungBrowser/' => 'Samsung Internet',
            'Firefox/' => 'Firefox', 'FxiOS/' => 'Firefox',
            'Chrome/' => 'Chrome', 'CriOS/' => 'Chrome',
            'Version/' => str_contains($agent, 'Safari/') ? 'Safari' : null,
        ]);
        $platform = self::match($agent, [
            'iPhone' => 'iOS', 'iPad' => 'iPadOS', 'iPod' => 'iOS',
            'Android' => 'Android', 'Windows NT' => 'Windows', 'CrOS' => 'ChromeOS',
            'Macintosh' => 'macOS', 'Mac OS X' => 'macOS', 'Linux' => 'Linux',
        ]);

        return ['browser' => $browser, 'platform' => $platform];
    }

    /** @param array<string, ?string> $labels */
    private static function match(string $agent, array $labels): ?string
    {
        foreach ($labels as $token => $label) {
            if (str_contains($agent, $token)) {
                return $label;
            }
        }

        return null;
    }
}
