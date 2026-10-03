<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->string('verification_code', 32)->nullable()->unique()->after('suffix');
        });

        DB::table('certificates')->whereNull('verification_code')->pluck('id')->each(
            fn ($id) => DB::table('certificates')->where('id', $id)->update(['verification_code' => Str::random(24)])
        );
    }

    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropUnique(['verification_code']);
            $table->dropColumn('verification_code');
        });
    }
};
