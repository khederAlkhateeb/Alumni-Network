<?php

namespace App\Mail;

use App\Models\Connection;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ConnectionAccepted extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Connection $connection,
        public readonly string $receiverName,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Connection Request Accepted',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.connections.accepted',
        );
    }
}
