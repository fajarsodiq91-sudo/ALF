<?php

namespace App\Services;

use App\Models\TrainingSession;

/**
 * Turns a session's fee and payment plan into the payments the customer owes.
 * "full": everything upfront. "installment": half upfront, half at the middle meeting.
 * A single-meeting program must always be paid in full upfront.
 */
class SessionPaymentPlan
{
    public const FULL = 'full';

    public const INSTALLMENT = 'installment';

    public const PLANS = [
        self::FULL => 'Pay in full upfront',
        self::INSTALLMENT => '50% upfront, 50% at the middle meeting (meeting 4 of 6, 7 of 12)',
    ];

    /** The meeting at which the second half falls due: 4 of 6 meetings, 7 of 12 (halfway, plus one). */
    public static function middleMeeting(int $meetingCount): int
    {
        return $meetingCount < 2 ? 1 : intdiv($meetingCount, 2) + 1;
    }

    /** The plan that actually applies: with exactly one meeting, only paying in full upfront is allowed. */
    public static function effective(string $plan, ?int $meetingCount): string
    {
        return $meetingCount === 1 ? self::FULL : $plan;
    }

    /**
     * Rebuilds the unpaid payments from the session's fee, plan and meetings.
     * Payments already recorded in Finance are never touched, so callers must check for those first.
     */
    public static function generate(TrainingSession $session): void
    {
        $session->payments()->whereNull('income_transaction_id')->delete();

        $fee = round((float) $session->fee, 2);
        $plan = self::effective($session->payment_plan, $session->meetings()->count());

        if ($plan !== $session->payment_plan) {
            $session->update(['payment_plan' => $plan]);
        }

        if ($fee <= 0) {
            return;
        }

        if ($plan === self::INSTALLMENT) {
            $first = round($fee / 2, 2);

            $session->payments()->createMany([
                ['label' => 'Down payment (50%)', 'percentage' => 50, 'amount' => $first, 'due_meeting_number' => null],
                ['label' => 'Final payment (50%)', 'percentage' => 50, 'amount' => round($fee - $first, 2), 'due_meeting_number' => self::middleMeeting($session->meetings()->count())],
            ]);

            return;
        }

        $session->payments()->create(['label' => 'Full payment', 'percentage' => 100, 'amount' => $fee, 'due_meeting_number' => null]);
    }

    /** Keeps the second installment on the middle meeting when meetings are added or removed. */
    public static function syncDueMeeting(TrainingSession $session): void
    {
        if ($session->payment_plan !== self::INSTALLMENT) {
            return;
        }

        // Down to a single meeting: the whole fee is due upfront, unless a payment is already recorded in Finance.
        if ($session->meetings()->count() === 1) {
            if ($session->payments()->whereNotNull('income_transaction_id')->doesntExist()) {
                self::generate($session);
            }

            return;
        }

        $session->payments()
            ->whereNull('income_transaction_id')
            ->whereNotNull('due_meeting_number')
            ->update(['due_meeting_number' => self::middleMeeting($session->meetings()->count())]);
    }

    public static function rupiah(float|string $amount): string
    {
        return 'Rp '.number_format((float) $amount, 0, ',', '.');
    }

    /**
     * What the customer pays and when, before a session exists (used while registering and reviewing).
     *
     * @return list<array{label: string, amount: float, when: string}>
     */
    public static function preview(float|string $fee, string $plan, ?int $meetingCount = null): array
    {
        $fee = round((float) $fee, 2);

        if ($fee <= 0) {
            return [];
        }

        if (self::effective($plan, $meetingCount) === self::INSTALLMENT) {
            $first = round($fee / 2, 2);
            $when = $meetingCount ? 'At meeting '.self::middleMeeting($meetingCount) : 'At the middle meeting';

            return [
                ['label' => 'Down payment (50%)', 'amount' => $first, 'when' => 'Upon registration'],
                ['label' => 'Final payment (50%)', 'amount' => round($fee - $first, 2), 'when' => $when],
            ];
        }

        return [['label' => 'Full payment', 'amount' => $fee, 'when' => 'Upon registration']];
    }
}
