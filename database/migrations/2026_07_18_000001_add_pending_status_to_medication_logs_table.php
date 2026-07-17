<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 'missed' was the default before any code actually created rows, so
        // a freshly-created dose slot for later today looked identical to a
        // genuinely missed one. 'pending' is the real starting state; a
        // scheduled command flips it to 'missed' once its time has passed
        // with no reminded_at set.
        DB::statement("ALTER TABLE medication_logs MODIFY status ENUM('pending', 'taken', 'missed', 'skipped') NOT NULL DEFAULT 'pending'");

        Schema::table('medication_logs', function (Blueprint $table) {
            $table->timestamp('reminded_at')->nullable()->after('taken_at');
        });
    }

    public function down(): void
    {
        Schema::table('medication_logs', function (Blueprint $table) {
            $table->dropColumn('reminded_at');
        });

        DB::statement("ALTER TABLE medication_logs MODIFY status ENUM('taken', 'missed', 'skipped') NOT NULL DEFAULT 'missed'");
    }
};
