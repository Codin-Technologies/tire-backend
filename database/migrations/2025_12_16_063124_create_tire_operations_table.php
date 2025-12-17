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
        Schema::create('tire_operations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tire_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained(); // Technician
            $table->string('type'); // mount, dismount, rotate, repair, replace, dispose, inspect
            $table->integer('odometer')->nullable();
            $table->string('position')->nullable();
            $table->string('previous_position')->nullable(); // For rotations
            $table->text('notes')->nullable();
            $table->decimal('cost', 10, 2)->nullable();
            $table->string('vendor')->nullable(); // For external repairs
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tire_operations');
    }
};
