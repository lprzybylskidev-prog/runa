<?php

use Illuminate\Mail\Mailable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification as Notifications;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Symfony\Component\Mime\Email;
use Tests\Fixtures\BrowserMailDocument;

beforeEach(function () {
    View::addNamespace('mail-fixture', base_path('../tests/backend/runa/Fixtures/mail'));
});

function sendLayoutFixture(?string $text = null): Email
{
    $mail = (new Mailable)->subject('Layout fixture')->markdown('mail-fixture::sample');
    if ($text !== null) {
        $mail->text($text);
    }
    $sent = Mail::mailer('array')->to('fixture@example.invalid')->send($mail);

    return $sent->getSymfonySentMessage()->getOriginalMessage();
}

it('sends real multipart Markdown mail with inline branding and automatic text', function (string $locale, string $poweredBy) {
    app()->setLocale($locale);
    config(['branding.application_name' => 'Atlas', 'branding.application_version' => '2.4.1']);
    $email = sendLayoutFixture();
    $html = $email->getHtmlBody();
    $text = $email->getTextBody();
    expect($html)->toContain('Atlas', 'v2.4.1', $poweredBy, '#8650ba', 'lang="'.$locale.'"');
    expect($text)->toContain('Atlas', $poweredBy, 'Open report', 'https://example.invalid/report')
        ->not->toContain('<table', 'cid:', '<img');
    expect($email->getAttachments())->toHaveCount(1);
    $part = $email->getAttachments()[0];
    expect($part->getDisposition())->toBe('inline');
    expect($part->getContentType())->toBe('image/png');
    expect($html)->toContain('cid:'.$part->getContentId());
    expect($email->toString())->toContain('multipart/alternative', 'multipart/related', 'Content-ID: <'.$part->getContentId().'>');
})->with([['pl', 'Oparte na Runa'], ['en', 'Powered by Runa']]);

it('brands standard non Auth notifications without opting into a custom engine', function () {
    $notification = new class extends Notification
    {
        public function via(object $notifiable): array
        {
            return ['mail'];
        }

        public function toMail(object $notifiable): MailMessage
        {
            return (new MailMessage)->subject('Report ready')->line('The report is ready.')
                ->action('Open report', 'https://example.invalid/report');
        }
    };
    Notifications::route('mail', 'fixture@example.invalid')->notify($notification);
    $email = Mail::mailer('array')->getSymfonyTransport()->messages()->last()->getOriginalMessage();
    expect($email->getHtmlBody())->toContain('Powered by Runa', '#8650ba', 'The report is ready.', 'cid:');
    expect($email->getTextBody())->toContain('Powered by Runa', 'Open report');
});

it('supports explicit text and safe names when images are missing', function () {
    config(['branding.mail_logo_path' => '/missing/logo.png', 'branding.application_name' => '<script>Atlas</script>']);
    $email = sendLayoutFixture('mail-fixture::explicit-text');
    expect($email->getTextBody())->toContain('Explicit plain text');
    expect($email->getHtmlBody())->toContain('&lt;script&gt;Atlas&lt;/script&gt;')->not->toContain('<script>');
    expect($email->getAttachments())->toBeEmpty();
});

it('supports public HTTPS branding without fetching or embedding it', function () {
    config(['branding.mail_logo_url' => 'https://cdn.example.invalid/logo.png']);
    $email = sendLayoutFixture();
    expect($email->getHtmlBody())->toContain('src="https://cdn.example.invalid/logo.png"', 'alt="Runa"');
    expect($email->getAttachments())->toBeEmpty();
});

it('rejects unsafe public image URLs and retains readable identity', function (string $url) {
    config(['branding.mail_logo_url' => $url]);
    $email = sendLayoutFixture();
    expect($email->getHtmlBody())->toContain('Runa')->not->toContain('<img');
    expect($email->getAttachments())->toBeEmpty();
})->with(['http://example.invalid/logo.png', 'https://user:pass@example.invalid/logo.png', 'javascript:alert(1)', '//example.invalid/logo.png']);

it('allows namespaced region overrides and footer visibility', function () {
    View::prependNamespace('runa-mail', base_path('../tests/backend/runa/Fixtures/mail/overrides'));
    config(['branding.mail_footer.visible' => false]);
    $email = sendLayoutFixture();
    expect($email->getHtmlBody())->toContain('Custom header')->not->toContain('Powered by Runa');
    expect($email->getTextBody())->not->toContain('Powered by Runa');
});

it('uses explicit text for custom HTML without creating unrelated CID parts', function () {
    $mail = (new Mailable)->subject('Custom HTML')->view('mail-fixture::custom-html')
        ->text('mail-fixture::explicit-text');
    $email = Mail::mailer('array')->to('fixture@example.invalid')->send($mail)
        ->getSymfonySentMessage()->getOriginalMessage();
    expect($email->getHtmlBody())->toContain('Custom HTML body');
    expect($email->getTextBody())->toContain('Explicit plain text');
    expect($email->getAttachments())->toBeEmpty();
    expect($email->toString())->toContain('multipart/alternative');
});

it('configures independent footer elements and platform version', function () {
    config(['branding.application_name' => 'Atlas', 'branding.application_version' => '2.4.1',
        'branding.platform_version' => '0.9.3', 'branding.mail_footer.application' => false]);
    $email = sendLayoutFixture();
    expect($email->getHtmlBody())->toContain('Powered by Runa v0.9.3')->not->toContain('v2.4.1');
    expect($email->getTextBody())->toContain('Powered by Runa v0.9.3')->not->toContain('v2.4.1');
    config(['branding.mail_footer.application' => true, 'branding.mail_footer.platform' => false]);
    $email = sendLayoutFixture();
    expect($email->getHtmlBody())->toContain('Atlas v2.4.1')->not->toContain('Powered by Runa');
    expect($email->getTextBody())->toContain('Atlas v2.4.1')->not->toContain('Powered by Runa');
});

it('renders isolated HTML text and MIME browser fixtures without SMTP', function () {
    View::addNamespace('browser-mail', base_path('../tests/backend/runa/Fixtures/browser-mail'));
    Route::get('/test-mail-document', BrowserMailDocument::class)->name('test.mail.document');
    $this->get('/test-mail-document?locale=pl')->assertOk()->assertSee('Raport jest gotowy')->assertSee('data:image/png;base64,', false);
    $this->get('/test-mail-document?locale=en&long=1&images=blocked')->assertOk()->assertSee('Summary 12')->assertSee('/__fixtures/missing-logo.png', false);
    $this->get('/test-mail-document?locale=en&format=text')->assertOk()->assertHeader('Content-Type', 'text/plain; charset=UTF-8')->assertSee('Powered by Runa');
    $this->get('/test-mail-document?format=eml')->assertOk()->assertHeader('Content-Type', 'message/rfc822')->assertSee('multipart/alternative', false);
    $this->getJson('/test-mail-document?locale=fr')->assertUnprocessable();
});
