<?php

namespace Runa\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;
use Runa\Support\ConfigurationValidator;
use Throwable;

final class TestMail extends Command
{
    protected $signature = 'runa:mail:test {recipient}';

    protected $description = 'Submit an explicit verification message through configured SMTP';

    public function handle(ConfigurationValidator $validator): int
    {
        $recipient = $this->argument('recipient');
        if (filter_var($recipient, FILTER_VALIDATE_EMAIL) === false
            || config('mail.default') !== 'smtp' || config('mail.mailers.smtp.transport') !== 'smtp' || $validator->errors() !== []) {
            $this->error('Valid SMTP configuration and an explicit verification recipient are required.');

            return self::INVALID;
        }
        try {
            $sent = Mail::raw('Runa installation SMTP verification. No application data is included.', function (Message $message) use ($recipient): void {
                $message->to($recipient)->subject('Runa installation verification');
            });
            if ($sent === null) {
                $this->error('SMTP verification was cancelled before provider acceptance.');

                return self::FAILURE;
            }
        } catch (Throwable) {
            // Transport exceptions can contain credentials or provider response payloads.
            $this->error('SMTP verification failed. Check provider settings, TLS trust and recipient authorization.');

            return self::FAILURE;
        }
        $this->info('SMTP provider accepted the verification message; check the recipient mailbox for final delivery.');

        return self::SUCCESS;
    }
}
