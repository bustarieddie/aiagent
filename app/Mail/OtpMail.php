<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable {
    use Queueable, SerializesModels;

    public function __construct(public string $code, public int $ttlMinutes = 10) {}

    public function envelope(): Envelope {
        return new Envelope(
            subject: 'Klinik Bustari Admin — Login Code: ' . $this->code,
        );
    }

    public function content(): Content {
        return new Content(
            view: 'mail.otp',
            with: [
                'code' => $this->code,
                'ttlMinutes' => $this->ttlMinutes,
            ],
        );
    }
}
