<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\TapDevice;
use App\Models\TrainingMeetingAttendance;
use App\Models\TrainingSessionMeeting;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Turns one card tap (RFID UID) or ID-card QR scan into attendance:
 * employees clock in/out, customers are marked present at today's training meeting.
 */
class TapRecorder
{
    /** Taps closer together than this are the same tap (a card held on the reader). */
    private const DEBOUNCE_MINUTES = 2;

    /** A training meeting opens this long before it starts. */
    private const OPENS_BEFORE_MINUTES = 60;

    /** Assumed length of a meeting that has no end time. */
    private const DEFAULT_MEETING_HOURS = 4;

    /** @return array{ok: bool, status: string, message: string, name?: string, role?: string} */
    public static function record(?string $uid, ?string $qr, ?TapDevice $device = null): array
    {
        [$person, $method] = self::resolve($uid, $qr);

        if (! $person) {
            return self::fail('unknown_card', 'Kartu tidak dikenal.');
        }

        return $person instanceof Employee
            ? self::employee($person)
            : self::customer($person, $method, $device);
    }

    /** @return array{0: ?Model, 1: string} */
    private static function resolve(?string $uid, ?string $qr): array
    {
        if ($qr !== null && trim($qr) !== '') {
            // The card QR holds a link ending in the token; accept the bare token too.
            $token = basename(parse_url(trim($qr), PHP_URL_PATH) ?: trim($qr));

            return [Employee::where('id_card_token', $token)->first() ?? Customer::where('id_card_token', $token)->first(), 'qr'];
        }

        $uid = Rfid::normalize($uid);

        return [$uid ? Rfid::owner($uid) : null, 'rfid'];
    }

    private static function employee(Employee $employee): array
    {
        if ($employee->status !== 'active') {
            return self::fail('not_allowed', 'Karyawan tidak aktif ('.(Employee::STATUSES[$employee->status] ?? $employee->status).').', $employee, 'Karyawan');
        }

        $now = now();
        $record = AttendanceRecord::where('employee_id', $employee->id)->whereDate('attendance_date', $now->toDateString())->first();

        if (! $record) {
            AttendanceRecord::create([
                'employee_id' => $employee->id,
                'attendance_date' => $now->toDateString(),
                'status' => 'present',
                'check_in' => $now->format('H:i'),
            ]);

            return self::ok('check_in', 'Selamat datang, '.$employee->name.'. Masuk '.$now->format('H:i').'.', $employee, 'Karyawan');
        }

        if ($record->status !== 'present') {
            return self::fail('not_allowed', 'Hari ini tercatat '.(AttendanceRecord::STATUSES[$record->status] ?? $record->status).'.', $employee, 'Karyawan');
        }

        $last = $record->check_out ?? $record->check_in;
        if ($last && $now->diffInMinutes($now->copy()->setTimeFromTimeString($last), true) < self::DEBOUNCE_MINUTES) {
            return self::ok('duplicate', 'Sudah tercatat, '.$employee->name.'.', $employee, 'Karyawan');
        }

        $record->update(['check_out' => $now->format('H:i')]);

        return self::ok('check_out', 'Sampai jumpa, '.$employee->name.'. Pulang '.$now->format('H:i').'.', $employee, 'Karyawan');
    }

    private static function customer(Customer $customer, string $method, ?TapDevice $device): array
    {
        if (! $customer->is_active || $customer->registration_status !== Customer::REGISTRATION_COMPLETE) {
            return self::fail('not_allowed', 'Customer tidak aktif.', $customer, 'Customer');
        }

        $now = now();
        $today = $customer->portalSessions()
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->with(['meetings' => fn ($query) => $query->whereDate('meeting_date', $now->toDateString())->where('is_completed', false)])
            ->get()
            ->flatMap->meetings;

        if ($today->isEmpty()) {
            return self::fail('no_meeting', 'Tidak ada jadwal training hari ini.', $customer, 'Customer');
        }

        $open = $today->filter(fn (TrainingSessionMeeting $meeting) => self::isOpen($meeting, $now));

        if ($open->isEmpty()) {
            $next = $today->sortBy('start_time')->first();

            return self::fail('outside_hours', 'Kelas belum dibuka atau sudah selesai (jadwal '.($next->timeRange() ?? 'hari ini').').', $customer, 'Customer');
        }

        $meeting = self::closestTo($open, $now);
        $existing = TrainingMeetingAttendance::where('training_session_meeting_id', $meeting->id)->where('customer_id', $customer->id)->first();

        if ($existing) {
            return self::ok('duplicate', 'Sudah hadir pukul '.$existing->checked_in_at->format('H:i').', '.$customer->name.'.', $customer, 'Customer');
        }

        TrainingMeetingAttendance::create([
            'training_session_meeting_id' => $meeting->id,
            'customer_id' => $customer->id,
            'checked_in_at' => $now,
            'method' => $method,
            'tap_device_id' => $device?->id,
        ]);

        return self::ok('attended', 'Hadir tercatat, '.$customer->name.'. Selamat belajar.', $customer, 'Customer');
    }

    private static function isOpen(TrainingSessionMeeting $meeting, Carbon $now): bool
    {
        if (! $meeting->start_time) {
            return true;
        }

        $date = $now->toDateString();
        $start = Carbon::parse($date.' '.$meeting->start_time);
        $end = $meeting->end_time ? Carbon::parse($date.' '.$meeting->end_time) : $start->copy()->addHours(self::DEFAULT_MEETING_HOURS);

        return $now->betweenIncluded($start->copy()->subMinutes(self::OPENS_BEFORE_MINUTES), $end);
    }

    /** @param Collection<int, TrainingSessionMeeting> $meetings */
    private static function closestTo(Collection $meetings, Carbon $now): TrainingSessionMeeting
    {
        return $meetings->sortBy(fn (TrainingSessionMeeting $meeting) => $meeting->start_time
            ? abs($now->diffInMinutes(Carbon::parse($now->toDateString().' '.$meeting->start_time), false))
            : PHP_INT_MAX)->first();
    }

    private static function ok(string $status, string $message, Model $person, string $role): array
    {
        return ['ok' => true, 'status' => $status, 'message' => $message, 'name' => $person->name, 'role' => $role];
    }

    private static function fail(string $status, string $message, ?Model $person = null, ?string $role = null): array
    {
        return ['ok' => false, 'status' => $status, 'message' => $message] + ($person ? ['name' => $person->name, 'role' => $role] : []);
    }
}
