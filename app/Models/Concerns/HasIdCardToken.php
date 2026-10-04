<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/** The unguessable token printed (as a QR link) on a person's ID card. Created the first time the card is needed. */
trait HasIdCardToken
{
    public function idCardToken(): string
    {
        if ($this->id_card_token === null) {
            $this->forceFill(['id_card_token' => Str::random(40)])->save();
        }

        return $this->id_card_token;
    }
}
