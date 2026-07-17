<?php

namespace App\Mail;

use App\Models\Vaccination;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VaccinationDueReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public bool $overdue;

    public function __construct(public Vaccination $vaccination)
    {
        $this->overdue = $vaccination->next_due_date->isPast();
    }

    public function envelope(): Envelope
    {
        $subject = $this->overdue
            ? $this->vaccination->vaccine_name.' vaccination is overdue'
            : $this->vaccination->vaccine_name.' vaccination is coming up';

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.vaccination-reminder',
            with: [
                'vaccineName' => $this->vaccination->vaccine_name,
                'doseNumber' => $this->vaccination->dose_number + 1,
                'dueDate' => $this->vaccination->next_due_date,
                'familyMemberName' => $this->vaccination->familyMember->full_name,
                'overdue' => $this->overdue,
                'manageUrl' => route('vaccinations.index', ['member' => $this->vaccination->family_member_id]),
            ],
        );
    }
}
