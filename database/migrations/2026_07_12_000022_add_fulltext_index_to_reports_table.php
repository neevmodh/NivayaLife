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
        // SQLite (local dev only — production runs MySQL) has no fulltext
        // index support at all; skip rather than fail the whole migration
        // batch, since local dev never depends on fulltext search working.
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('reports', function (Blueprint $table) {
            $table->fullText(['ocr_text', 'ai_summary'], 'reports_ocr_ai_summary_fulltext');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('reports', function (Blueprint $table) {
            $table->dropFullText('reports_ocr_ai_summary_fulltext');
        });
    }
};
