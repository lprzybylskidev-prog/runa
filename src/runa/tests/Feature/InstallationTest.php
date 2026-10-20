<?php

use Illuminate\Mail\SentMessage;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage as SymfonySentMessage;
use Symfony\Component\Mime\Email;

beforeEach(function (): void {
    config(['mail.default' => 'smtp']);
});

it('rejects invalid verification recipients before contacting SMTP', function (): void {
    Mail::swap(new class
    {
        public function raw(): never
        {
            throw new LogicException('SMTP must not be contacted for invalid input.');
        }
    });
    expect(Artisan::call('runa:mail:test', ['recipient' => "bad\nrecipient"]))->toBe(2);
});

it('redacts SMTP provider exceptions in installation diagnostics', function (): void {
    Mail::swap(new class
    {
        public function raw(): never
        {
            throw new RuntimeException('synthetic_private_smtp_response');
        }
    });
    expect(Artisan::call('runa:mail:test', ['recipient' => 'verification@example.invalid']))->toBe(1)
        ->and(Artisan::output())->toContain('SMTP verification failed')->not->toContain('synthetic_private_smtp_response');
});

it('requires SMTP acceptance for a successful verification command', function (): void {
    $mailer = new class
    {
        public int $calls = 0;

        public function raw(): SentMessage
        {
            $this->calls++;
            $email = (new Email)->from('fixture@example.invalid')->to('verification@example.invalid')->text('Fixture');

            return new SentMessage(new SymfonySentMessage($email, Envelope::create($email)));
        }
    };
    Mail::swap($mailer);
    expect(Artisan::call('runa:mail:test', ['recipient' => 'verification@example.invalid']))->toBe(0)
        ->and(Artisan::output())->toContain('SMTP provider accepted')->and($mailer->calls)->toBe(1);
});

it('does not report delivery when an application listener cancels the verification message', function (): void {
    Mail::swap(new class
    {
        public function raw(): null
        {
            return null;
        }
    });
    expect(Artisan::call('runa:mail:test', ['recipient' => 'verification@example.invalid']))->toBe(1)
        ->and(Artisan::output())->toContain('cancelled')->not->toContain('provider accepted');
});
