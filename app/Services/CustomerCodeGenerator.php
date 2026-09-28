<?php

namespace App\Services;

use Carbon\CarbonInterface;
use DomainException;
use Illuminate\Support\Facades\DB;

class CustomerCodeGenerator
{
    public const MAX_PER_MONTH = 99;

    /**
     * Builds the next permanent customer ID: YY + MM + two-digit running number for that month.
     * The counter is stored separately so a number is never handed out twice, even after a delete.
     *
     * @throws DomainException when the month already has 99 customers
     */
    public static function next(CarbonInterface $date): string
    {
        $period = $date->format('ym');

        return DB::transaction(function () use ($period) {
            $last = DB::table('customer_code_sequences')->where('period', $period)->lockForUpdate()->value('last_number');

            if ($last === null) {
                DB::table('customer_code_sequences')->insert(['period' => $period, 'last_number' => 0]);
                $last = 0;
            }

            if ($last >= self::MAX_PER_MONTH) {
                throw new DomainException('The customer ID limit ('.self::MAX_PER_MONTH.') for this month has been reached.');
            }

            $next = $last + 1;
            DB::table('customer_code_sequences')->where('period', $period)->update(['last_number' => $next]);

            return $period.str_pad((string) $next, 2, '0', STR_PAD_LEFT);
        });
    }
}
