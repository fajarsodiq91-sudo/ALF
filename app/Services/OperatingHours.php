<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Carbon;

/**
 * The company's meeting hours: which weekdays are open and which time slots exist on them.
 * Days use ISO numbers (1 = Monday … 7 = Sunday). Stored as JSON in the system settings.
 * Each slot is [start, end, corporateOnly]; a corporate-only slot is available only to
 * bookings for a program flagged "corporate training" (TrainingProgram::is_corporate).
 */
class OperatingHours
{
    private const SETTING = 'operating_hours';

    private const ENFORCED_SETTING = 'operating_hours_enforced';

    private const SATURDAY_SLOTS = [['09:00', '10:30', false], ['10:40', '12:10', false], ['13:00', '14:30', false], ['14:40', '16:00', false]];

    private const EVENING_SLOT = [['20:00', '21:30', false]];

    public const DAY_NAMES = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'];

    /**
     * @return array<int, list<array{0: string, 1: string, 2: bool}>> ISO weekday => list of [start, end, corporateOnly]
     */
    public static function defaults(): array
    {
        return [2 => self::EVENING_SLOT, 4 => self::EVENING_SLOT, 6 => self::SATURDAY_SLOTS, 7 => self::SATURDAY_SLOTS];
    }

    /**
     * The raw schedule, every slot included regardless of audience.
     *
     * @return array<int, list<array{0: string, 1: string, 2: bool}>>
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
     * @param  array<int|string, list<array{start: string, end: string, corporate?: mixed}|array{0: string, 1: string, 2?: mixed}>>  $days
     */
    public static function save(array $days, bool $enforced): void
    {
        Setting::put([
            self::SETTING => json_encode(self::normalise($days)),
            self::ENFORCED_SETTING => $enforced ? '1' : '0',
        ]);
    }

    /**
     * The slots open on the weekday of a date, for the given audience.
     *
     * @return list<array{0: string, 1: string, 2: bool}>
     */
    public static function slotsForDate(string $date, bool $corporate = false): array
    {
        $slots = self::schedule()[Carbon::parse($date)->dayOfWeekIso] ?? [];

        return self::filterSlots($slots, $corporate);
    }

    /**
     * @param  list<array{0: string, 1: string, 2: bool}>  $slots
     * @return list<array{0: string, 1: string, 2: bool}>
     */
    private static function filterSlots(array $slots, bool $corporate): array
    {
        return $corporate ? $slots : array_values(array_filter($slots, fn ($slot) => ! ($slot[2] ?? false)));
    }

    /** Minutes between the start times a customer can pick inside an operating window. */
    public const START_STEP_MINUTES = 30;

    public static function toMinutes(string $time): int
    {
        return (int) substr($time, 0, 2) * 60 + (int) substr($time, 3, 2);
    }

    /** The operating window of that date that fully contains the time range, if any. */
    public static function windowContaining(string $date, string $start, string $end, bool $corporate = false): ?array
    {
        foreach (self::slotsForDate($date, $corporate) as $slot) {
            if (self::toMinutes($slot[0]) <= self::toMinutes($start) && self::toMinutes($end) <= self::toMinutes($slot[1])) {
                return $slot;
            }
        }

        return null;
    }

    /**
     * Null when the meeting is allowed; otherwise a message explaining what to change.
     * With $sessionMinutes (the program's session length) the times are free inside a window;
     * without it the meeting must match a whole window. $corporate opens the corporate-only
     * slots as well as the public ones (pass the program's is_corporate flag).
     */
    public static function violation(string $date, ?string $start, ?string $end, ?int $sessionMinutes = null, bool $corporate = false): ?string
    {
        if (! self::enforced()) {
            return null;
        }

        $day = Carbon::parse($date)->dayOfWeekIso;
        $slots = self::slotsForDate($date, $corporate);

        if ($slots === []) {
            $open = collect(self::schedule())
                ->filter(fn ($daySlots) => self::filterSlots($daySlots, $corporate) !== [])
                ->keys()
                ->map(fn ($d) => self::DAY_NAMES[$d])
                ->implode(', ');

            return self::DAY_NAMES[$day].' is not an operating day. Choose '.($open ?: 'a day with operating hours').'.';
        }

        if (! $start || ! $end) {
            return 'Choose a time slot for '.self::DAY_NAMES[$day].'.';
        }

        if ($sessionMinutes) {
            $start = substr($start, 0, 5);
            $end = substr($end, 0, 5);
            $window = self::windowContaining($date, $start, $end, $corporate);

            if (! $window) {
                return 'The time must be inside the '.self::DAY_NAMES[$day].' operating hours: '.self::describe($slots).'.';
            }

            if (self::toMinutes($end) - self::toMinutes($start) !== $sessionMinutes) {
                return 'This program takes '.$sessionMinutes.' minutes per session.';
            }

            if ((self::toMinutes($start) - self::toMinutes($window[0])) % self::START_STEP_MINUTES !== 0) {
                return 'Start times are every '.self::START_STEP_MINUTES.' minutes from the opening time ('.$window[0].').';
            }

            return null;
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
     * $corporate includes the corporate-only slots as well as the public ones.
     *
     * @return array<string, string>
     */
    public static function formatted(bool $corporate = false): array
    {
        return collect(self::schedule())
            ->map(fn ($slots) => self::filterSlots($slots, $corporate))
            ->filter(fn ($slots) => $slots !== [])
            ->mapWithKeys(fn ($slots, $day) => [self::DAY_NAMES[$day] => self::describe($slots)])
            ->all();
    }

    /**
     * The schedule shaped for the browser: keyed by JS weekday (0 = Sunday). Every slot is
     * included, tagged with `corporate`; the slot-picker itself hides corporate-only slots
     * unless it was opened for a corporate booking.
     *
     * @return array{enforced: bool, days: array<int, list<array{value: string, start: string, end: string, label: string, corporate: bool}>>}
     */
    public static function forBrowser(): array
    {
        $days = [];

        foreach (self::schedule() as $isoDay => $slots) {
            $days[$isoDay % 7] = array_map(fn ($slot) => [
                'value' => $slot[0].'-'.$slot[1], 'start' => $slot[0], 'end' => $slot[1], 'label' => $slot[0].' – '.$slot[1],
                'corporate' => (bool) ($slot[2] ?? false),
            ], $slots);
        }

        return ['enforced' => self::enforced(), 'step' => self::START_STEP_MINUTES, 'days' => $days];
    }

    /**
     * The schedule for the weekly calendar: every ISO weekday 1-7 (closed days are empty) with start/end rows.
     * $corporate = false drops the corporate-only slots entirely, for a general audience display.
     *
     * @return array<int, list<array{start: string, end: string, corporate: bool}>>
     */
    public static function forWeekCalendar(?array $schedule = null, bool $corporate = true): array
    {
        $schedule ??= self::schedule();

        return collect(range(1, 7))->mapWithKeys(fn (int $day) => [
            $day => collect(self::filterSlots($schedule[$day] ?? [], $corporate))
                ->map(fn ($slot) => ['start' => $slot[0], 'end' => $slot[1], 'corporate' => (bool) ($slot[2] ?? false)])
                ->values()->all(),
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
     * Accepts [start, end] pairs or ['start' => .., 'end' => ..] rows (each optionally carrying
     * a 'corporate'/[2] flag), drops empty days, and sorts and merges slots by time — only
     * merging adjacent/overlapping slots that share the same corporate flag.
     *
     * @param  array<int|string, mixed>  $days
     * @return array<int, list<array{0: string, 1: string, 2: bool}>>
     */
    private static function normalise(array $days): array
    {
        $clean = [];

        foreach ($days as $day => $slots) {
            $rows = [];

            foreach (collect($slots)
                ->map(fn ($slot) => [
                    $slot['start'] ?? $slot[0],
                    $slot['end'] ?? $slot[1],
                    filter_var($slot['corporate'] ?? $slot[2] ?? false, FILTER_VALIDATE_BOOLEAN),
                ])
                ->sortBy(fn ($slot) => $slot[0])
                ->values() as $slot) {
                $last = count($rows) - 1;

                if ($last >= 0 && $slot[0] < $rows[$last][1] && $slot[2] === $rows[$last][2]) {
                    $rows[$last][1] = max($rows[$last][1], $slot[1]);
                } else {
                    $rows[] = $slot;
                }
            }

            if ($rows !== [] && (int) $day >= 1 && (int) $day <= 7) {
                $clean[(int) $day] = $rows;
            }
        }

        ksort($clean);

        return $clean;
    }
}
