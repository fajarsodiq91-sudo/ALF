<?php

namespace App\Mail;

use App\Models\Employee;
use App\Models\TrainingSessionMeeting;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Reminds the session's trainer of a meeting they teach, the day before or an hour before it starts. */
class TrainerMeetingReminder extends Mailable
{
    public function __construct(public Employee $trainer, public TrainingSessionMeeting $meeting, public bool $hourBefore) {}

    public function envelope(): Envelope
    {
        $subject = $this->hourBefore ? 'You teach in 1 hour' : 'Reminder: you teach tomorrow';

        return new Envelope(subject: $subject.' | PT Alfajar Logic Futura');
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.trainer-meeting-reminder', with: ['loginUrl' => route('login')]);
    }
}
