<?php

namespace App\Mail;

use App\Models\Employee;
use App\Models\ProjectTask;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ProjectTaskNotification extends Mailable
{
    public const ASSIGNED = 'assigned';

    public const UPDATED = 'updated';

    public const UNASSIGNED = 'unassigned';

    private const HEADLINES = [
        self::ASSIGNED => 'A task has been assigned to you',
        self::UPDATED => 'A task assigned to you was updated',
        self::UNASSIGNED => 'You were removed from a task',
    ];

    /** @param  array<string, array{0: string, 1: string}>  $changes  field label => [before, after] */
    public function __construct(
        public ProjectTask $task,
        public Employee $recipient,
        public string $type,
        public array $changes = [],
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: self::HEADLINES[$this->type].': '.$this->task->title.' | PT Alfajar Logic Futura');
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.project-task',
            with: [
                'headline' => self::HEADLINES[$this->type],
                'project' => $this->task->project,
                'taskUrl' => route('projects.show', $this->task->project_id),
            ],
        );
    }
}
