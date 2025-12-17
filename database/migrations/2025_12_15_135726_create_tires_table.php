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
        Schema::create('tires', function (Blueprint $table) {
            $table->id();
            $table->string('unique_tire_id')->unique()->comment('System generated unique ID');
            $table->string('serial_number')->unique()->nullable();
            $table->string('brand');
            $table->string('model');
            $table->string('size');
            $table->decimal('cost', 10, 2)->nullable();
            $table->string('vendor')->nullable();
            $table->date('purchase_date')->nullable();
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('status', ['available', 'reserved', 'mounted', 'defective', 'retired'])->default('available');
            $table->timestamps();
            
            // Index for faster search
            $table->index(['brand', 'model', 'size']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tires');
    }
};
