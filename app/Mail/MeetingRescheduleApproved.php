<?php

namespace App\Mail;

use App\Models\MeetingRescheduleRequest;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class MeetingRescheduleApproved extends Mailable
{
    /** @param  string  $originalLabel  the meeting's schedule before it was moved, e.g. "Tue, 06 Oct 2026 · 20:00 – 21:30" */
    public function __construct(public MeetingRescheduleRequest $rescheduleRequest, public string $originalLabel) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your reschedule request is approved | PT Alfajar Logic Futura');
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.meeting-reschedule-approved',
            with: [
                'meeting' => $this->rescheduleRequest->meeting()->with('session.program')->first(),
                'originalLabel' => $this->originalLabel,
                'loginUrl' => route('portal.login'),
            ],
        );
    }
}
