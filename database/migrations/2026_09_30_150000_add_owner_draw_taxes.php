<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        foreach ([
            ['PPh Dividen (10%)', 10, 'PPh final atas dividen yang diterima pemegang saham orang pribadi'],
            ['PPh 21 (5%)', 5, 'PPh Pasal 21 lapisan pertama; untuk gaji owner ubah nominalnya sesuai perhitungan progresif'],
        ] as [$name, $rate, $description]) {
            DB::table('taxes')->updateOrInsert(
                ['name' => $name],
                ['type' => 'withholding', 'rate' => $rate, 'is_active' => true, 'description' => $description, 'created_at' => $now, 'updated_at' => $now],
            );
        }
    }

    public function down(): void
    {
        DB::table('taxes')->whereIn('name', ['PPh Dividen (10%)', 'PPh 21 (5%)'])->delete();
    }
};
