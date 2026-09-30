<?php

use App\Models\TrainingSession;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /** Groups set up before the owner counted as a participant: seat them so the link only asks for the others. */
    public function up(): void
    {
        TrainingSession::query()->whereNotNull('participant_token')->with('customer')->get()->each->seatIndividualOwner();
    }

    public function down(): void
    {
        //
    }
};
