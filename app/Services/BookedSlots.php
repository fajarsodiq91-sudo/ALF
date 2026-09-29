<?php

namespace App\Services;

use App\Models\BlockedSlot;
use App\Models\Customer;
use App\Models\TrainingProgram;
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
     * The same bookings as {@see keys()}, grouped by that key, with the context a calendar tooltip
     * or details popup needs: who booked it (a scheduled meeting or a pending registration request)
     * or why the company blocked it.
     *
     * @return array<string, list<array<string, mixed>>>
     */
    public static function details(): array
    {
        $scheduled = TrainingSessionMeeting::query()
            ->whereNotNull('start_time')
            ->whereNotNull('end_time')
            ->whereHas('session', fn ($query) => $query->where('status', '!=', 'cancelled'))
            ->with(['session.program', 'session.customer', 'session.instructor'])
            ->get()
            ->map(function (TrainingSessionMeeting $meeting) {
                $session = $meeting->session;

                return [
                    'key' => $meeting->meeting_date->toDateString().'|'.substr($meeting->start_time, 0, 5).'-'.substr($meeting->end_time, 0, 5),
                    'type' => 'meeting',
                    'title' => $session->program?->name ?? 'Training session',
                    'customer' => $session->customer?->name,
                    'instructor' => $session->instructor?->name,
                    'location' => $meeting->location ?: $session->location,
                    'topic' => $meeting->topic,
                    'session_id' => $session->id,
                ];
            });

        $pendingCustomers = Customer::query()
            ->where('registration_status', Customer::REGISTRATION_PENDING_APPROVAL)
            ->get(['id', 'name', 'requested_programs']);

        $programNames = TrainingProgram::query()
            ->whereIn('id', $pendingCustomers
                ->flatMap(fn (Customer $customer) => collect($customer->requested_programs ?? [])->pluck('training_program_id'))
                ->unique())
            ->pluck('name', 'id');

        $requested = $pendingCustomers->flatMap(fn (Customer $customer) => collect($customer->requested_programs ?? [])
            ->flatMap(fn ($program) => collect($program['meetings'] ?? [])
                ->filter(fn ($meeting) => ! empty($meeting['start_time']) && ! empty($meeting['end_time']))
                ->map(fn ($meeting) => [
                    'key' => Carbon::parse($meeting['meeting_date'])->toDateString().'|'.substr($meeting['start_time'], 0, 5).'-'.substr($meeting['end_time'], 0, 5),
                    'type' => 'pending',
                    'title' => 'Pending registration',
                    'customer' => $customer->name,
                    'program' => $programNames[$program['training_program_id']] ?? null,
                ])));

        $blocked = BlockedSlot::query()->get()->map(fn (BlockedSlot $slot) => [
            'key' => $slot->key(),
            'type' => 'blocked',
            'title' => 'Blocked by the company',
            'reason' => $slot->reason,
        ]);

        return $scheduled->concat($requested)->concat($blocked)
            ->groupBy('key')
            ->map(fn (Collection $entries) => $entries->map(fn (array $entry) => collect($entry)->except('key')->all())->values()->all())
            ->all();
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
