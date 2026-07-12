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
        Schema::create('bmi_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_member_id')->constrained()->restrictOnDelete();
            $table->decimal('height_cm', 5, 1);
            $table->decimal('weight_kg', 5, 1);
            $table->decimal('bmi_value', 4, 1);
            $table->enum('bmi_category', ['underweight', 'normal', 'overweight', 'obese']);
            $table->date('recorded_date');
            $table->enum('source', ['manual', 'report_extracted'])->default('manual');
            $table->timestamps();

            $table->index(['family_member_id', 'recorded_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bmi_logs');
    }
};
