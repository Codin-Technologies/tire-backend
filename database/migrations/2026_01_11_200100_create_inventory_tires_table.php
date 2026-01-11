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
        Schema::create('inventory_tires', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sku_id')->constrained('skus')->onDelete('cascade');
            $table->foreignId('warehouse_id')->constrained('warehouses')->onDelete('cascade');
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('cascade');
            
            // DOT Code and Manufacturing Info
            $table->string('dot_code')->unique()->comment('Department of Transportation code');
            $table->integer('manufacture_week')->comment('Week number 1-52');
            $table->integer('manufacture_year')->comment('Manufacturing year');
            
            // Condition and Status
            $table->enum('condition', ['NEW', 'USED', 'REFURBISHED', 'DAMAGED'])->default('NEW');
            $table->enum('status', ['AVAILABLE', 'RESERVED', 'SOLD', 'SCRAPPED', 'IN_USE'])->default('AVAILABLE');
            
            // Tracking
            $table->string('qr_code')->unique()->comment('Unique QR code for tire');
            $table->date('received_date');
            $table->date('sold_date')->nullable();
            
            // Additional Info
            $table->decimal('purchase_price', 10, 2)->nullable();
            $table->decimal('selling_price', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index('dot_code');
            $table->index('qr_code');
            $table->index('status');
            $table->index('condition');
            $table->index(['sku_id', 'warehouse_id']);
            $table->index('received_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_tires');
    }
};
