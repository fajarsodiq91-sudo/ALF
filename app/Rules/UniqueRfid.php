<?php

namespace App\Rules;

use App\Services\Rfid;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;

/** A card belongs to exactly one person, whether employee or customer. */
class UniqueRfid implements ValidationRule
{
    public function __construct(private readonly ?Model $except = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($owner = Rfid::owner($value, $this->except)) {
            $fail('This RFID card is already registered to '.$owner->name.'.');
        }
    }
}
