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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('registration_number')->unique();
            $table->string('fleet_number')->nullable();
            $table->string('model')->nullable();
            $table->string('type')->nullable(); // Truck, Bus, Van, etc.
            $table->string('status')->default('active'); // active, maintenance, retired
            $table->integer('axle_config')->nullable(); // e.g., 2, 4, 6, 8 (4x2, 6x4 etc)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
