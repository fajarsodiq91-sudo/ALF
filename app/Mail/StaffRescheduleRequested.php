<?php

namespace App\Mail;

use App\Models\MeetingRescheduleRequest;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Tells the owner and staff that a customer asked to move one of their meetings. */
class StaffRescheduleRequested extends Mailable
{
    public function __construct(public MeetingRescheduleRequest $rescheduleRequest) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Reschedule request from {$this->rescheduleRequest->customer->name} | PT Alfajar Logic Futura");
    }

    public function content(): Content
    {
        $meeting = $this->rescheduleRequest->meeting()->with('session.program')->first();

        return new Content(
            markdown: 'emails.staff-reschedule-requested',
            with: [
                'meeting' => $meeting,
                'reviewUrl' => route('training.edit', $meeting->training_session_id),
            ],
        );
    }
}
