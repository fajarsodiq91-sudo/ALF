<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Model;

/** RFID card UIDs: readers send them in different shapes ("04:a1 b2", "04A1B2"), so one canonical form is stored and compared. */
class Rfid
{
    /** Upper-case, no spaces/colons/dashes; blank becomes null. */
    public static function normalize(?string $uid): ?string
    {
        $uid = strtoupper(preg_replace('/[\s:\-]/', '', (string) $uid));

        return $uid === '' ? null : $uid;
    }

    /** The employee or customer who already holds this UID, ignoring `$except` (the record being edited). */
    public static function owner(string $uid, ?Model $except = null): ?Model
    {
        foreach ([Employee::class, Customer::class] as $model) {
            $owner = $model::where('rfid_uid', $uid)
                ->when($except instanceof $model, fn ($query) => $query->whereKeyNot($except->getKey()))
                ->first();

            if ($owner) {
                return $owner;
            }
        }

        return null;
    }
}
