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
            // Structured output from the optional TorchXRayVision service —
            // only ever set for chest X-ray reports whose vision analysis
            // ran with that service configured; null otherwise.
            $table->json('xray_findings')->nullable()->after('analysis_method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn('xray_findings');
        });
    }
};
