<?php

namespace App\Support;

use Illuminate\Mail\Events\MessageSending;
use Symfony\Component\Mime\Part\DataPart;

final class MailBranding
{
    private const LOGO_CID = 'runa-brand-logo@runa';

    public function name(): string
    {
        $name = config('branding.application_name');

        return is_string($name) && trim($name) !== '' ? $name : 'Runa';
    }

    public function logo(): ?string
    {
        $url = config('branding.mail_logo_url');
        if (is_string($url) && $url !== '') {
            $parts = parse_url($url);

            return filter_var($url, FILTER_VALIDATE_URL) && is_array($parts)
                && ($parts['scheme'] ?? '') === 'https'
                && ! isset($parts['user']) && ! isset($parts['pass']) ? $url : null;
        }

        return $this->logoPath() !== null ? 'cid:'.self::LOGO_CID : null;
    }

    // Anonymous Markdown components do not inherit Laravel's $message variable.
    // Attach only when the rendered HTML actually references the default CID.
    public function attachLogo(MessageSending $event): void
    {
        $html = $event->message->getHtmlBody();
        if (! is_string($html) || ! str_contains($html, 'cid:'.self::LOGO_CID)) {
            return;
        }
        $path = $this->logoPath();
        if ($path !== null) {
            $event->message->addPart(DataPart::fromPath($path)->asInline()->setContentId(self::LOGO_CID));
        }
    }

    private function logoPath(): ?string
    {
        $path = config('branding.mail_logo_path');
        if (! is_string($path) || ! is_file($path) || ! is_readable($path)) {
            return null;
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($path);

        return in_array($mime, ['image/png', 'image/jpeg'], true) ? $path : null;
    }

    public function poweredBy(): string
    {
        return app()->getLocale() === 'pl' ? 'Oparte na Runa' : 'Powered by Runa';
    }
}
