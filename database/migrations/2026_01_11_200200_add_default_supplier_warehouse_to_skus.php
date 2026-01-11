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
            $table->foreignId('default_supplier_id')->nullable()->after('metadata')->constrained('suppliers')->nullOnDelete();
            $table->foreignId('default_warehouse_id')->nullable()->after('default_supplier_id')->constrained('warehouses')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('skus', function (Blueprint $table) {
            $table->dropForeign(['default_supplier_id']);
            $table->dropForeign(['default_warehouse_id']);
            $table->dropColumn(['default_supplier_id', 'default_warehouse_id']);
        });
    }
};
