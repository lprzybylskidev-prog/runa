<?php

namespace Runa\Modules\Auth\Mail;

use Illuminate\Mail\Mailable;

/** Fixed Auth copy; render only at the private synchronous boundary, never queue or log. */
final class AuthMessage extends Mailable
{
    public function __construct(
        public string $mailTitle,
        public string $mailBody,
        public ?string $actionLabel = null,
        public ?string $actionUrl = null,
        public ?string $code = null,
        public string $language = 'pl',
    ) {
        $this->locale($language);
    }

    public function build(): static
    {
        return $this->subject($this->mailTitle)->markdown('auth.mail.message');
    }
}
