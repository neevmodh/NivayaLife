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
        Schema::create('medications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_member_id')->constrained()->restrictOnDelete();
            $table->foreignId('report_id')->nullable()->constrained()->nullOnDelete();

            $table->string('medicine_name');
            $table->string('dosage')->nullable();
            $table->string('frequency')->nullable();
            $table->json('schedule_times')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('prescribing_doctor')->nullable();
            $table->boolean('active')->default(true);
            $table->boolean('reminder_enabled')->default(false);
            $table->timestamps();

            $table->index(['family_member_id', 'active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medications');
    }
};
