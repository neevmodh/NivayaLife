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
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_member_id')->constrained()->restrictOnDelete();
            $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->enum('type', ['blood_test', 'prescription', 'xray', 'mri_ct', 'insurance', 'bill', 'ecg', 'other']);
            $table->string('file_path');
            $table->string('original_filename');
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('mime_type')->nullable();
            $table->date('report_date')->nullable();
            $table->string('hospital_or_clinic_name')->nullable();
            $table->string('doctor_name')->nullable();
            $table->timestamp('uploaded_at')->nullable();

            $table->longText('ocr_text')->nullable();
            $table->enum('ocr_status', ['pending', 'processing', 'completed', 'failed'])->default('pending');

            $table->longText('ai_summary')->nullable();
            $table->string('ai_summary_language', 10)->default('en');
            $table->timestamp('ai_summary_generated_at')->nullable();

            $table->boolean('is_archived')->default(false);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['family_member_id', 'type']);
            $table->index('ocr_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
