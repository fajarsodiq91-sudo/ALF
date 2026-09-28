<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\Customer;
use App\Models\TrainingSession;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * Issues certificates when a learning session is completed.
 * Number: ALF/LRN/{year}/{month in roman}/{running number}-{unique code}, where year, month and running number
 * come from the holder's customer ID (YYMMNN) and the code is three random letters/digits that are never reused.
 */
class CertificateIssuer
{
    private const ROMAN = [1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI', 7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'];

    private const CHARS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

    /** Creates the missing certificates of a completed learning session; returns how many were created. */
    public static function issueFor(TrainingSession $session): int
    {
        $session->loadMissing('program');

        if ($session->status !== 'completed' || $session->program?->program_type !== 'learning') {
            return 0;
        }

        // Corporate sessions certify the participants; otherwise the customer themselves.
        $holders = $session->acceptsParticipants() ? $session->participants()->get() : collect([$session->customer])->filter();
        $created = 0;

        foreach ($holders as $holder) {
            if (Certificate::where('training_session_id', $session->id)->where('customer_id', $holder->id)->exists()) {
                continue;
            }

            self::create($session, $holder);
            $created++;
        }

        return $created;
    }

    /** ALF/LRN/2026/IX/001 from customer ID 260901; null when the ID does not have that shape. */
    public static function prefix(Customer $holder): ?string
    {
        if (! preg_match('/^(\d{2})(0[1-9]|1[0-2])(\d{2})$/', (string) $holder->customer_code, $m)) {
            return null;
        }

        return 'ALF/LRN/20'.$m[1].'/'.self::ROMAN[(int) $m[2]].'/'.str_pad($m[3], 3, '0', STR_PAD_LEFT);
    }

    private static function create(TrainingSession $session, Customer $holder): Certificate
    {
        $prefix = self::prefix($holder) ?? 'ALF/LRN/'.now()->format('Y').'/'.self::ROMAN[now()->month].'/000';

        // The unique indexes are the final guard; on the rare clash just draw another code.
        for ($attempt = 0; $attempt < 20; $attempt++) {
            $suffix = self::suffix();

            try {
                return Certificate::create([
                    'training_session_id' => $session->id,
                    'customer_id' => $holder->id,
                    'number' => $prefix.'-'.$suffix,
                    'suffix' => $suffix,
                    'issued_at' => today(),
                ]);
            } catch (UniqueConstraintViolationException $exception) {
                if (Certificate::where('training_session_id', $session->id)->where('customer_id', $holder->id)->exists()) {
                    return Certificate::where('training_session_id', $session->id)->where('customer_id', $holder->id)->first();
                }
            }
        }

        throw new \RuntimeException('Could not generate a unique certificate code.');
    }

    /** Three characters mixing letters and digits, like A1P. */
    private static function suffix(): string
    {
        do {
            $code = '';
            for ($i = 0; $i < 3; $i++) {
                $code .= self::CHARS[random_int(0, strlen(self::CHARS) - 1)];
            }
        } while (! preg_match('/[A-Z]/', $code) || ! preg_match('/\d/', $code) || Certificate::where('suffix', $code)->exists());

        return $code;
    }
}
