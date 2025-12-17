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
        Schema::create('inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained();
            $table->foreignId('assigned_to')->nullable()->constrained('users'); // Inspector
            $table->string('status')->default('pending'); // pending, scheduled, in_progress, completed, approved, rejected
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            
            // Execution Details
            $table->integer('odometer')->nullable();
            $table->string('type')->default('routine'); 
            $table->text('notes')->nullable(); // Inspector notes
            
            // Review Details
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->dateTime('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inspections');
    }
};
