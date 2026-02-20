<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('skus', function (Blueprint $table) {
            // Retread Rules
            $table->boolean('retreadable')->default(false)->after('metadata');
            $table->integer('max_retread_cycles')->default(0)->after('retreadable');
            
            // Performance Rules
            $table->integer('expected_mileage')->nullable()->after('max_retread_cycles');
            $table->decimal('min_tread_depth', 4, 2)->nullable()->comment('Minimum legal tread depth in mm')->after('expected_mileage');
            
            // Categorization
            $table->enum('tire_category', ['STEER', 'DRIVE', 'TRAILER', 'ALL_POSITION'])->nullable()->after('min_tread_depth');
            
            // Supply Chain rules
            $table->foreignId('preferred_warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete()->after('tire_category');
            $table->integer('lead_time_days')->nullable()->after('preferred_warehouse_id');
            $table->string('budget_category')->nullable()->comment('PREMIUM, MID_RANGE, BUDGET')->after('lead_time_days');
            
            // Safety
            $table->integer('max_age_months')->default(72)->comment('Maximum allowed age for this tire type')->after('budget_category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('skus', function (Blueprint $table) {
            $table->dropForeign(['preferred_warehouse_id']);
            $table->dropColumn([
                'retreadable',
                'max_retread_cycles',
                'expected_mileage',
                'min_tread_depth',
                'tire_category',
                'preferred_warehouse_id',
                'lead_time_days',
                'budget_category',
                'max_age_months'
            ]);
        });
    }
};
