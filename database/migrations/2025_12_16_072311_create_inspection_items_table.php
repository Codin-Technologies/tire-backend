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
        Schema::create('inspection_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inspection_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tire_id')->nullable()->constrained()->nullOnDelete();
            $table->string('position'); // FL, FR, etc.
            $table->decimal('pressure_psi', 5, 2)->nullable();
            $table->decimal('tread_depth_mm', 5, 2)->nullable();
            $table->string('condition')->default('good'); // good, fair, poor, critical
            $table->json('issues')->nullable(); // ['cut', 'bulge']
            $table->json('images')->nullable(); // paths to images
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inspection_items');
    }
};
