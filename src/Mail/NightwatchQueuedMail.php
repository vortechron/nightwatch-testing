<?php

namespace Vortechron\NightwatchTesting\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NightwatchQueuedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $message = 'Nightwatch queued test email'
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nightwatch Queued Test Mail',
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: '<h1>Nightwatch Queued Test</h1><p>'.e($this->message).'</p>',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
