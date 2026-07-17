<?php

namespace App\Mail;

use App\Models\MedicationLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MedicationDoseReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public MedicationLog $log)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Time to take '.$this->log->medication->medicine_name,
        );
    }

    public function content(): Content
    {
        $medication = $this->log->medication;

        return new Content(
            view: 'emails.medication-reminder',
            with: [
                'medicineName' => $medication->medicine_name,
                'dosage' => $medication->dosage,
                'time' => $this->log->scheduled_at->format('g:i A'),
                'familyMemberName' => $medication->familyMember->full_name,
                'dashboardUrl' => route('dashboard'),
            ],
        );
    }
}
