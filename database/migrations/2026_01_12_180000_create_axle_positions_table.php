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
        Schema::create('axle_positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles')->onDelete('cascade');
            $table->string('position_code')->comment('e.g. A1-L, A2-R1'); // Unique per vehicle?
            $table->integer('axle_number');
            $table->enum('side', ['L', 'R']); // Left or Right
            $table->enum('tire_type_requirement', ['STEER', 'DRIVE', 'TRAILER', 'ALL_POSITION'])->nullable();
            
            // Current tire assignment
            // We use nullable SKU_ID reference (tires table id? No, tires table id)
            // Wait, tire_id refers to the 'tires' table id.
            $table->foreignId('tire_id')->nullable()->constrained('tires')->nullOnDelete();
            
            $table->timestamps();

            $table->unique(['vehicle_id', 'position_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('axle_positions');
    }
};
