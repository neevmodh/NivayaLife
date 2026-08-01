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
            // Structured lab table (test/value/unit/reference_range/flag)
            // parsed by ExtractLabResultsJob for blood_test reports — display
            // only, null for every other report type or on extraction failure.
            $table->json('lab_results')->nullable()->after('detected_entities');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn('lab_results');
        });
    }
};
