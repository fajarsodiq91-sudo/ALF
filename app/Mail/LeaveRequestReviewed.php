<?php

namespace App\Mail;

use App\Models\LeaveRequest;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Tells an employee whether their leave request was approved or rejected. */
class LeaveRequestReviewed extends Mailable
{
    public function __construct(public LeaveRequest $leave) {}

    public function envelope(): Envelope
    {
        $outcome = $this->leave->status === 'approved' ? 'approved' : 'rejected';

        return new Envelope(subject: "Your leave request was {$outcome} | PT Alfajar Logic Futura");
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.leave-request-reviewed',
            with: ['approved' => $this->leave->status === 'approved'],
        );
    }
}
