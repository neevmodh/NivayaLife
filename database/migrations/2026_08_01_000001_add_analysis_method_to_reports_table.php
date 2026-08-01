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
        Schema::table('reports', function (Blueprint $table) {
            // Null for the normal OCR-text path; 'vision' when the summary
            // came from a vision model describing the file directly because
            // no usable text could be extracted (e.g. an X-ray/sonography/
            // MRI scan with no embedded text).
            $table->string('analysis_method')->nullable()->after('ocr_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn('analysis_method');
        });
    }
};
