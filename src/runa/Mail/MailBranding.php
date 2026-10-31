<?php

namespace Runa\Mail;

use Illuminate\Mail\Events\MessageSending;
use Runa\Identity\ApplicationIdentity;
use Runa\Support\Settings\ApplicationPresentation;
use Symfony\Component\Mime\Part\DataPart;

final class MailBranding
{
    private const LOGO_CID = 'runa-brand-logo@runa';

    public function name(): string
    {
        return app(ApplicationPresentation::class)->read()['name'];
    }

    public function logo(): ?string
    {
        $url = config('branding.mail_logo_url');
        $selected = app(ApplicationPresentation::class)->read()['logo'];
        $source = app(ApplicationIdentity::class)->logo;
        if ($selected !== $source && $url === ($source['src'] ?? null)) {
            $url = null;
        }
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
        $presentation = app(ApplicationPresentation::class)->read();
        $identity = app(ApplicationIdentity::class);
        $src = $presentation['logo']['src'] ?? null;
        if ($path === base_path('runa/resources/images/runa-mail-logo.png') && is_string($src) && $presentation['logo'] !== $identity->logo) {
            $candidate = public_path(ltrim($src, '/'));
            $resolved = realpath($candidate);
            $public = realpath(public_path());
            // Source-catalogued assets still cannot escape the served public tree.
            if ($resolved !== false && $public !== false && str_starts_with($resolved, $public.'/') && is_readable($resolved)
                && in_array((new \finfo(FILEINFO_MIME_TYPE))->file($resolved), ['image/png', 'image/jpeg'], true)) {
                $path = $resolved;
            }
        }
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
