<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Structured extraction now runs for every report type, not just blood
     * tests, so the per-type shape needs somewhere to live.
     *
     * `lab_results` is deliberately left in place and still populated for
     * result-table types — existing rows, the report page and the mobile API
     * all read it, and migrating them is a separate concern from shipping
     * extraction for the other twelve types.
     */
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->json('structured_data')->nullable()->after('lab_results');
            // Which engine produced it — lets the admin AI-usage page show how
            // much extraction work moved off the paid providers.
            $table->string('structured_provider')->nullable()->after('structured_data');
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn(['structured_data', 'structured_provider']);
        });
    }
};
