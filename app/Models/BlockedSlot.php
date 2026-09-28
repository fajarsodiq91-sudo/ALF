<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** An operating-hours slot the company blocked itself, so it shows as booked to everyone. */
class BlockedSlot extends Model
{
    protected $fillable = ['date', 'start_time', 'end_time', 'reason'];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    public function key(): string
    {
        return $this->date->toDateString().'|'.$this->start_time.'-'.$this->end_time;
    }
}
