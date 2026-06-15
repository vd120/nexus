<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InactiveUserReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user, public int $days) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('emails.inactive_subject', ['app' => config('app.name')]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.inactive-reminder',
            text: 'emails.inactive-reminder-text',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
