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
        Schema::create('health_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_member_id')->constrained()->restrictOnDelete();
            $table->foreignId('report_id')->nullable()->constrained()->cascadeOnDelete();

            $table->enum('metric_type', [
                'blood_pressure_systolic', 'blood_pressure_diastolic', 'blood_sugar_fasting',
                'blood_sugar_pp', 'hba1c', 'cholesterol_total', 'cholesterol_ldl',
                'cholesterol_hdl', 'hemoglobin', 'other',
            ]);
            $table->decimal('value', 8, 2);
            $table->string('unit', 20)->nullable();
            $table->date('recorded_date');
            $table->enum('source', ['manual', 'ocr_extracted', 'ai_extracted'])->default('manual');
            $table->timestamps();

            $table->index(['family_member_id', 'metric_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('health_metrics');
    }
};
