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
            // Biomedical entities (drug/diagnosis mentions) detected by the
            // optional clinical-nlp-service (scispaCy/medspaCy) — display
            // only, never null unless the service was unconfigured/failed.
            $table->json('detected_entities')->nullable()->after('xray_findings');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn('detected_entities');
        });
    }
};
