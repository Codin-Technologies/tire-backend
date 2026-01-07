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
        Schema::create('skus', function (Blueprint $table) {
            $table->id();
            $table->string('sku_code')->unique()->comment('Unique SKU identifier');
            $table->string('sku_name');
            $table->string('category')->nullable();
            $table->string('unit_of_measure')->default('piece')->comment('e.g. piece, pair, set');
            $table->decimal('unit_price', 10, 2)->default(0)->comment('Price per unit');
            $table->decimal('cost_price', 10, 2)->nullable()->comment('Cost price for margin calculation');
            $table->enum('status', ['active', 'inactive', 'discontinued'])->default('active');
            $table->text('description')->nullable();
            
            // Tire-specific fields (since this is a tire management system)
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('size')->nullable();
            $table->string('tire_type')->nullable()->comment('e.g. summer, winter, all-season');
            $table->integer('load_index')->nullable();
            $table->string('speed_rating')->nullable();
            
            // Stock tracking
            $table->integer('current_stock')->default(0)->comment('Current available stock quantity');
            $table->integer('min_stock_level')->nullable()->comment('Minimum stock threshold');
            $table->integer('max_stock_level')->nullable()->comment('Maximum stock level');
            $table->integer('reorder_point')->nullable()->comment('Reorder trigger point');
            
            // Metadata
            $table->json('metadata')->nullable()->comment('Additional custom fields');
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index('sku_code');
            $table->index('status');
            $table->index(['brand', 'model', 'size']);
            $table->index('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('skus');
    }
};
