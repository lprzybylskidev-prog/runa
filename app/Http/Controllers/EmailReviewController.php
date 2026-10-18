<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mime\Email;

final class EmailReviewController extends Controller
{
    public function __invoke(Request $request): Response
    {
        abort_unless(app()->environment('local', 'testing'), 404);
        $request->validate([
            'locale' => ['sometimes', 'in:pl,en'],
            'format' => ['sometimes', 'in:html,text,eml'],
            'long' => ['sometimes', 'in:0,1'],
            'images' => ['sometimes', 'in:cid,blocked,public'],
        ]);
        $locale = $request->string('locale', 'pl')->toString();
        $format = $request->string('format', 'html')->toString();
        $images = $request->string('images', 'cid')->toString();
        $mail = (new Mailable)->subject($locale === 'pl' ? 'Raport jest gotowy' : 'Report is ready')
            ->markdown('review-mail::sample', ['polish' => $locale === 'pl', 'long' => $request->string('long')->toString() === '1'])
            ->locale($locale)->to('preview@example.invalid');
        if ($images === 'public') {
            config(['branding.mail_logo_url' => 'https://example.invalid/runa-logo.png']);
        }
        // Explicit array transport: a GET preview must never deliver mail or depend on SMTP.
        $sent = Mail::mailer('array')->send($mail);
        if ($sent === null) {
            throw new \LogicException('Review message was cancelled.');
        }
        $email = $sent->getSymfonySentMessage()->getOriginalMessage();
        if (! $email instanceof Email) {
            throw new \LogicException('Expected a MIME email.');
        }
        $headers = ['Cache-Control' => 'no-store, private'];
        if ($format === 'eml') {
            return response($email->toString(), 200, $headers + [
                'Content-Type' => 'message/rfc822',
                'Content-Disposition' => 'attachment; filename="runa-sample-'.$locale.'.eml"',
            ]);
        }
        if ($format === 'text') {
            $text = $email->getTextBody();
            if (! is_string($text)) {
                throw new \LogicException('Expected rendered text.');
            }

            return response($text, 200, $headers + ['Content-Type' => 'text/plain; charset=UTF-8']);
        }
        $html = $email->getHtmlBody();
        if (! is_string($html)) {
            throw new \LogicException('Expected rendered HTML.');
        }
        foreach ($email->getAttachments() as $part) {
            $src = $images === 'blocked'
                ? '/__review/missing-logo.png'
                : 'data:'.$part->getContentType().';base64,'.base64_encode($part->getBody());
            $html = str_replace('cid:'.$part->getContentId(), $src, $html);
        }

        return response($html, 200, $headers + ['Content-Type' => 'text/html; charset=UTF-8']);
    }
}
