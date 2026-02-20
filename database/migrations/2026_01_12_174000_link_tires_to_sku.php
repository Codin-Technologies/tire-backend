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
        Schema::table('tires', function (Blueprint $table) {
            // Add reference to SKU
            $table->foreignId('sku_id')->nullable()->constrained('skus')->onDelete('cascade')->after('id');
        });

        // We migrate legacy data if possible, but for this migration we just structure it.
        // Dropping redundant columns
        Schema::table('tires', function (Blueprint $table) {
             $table->dropColumn(['brand', 'model', 'size']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tires', function (Blueprint $table) {
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('size')->nullable();
            $table->dropForeign(['sku_id']);
            $table->dropColumn('sku_id');
        });
    }
};
