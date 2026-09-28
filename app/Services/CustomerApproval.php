<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\TrainingSession;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CustomerApproval
{
    /**
     * Approves a customer who submitted their own registration: assigns the permanent ID,
     * sets the initial portal password (equal to that ID; they must change it at first login)
     * and creates the programs, with their meetings, the company agreed on.
     *
     * @param  list<array<string, mixed>>  $programs  validated `programs` input
     *
     * @throws \DomainException when the month's customer IDs are used up
     */
    public static function approve(Customer $customer, array $programs, User $approver): Customer
    {
        return DB::transaction(function () use ($customer, $programs, $approver) {
            $customer->customer_code ??= CustomerCodeGenerator::next(now());
            $customer->password = $customer->customer_code;
            $customer->must_change_password = true;
            $customer->registration_status = Customer::REGISTRATION_COMPLETE;
            $customer->approved_at = now();
            $customer->approved_by = $approver->id;
            $customer->rejected_at = null;
            $customer->rejection_reason = null;
            $customer->save();

            foreach ($programs as $entry) {
                $meetings = collect($entry['meetings'])->sortBy('meeting_date')->values();

                $session = TrainingSession::create([
                    'training_program_id' => $entry['training_program_id'],
                    'customer_id' => $customer->id,
                    'instructor_id' => $entry['instructor_id'] ?? null,
                    'start_date' => $meetings->first()['meeting_date'],
                    'end_date' => $meetings->last()['meeting_date'],
                    'delivery_mode' => $entry['delivery_mode'],
                    'location' => $entry['location'] ?? null,
                    'participants_count' => 1,
                    'participant_limit' => $entry['participant_limit'] ?? null,
                    'fee' => $entry['fee'] ?? 0,
                    'payment_plan' => $entry['payment_plan'] ?? SessionPaymentPlan::FULL, // generate() below applies the single-meeting rule
                    'status' => 'planned',
                ]);

                foreach ($meetings as $meeting) {
                    $session->meetings()->create([
                        'meeting_date' => $meeting['meeting_date'],
                        'start_time' => $meeting['start_time'] ?? null,
                        'end_time' => $meeting['end_time'] ?? null,
                        'location' => $meeting['location'] ?? null,
                        'topic' => $meeting['topic'] ?? null,
                    ]);
                }

                SessionPaymentPlan::generate($session);
                $session->syncParticipantToken();
            }

            return $customer;
        });
    }

    public static function reject(Customer $customer, ?string $reason): void
    {
        $customer->registration_status = Customer::REGISTRATION_REJECTED;
        $customer->rejected_at = now();
        $customer->rejection_reason = $reason;
        $customer->save();
    }
}
