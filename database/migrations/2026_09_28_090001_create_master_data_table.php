<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Values that were hard-coded before, so existing records keep resolving to a label. */
    private const DEFAULTS = [
        'customer_type' => ['company' => 'Company', 'individual' => 'Individual', 'government' => 'Government / Institution'],
        'asset_category' => [
            'electronics' => 'Electronics', 'furniture' => 'Furniture', 'vehicle' => 'Vehicle',
            'equipment' => 'Equipment', 'software' => 'Software / License', 'other' => 'Other',
        ],
        'employment_type' => ['permanent' => 'Permanent', 'contract' => 'Contract', 'intern' => 'Intern', 'freelance' => 'Freelance'],
        'delivery_mode' => ['onsite' => 'On-site', 'online' => 'Online', 'hybrid' => 'Hybrid'],
        'leave_type' => [
            'annual' => 'Annual Leave', 'sick' => 'Sick Leave', 'unpaid' => 'Unpaid Leave',
            'maternity' => 'Maternity Leave', 'other' => 'Other',
        ],
    ];

    public function up(): void
    {
        Schema::create('master_data', function (Blueprint $table) {
            $table->id();
            $table->string('group', 50);
            $table->string('code', 100);
            $table->string('label');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['group', 'code']);
        });

        $now = now();
        foreach (self::DEFAULTS as $group => $items) {
            $order = 0;
            foreach ($items as $code => $label) {
                DB::table('master_data')->insert([
                    'group' => $group, 'code' => $code, 'label' => $label,
                    'sort_order' => ++$order, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('master_data');
    }
};
