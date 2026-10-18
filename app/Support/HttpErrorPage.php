<?php

namespace App\Support;

use Illuminate\Http\Request;

final class HttpErrorPage
{
    public const CODES = [403 => 'FORBIDDEN', 404 => 'NOT_FOUND', 419 => 'PAGE_EXPIRED',
        429 => 'TOO_MANY_REQUESTS', 500 => 'INTERNAL_SERVER_ERROR', 503 => 'SERVICE_UNAVAILABLE'];

    /** @return array<string, mixed> */
    public function forRequest(int $status, Request $request): array
    {
        $lang = $request->query('lang');
        $locale = in_array($lang, ['pl', 'en'], true) ? $lang : ($request->getPreferredLanguage(['pl', 'en']) ?? 'pl');
        $theme = $request->query('theme');
        $name = config('branding.application_name');
        $version = config('branding.application_version');
        $platform = config('branding.platform_version');
        $id = $request->attributes->get('request_id');

        return $this->data($status, $locale, in_array($theme, ['light', 'dark'], true) ? $theme : 'system',
            is_string($name) ? $name : 'Runa', is_string($version) ? $version : '',
            is_string($platform) ? $platform : '', is_string($id) ? $id : '');
    }

    /** @return array<string, mixed> */
    public function data(int $status, string $locale, string $theme, string $name, string $version, string $platformVersion, string $requestId = ''): array
    {
        if (! isset(self::CODES[$status]) || ! in_array($locale, ['pl', 'en'], true)) {
            throw new \InvalidArgumentException('Unsupported HTTP error page.');
        }
        $source = file_get_contents(dirname(__DIR__, 2).'/resources/errors/messages.json');
        if ($source === false) {
            throw new \RuntimeException('HTTP error messages are missing.');
        }
        $messages = json_decode($source, true, flags: JSON_THROW_ON_ERROR);
        if (! is_array($messages) || ! isset($messages[$locale]) || ! is_array($messages[$locale])) {
            throw new \RuntimeException('Invalid HTTP error messages.');
        }

        return compact('status', 'locale', 'theme', 'name', 'version', 'platformVersion', 'requestId')
            + ['copy' => $messages[$locale], 'errorCode' => self::CODES[$status]];
    }
}
