<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Staff can print a certificate before the session is completed; it stays hidden from the customer until released. */
    public function up(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->timestamp('released_at')->nullable()->after('issued_at');
        });

        // Every certificate that exists today was issued on completion, so it is already visible to the customer.
        DB::table('certificates')->update(['released_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropColumn('released_at');
        });
    }
};
