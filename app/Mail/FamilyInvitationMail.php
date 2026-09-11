<?php

namespace App\Mail;

use App\Models\FamilyInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FamilyInvitationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public FamilyInvitation $invitation)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->invitation->primaryAccount->name.' invited you to their family on NivayaLife',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.family-invitation',
            with: [
                'inviterName' => $this->invitation->primaryAccount->name,
                'inviteeName' => $this->invitation->familyMember->full_name,
                'relation' => $this->invitation->familyMember->relation,
                'acceptUrl' => route('invite.show', $this->invitation->token),
                'expiresAt' => $this->invitation->expires_at,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
