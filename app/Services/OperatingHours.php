<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Carbon;

/**
 * The company's meeting hours: which weekdays are open and which time slots exist on them.
 * Days use ISO numbers (1 = Monday … 7 = Sunday). Stored as JSON in the system settings.
 */
class OperatingHours
{
    private const SETTING = 'operating_hours';

    private const ENFORCED_SETTING = 'operating_hours_enforced';

    private const SATURDAY_SLOTS = [['09:00', '10:30'], ['10:40', '12:10'], ['13:00', '14:30'], ['14:40', '16:00']];

    private const EVENING_SLOT = [['20:00', '21:30']];

    public const DAY_NAMES = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'];

    /**
     * @return array<int, list<array{0: string, 1: string}>> ISO weekday => list of [start, end]
     */
    public static function defaults(): array
    {
        return [2 => self::EVENING_SLOT, 4 => self::EVENING_SLOT, 6 => self::SATURDAY_SLOTS, 7 => self::SATURDAY_SLOTS];
    }

    /**
     * @return array<int, list<array{0: string, 1: string}>>
     */
    public static function schedule(): array
    {
        $stored = Setting::get(self::SETTING);
        $decoded = $stored ? json_decode($stored, true) : null;

        return is_array($decoded) ? self::normalise($decoded) : self::defaults();
    }

    /** When on (the default), meetings must sit exactly on one of the slots. */
    public static function enforced(): bool
    {
        return Setting::get(self::ENFORCED_SETTING, '1') === '1';
    }

    /**
     * @param  array<int|string, list<array{start: string, end: string}|array{0: string, 1: string}>>  $days
     */
    public static function save(array $days, bool $enforced): void
    {
        Setting::put([
            self::SETTING => json_encode(self::normalise($days)),
            self::ENFORCED_SETTING => $enforced ? '1' : '0',
        ]);
    }

    /**
     * @return list<array{0: string, 1: string}>
     */
    public static function slotsForDate(string $date): array
    {
        return self::schedule()[Carbon::parse($date)->dayOfWeekIso] ?? [];
    }

    /** Null when the meeting is allowed; otherwise a message explaining what to change. */
    public static function violation(string $date, ?string $start, ?string $end): ?string
    {
        if (! self::enforced()) {
            return null;
        }

        $day = Carbon::parse($date)->dayOfWeekIso;
        $slots = self::slotsForDate($date);

        if ($slots === []) {
            $open = collect(array_keys(self::schedule()))->map(fn ($d) => self::DAY_NAMES[$d])->implode(', ');

            return self::DAY_NAMES[$day].' is not an operating day. Choose '.($open ?: 'a day with operating hours').'.';
        }

        if (! $start || ! $end) {
            return 'Choose a time slot for '.self::DAY_NAMES[$day].'.';
        }

        foreach ($slots as [$slotStart, $slotEnd]) {
            if ($slotStart === substr($start, 0, 5) && $slotEnd === substr($end, 0, 5)) {
                return null;
            }
        }

        return 'The time must be one of the '.self::DAY_NAMES[$day].' slots: '.self::describe($slots).'.';
    }

    /**
     * Open days as "Tuesday" => "20:00 – 21:30", for display to customers and staff.
     *
     * @return array<string, string>
     */
    public static function formatted(): array
    {
        return collect(self::schedule())->mapWithKeys(fn ($slots, $day) => [self::DAY_NAMES[$day] => self::describe($slots)])->all();
    }

    /**
     * The schedule shaped for the browser: keyed by JS weekday (0 = Sunday).
     *
     * @return array{enforced: bool, days: array<int, list<array{value: string, start: string, end: string, label: string}>>}
     */
    public static function forBrowser(): array
    {
        $days = [];

        foreach (self::schedule() as $isoDay => $slots) {
            $days[$isoDay % 7] = array_map(fn ($slot) => [
                'value' => $slot[0].'-'.$slot[1], 'start' => $slot[0], 'end' => $slot[1], 'label' => $slot[0].' – '.$slot[1],
            ], $slots);
        }

        return ['enforced' => self::enforced(), 'days' => $days];
    }

    /**
     * The schedule for the weekly calendar: every ISO weekday 1-7 (closed days are empty) with start/end rows.
     *
     * @return array<int, list<array{start: string, end: string}>>
     */
    public static function forWeekCalendar(?array $schedule = null): array
    {
        $schedule ??= self::schedule();

        return collect(range(1, 7))->mapWithKeys(fn (int $day) => [
            $day => array_map(fn ($slot) => ['start' => $slot[0], 'end' => $slot[1]], $schedule[$day] ?? []),
        ])->all();
    }

    /**
     * @param  list<array{0: string, 1: string}>  $slots
     */
    private static function describe(array $slots): string
    {
        return implode(', ', array_map(fn ($slot) => $slot[0].' – '.$slot[1], $slots));
    }

    /**
     * Accepts [start, end] pairs or ['start' => .., 'end' => ..] rows, drops empty days and sorts slots by time.
     *
     * @param  array<int|string, mixed>  $days
     * @return array<int, list<array{0: string, 1: string}>>
     */
    private static function normalise(array $days): array
    {
        $clean = [];

        foreach ($days as $day => $slots) {
            $rows = collect($slots)
                ->map(fn ($slot) => isset($slot['start']) ? [$slot['start'], $slot['end']] : [$slot[0], $slot[1]])
                ->sortBy(fn ($slot) => $slot[0])
                ->values()
                ->all();

            if ($rows !== [] && (int) $day >= 1 && (int) $day <= 7) {
                $clean[(int) $day] = $rows;
            }
        }

        ksort($clean);

        return $clean;
    }
}
