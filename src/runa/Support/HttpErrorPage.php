<?php

namespace Runa\Support;

use Illuminate\Http\Request;

final class HttpErrorPage
{
    public const STATUSES = [403, 404, 419, 429, 500, 503];

    /** @return array<string, mixed> */
    public function forRequest(int $status, Request $request): array
    {
        $lang = $request->query('lang');
        $locale = in_array($lang, ['pl', 'en'], true) ? $lang : ($request->getPreferredLanguage(['pl', 'en']) ?? 'pl');
        $theme = $request->query('theme');
        $identity = app(ApplicationIdentity::class);
        $id = $request->attributes->get('request_id');

        return $this->data($status, $locale, in_array($theme, ['light', 'dark'], true) ? $theme : 'system',
            $identity->applicationName, $identity->applicationVersion,
            $identity->platformVersion, is_string($id) ? $id : '');
    }

    /** @return array<string, mixed> */
    public function data(int $status, string $locale, string $theme, string $name, string $version, string $platformVersion, string $requestId = ''): array
    {
        if (! in_array($status, self::STATUSES, true) || ! in_array($locale, ['pl', 'en'], true) || ! in_array($theme, ['system', 'light', 'dark'], true)) {
            throw new \InvalidArgumentException('Unsupported HTTP error page.');
        }
        $source = file_get_contents(base_path('runa/resources/errors/messages.json'));
        if ($source === false) {
            throw new \RuntimeException('HTTP error messages are missing.');
        }
        $messages = json_decode($source, true, flags: JSON_THROW_ON_ERROR);
        if (! is_array($messages) || ! isset($messages[$locale]) || ! is_array($messages[$locale])) {
            throw new \RuntimeException('Invalid HTTP error messages.');
        }

        return compact('status', 'locale', 'theme', 'name', 'version', 'platformVersion', 'requestId')
            + ['copy' => $messages[$locale]];
    }
}
