<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Laravel's schema builder can't alter an existing native ENUM's
        // value list, so this goes straight to SQL. Only needed on MySQL —
        // SQLite (used in tests) stores enum() as a plain string column with
        // an app-level CHECK, which Report::rules() already covers.
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE reports MODIFY type ENUM('blood_test', 'prescription', 'xray', 'sonography', 'mri_ct', 'insurance', 'bill', 'ecg', 'other') NOT NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE reports MODIFY type ENUM('blood_test', 'prescription', 'xray', 'mri_ct', 'insurance', 'bill', 'ecg', 'other') NOT NULL");
        }
    }
};
