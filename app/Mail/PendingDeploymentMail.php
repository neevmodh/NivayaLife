<?php

namespace App\Mail;

use App\Models\PendingDeployment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PendingDeploymentMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public PendingDeployment $deployment)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New commit pending deploy approval on Novix',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.pending-deployment',
            with: [
                'shortSha' => substr($this->deployment->commit_sha, 0, 7),
                'commitMessage' => $this->deployment->commit_message,
                'authorName' => $this->deployment->author_name,
                'reviewUrl' => route('admin.deployments.index'),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
