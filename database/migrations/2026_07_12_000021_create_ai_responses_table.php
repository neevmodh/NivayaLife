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
        // Full history of every AI-generated answer for a report (every summary,
        // every translation, every regeneration) — reports.ai_summary stays as a
        // fast "current" cache, this table is the permanent audit trail.
        Schema::create('ai_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ai_job_id')->nullable()->constrained('ai_jobs')->nullOnDelete();

            $table->enum('response_type', ['summary', 'translation', 'entity_extraction']);
            $table->longText('content');
            $table->string('language', 10)->default('en');
            $table->string('provider')->default('gemini');
            $table->timestamp('generated_at');
            $table->timestamps();

            $table->index(['report_id', 'response_type', 'language']);
            $table->index(['report_id', 'generated_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_responses');
    }
};
