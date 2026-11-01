<?php

namespace App\Mail;

use App\Models\TrainingSessionMeeting;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Collection;

/** The daily rundown, for the owner and staff, of the meetings happening today and tomorrow. */
class StaffMeetingSummary extends Mailable
{
    /**
     * @param  Collection<int, TrainingSessionMeeting>  $today
     * @param  Collection<int, TrainingSessionMeeting>  $tomorrow
     */
    public function __construct(public Collection $today, public Collection $tomorrow) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Meetings today: '.$this->today->count().' | PT Alfajar Logic Futura');
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.staff-meeting-summary', with: ['scheduleUrl' => route('training.index')]);
    }
}
