<?php

namespace App\Console\Commands;

use App\Services\MeetingReminders;
use Illuminate\Console\Command;

class SendMeetingReminders extends Command
{
    protected $signature = 'meetings:send-reminders {type : day-before, hour-before or staff-summary}';

    protected $description = 'Email meeting reminders to customers (the day before, an hour before) or the daily summary to the staff';

    public function handle(): int
    {
        $count = match ($this->argument('type')) {
            'day-before' => MeetingReminders::sendDayBefore(),
            'hour-before' => MeetingReminders::sendHourBefore(),
            'staff-summary' => MeetingReminders::sendStaffSummary(),
            default => null,
        };

        if ($count === null) {
            $this->error('Type must be day-before, hour-before or staff-summary.');

            return self::INVALID;
        }

        $this->info("{$count} meeting(s) handled.");

        return self::SUCCESS;
    }
}
