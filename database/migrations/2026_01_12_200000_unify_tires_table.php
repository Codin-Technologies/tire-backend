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
            $table->string('dot_code')->nullable()->index()->after('serial_number');
            $table->integer('manufacture_week')->nullable()->after('dot_code');
            $table->integer('manufacture_year')->nullable()->after('manufacture_week');
            $table->enum('condition', ['NEW', 'USED', 'REFURBISHED', 'DAMAGED'])->default('NEW')->after('manufacture_year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tires', function (Blueprint $table) {
            $table->dropColumn(['dot_code', 'manufacture_week', 'manufacture_year', 'condition']);
        });
    }
};
