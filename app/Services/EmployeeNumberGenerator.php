<?php

namespace App\Services;

use Carbon\CarbonInterface;
use DomainException;
use Illuminate\Support\Facades\DB;

class EmployeeNumberGenerator
{
    public const MAX_PER_MONTH = 99;

    /**
     * Builds the next employee number: YY + MM + two-digit running number for that month, like customer IDs.
     * The counter is stored separately so a number is never handed out twice, even after a delete.
     * Numbers that already exist (older or hand-typed ones) are skipped.
     *
     * @throws DomainException when the month already has 99 employees
     */
    public static function next(CarbonInterface $date): string
    {
        $period = $date->format('ym');

        return DB::transaction(function () use ($period) {
            $last = DB::table('employee_number_sequences')->where('period', $period)->lockForUpdate()->value('last_number');

            if ($last === null) {
                DB::table('employee_number_sequences')->insert(['period' => $period, 'last_number' => 0]);
                $last = 0;
            }

            do {
                if ($last >= self::MAX_PER_MONTH) {
                    throw new DomainException('The employee number limit ('.self::MAX_PER_MONTH.') for this month has been reached.');
                }

                $last++;
                $number = $period.str_pad((string) $last, 2, '0', STR_PAD_LEFT);
            } while (DB::table('employees')->where('employee_number', $number)->exists());

            DB::table('employee_number_sequences')->where('period', $period)->update(['last_number' => $last]);

            return $number;
        });
    }
}
