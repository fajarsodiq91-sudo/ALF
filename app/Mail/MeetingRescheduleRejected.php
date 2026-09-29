<?php

namespace App\Mail;

use App\Models\MeetingRescheduleRequest;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class MeetingRescheduleRejected extends Mailable
{
    public function __construct(public MeetingRescheduleRequest $rescheduleRequest) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'About your reschedule request | PT Alfajar Logic Futura');
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.meeting-reschedule-rejected',
            with: [
                'meeting' => $this->rescheduleRequest->meeting()->with('session.program')->first(),
                'loginUrl' => route('portal.login'),
            ],
        );
    }
}
