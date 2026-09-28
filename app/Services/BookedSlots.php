<?php

namespace App\Services;

use App\Models\BlockedSlot;
use App\Models\Customer;
use App\Models\TrainingSessionMeeting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Which meeting times are already taken. A time is taken by a scheduled meeting of any session that is
 * not cancelled, and by the preferred dates of customers whose registration still waits for approval
 * (so two customers cannot pick the same slot before the company has reviewed them), and by slots the
 * company blocked itself in Master Data.
 */
class BookedSlots
{
    /** How far ahead the calendars can show bookings. */
    public const WINDOW_DAYS = 200;

    /**
     * Keys like "2026-10-06|20:00-21:30" for the calendars, so they can paint taken slots red.
     *
     * @param  int|null  $exceptCustomerId  a pending customer whose own request must not block them
     * @return list<string>
     */
    public static function keys(?int $exceptCustomerId = null): array
    {
        return self::all($exceptCustomerId)
            ->filter(fn (array $slot) => $slot['date'] >= today()->toDateString() && $slot['date'] <= today()->addDays(self::WINDOW_DAYS)->toDateString())
            ->map(fn (array $slot) => $slot['date'].'|'.$slot['start'].'-'.$slot['end'])
            ->unique()
            ->values()
            ->all();
    }

    /** Whether the given time overlaps something already booked. Times are "HH:MM"; unknown times cannot clash. */
    public static function conflicts(string $date, ?string $start, ?string $end, ?int $exceptCustomerId = null): bool
    {
        if (! $start || ! $end) {
            return false;
        }

        $date = Carbon::parse($date)->toDateString();
        [$start, $end] = [substr($start, 0, 5), substr($end, 0, 5)];

        return self::all($exceptCustomerId)->contains(
            fn (array $slot) => $slot['date'] === $date && $slot['start'] < $end && $slot['end'] > $start
        );
    }

    /** "Tue, 06 Oct 2026, 20:00 – 21:30" for messages. */
    public static function describe(string $date, string $start, string $end): string
    {
        return Carbon::parse($date)->format('D, d M Y').', '.substr($start, 0, 5).' – '.substr($end, 0, 5);
    }

    /**
     * @return Collection<int, array{date: string, start: string, end: string}>
     */
    private static function all(?int $exceptCustomerId): Collection
    {
        $scheduled = TrainingSessionMeeting::query()
            ->whereNotNull('start_time')
            ->whereNotNull('end_time')
            ->whereHas('session', fn ($query) => $query->where('status', '!=', 'cancelled'))
            ->get(['meeting_date', 'start_time', 'end_time'])
            ->map(fn ($meeting) => [
                'date' => $meeting->meeting_date->toDateString(),
                'start' => substr($meeting->start_time, 0, 5),
                'end' => substr($meeting->end_time, 0, 5),
            ]);

        $requested = Customer::query()
            ->where('registration_status', Customer::REGISTRATION_PENDING_APPROVAL)
            ->when($exceptCustomerId, fn ($query, $id) => $query->whereKeyNot($id))
            ->get(['id', 'requested_programs'])
            ->flatMap(fn (Customer $customer) => collect($customer->requested_programs ?? [])->flatMap(fn ($program) => $program['meetings'] ?? []))
            ->filter(fn ($meeting) => ! empty($meeting['start_time']) && ! empty($meeting['end_time']))
            ->map(fn ($meeting) => [
                'date' => Carbon::parse($meeting['meeting_date'])->toDateString(),
                'start' => substr($meeting['start_time'], 0, 5),
                'end' => substr($meeting['end_time'], 0, 5),
            ]);

        $blocked = BlockedSlot::query()->get()->map(fn (BlockedSlot $slot) => [
            'date' => $slot->date->toDateString(),
            'start' => $slot->start_time,
            'end' => $slot->end_time,
        ]);

        return $scheduled->concat($requested)->concat($blocked);
    }
}
