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

it('validates both installer modes and forwards bounded host options', function (): void {
    foreach (['dev', 'prod', 'production'] as $mode) {
        expect(Artisan::call('runa:install', ['mode' => $mode, 'target' => str_repeat('a', 40), '--plan' => true]))->toBe(0);
        $plan = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);
        expect($plan['operation'])->toBe('tooling.install')->and($plan['parameters']['arguments'])->toBe([$mode, str_repeat('a', 40)]);
    }
    expect(Artisan::call('runa:install', ['mode' => 'dev', 'target' => str_repeat('a', 40), '--simulation' => true, '--plan' => true]))->toBe(2);
    expect(Artisan::call('runa:install', ['mode' => 'prod', 'target' => str_repeat('a', 40), '--profile' => 'low', '--plan' => true]))->toBe(2);
});

it('rejects invalid verification recipients before contacting SMTP', function (): void {
    Mail::swap(new class
    {
        public function raw(): never
        {
            throw new LogicException('SMTP must not be contacted for invalid input.');
        }
    });
    expect(Artisan::call('runa:install:verify-mail', ['recipient' => "bad\nrecipient"]))->toBe(2);
});

it('redacts SMTP provider exceptions in installation diagnostics', function (): void {
    Mail::swap(new class
    {
        public function raw(): never
        {
            throw new RuntimeException('synthetic_private_smtp_response');
        }
    });
    expect(Artisan::call('runa:install:verify-mail', ['recipient' => 'verification@example.invalid']))->toBe(1)
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
    expect(Artisan::call('runa:install:verify-mail', ['recipient' => 'verification@example.invalid']))->toBe(0)
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
    expect(Artisan::call('runa:install:verify-mail', ['recipient' => 'verification@example.invalid']))->toBe(1)
        ->and(Artisan::output())->toContain('cancelled')->not->toContain('provider accepted');
});
