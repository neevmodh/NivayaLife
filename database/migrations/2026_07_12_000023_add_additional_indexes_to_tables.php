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
            $table->index('report_date');
            $table->index('is_archived');
        });

        Schema::table('medications', function (Blueprint $table) {
            $table->index('end_date');
        });

        Schema::table('vaccinations', function (Blueprint $table) {
            $table->index('next_due_date');
        });

        Schema::table('doctors', function (Blueprint $table) {
            $table->index('specialization');
        });

        Schema::table('family_members', function (Blueprint $table) {
            $table->index('date_of_birth');
        });

        Schema::table('id_cards', function (Blueprint $table) {
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropIndex(['report_date']);
            $table->dropIndex(['is_archived']);
        });

        Schema::table('medications', function (Blueprint $table) {
            $table->dropIndex(['end_date']);
        });

        Schema::table('vaccinations', function (Blueprint $table) {
            $table->dropIndex(['next_due_date']);
        });

        Schema::table('doctors', function (Blueprint $table) {
            $table->dropIndex(['specialization']);
        });

        Schema::table('family_members', function (Blueprint $table) {
            $table->dropIndex(['date_of_birth']);
        });

        Schema::table('id_cards', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
        });
    }
};
