<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('archived_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('original_user_id');
            // Deliberately not unique — the same email can be archived more
            // than once over time (delete, re-register, delete again). The
            // live `users` table is the only place email uniqueness matters.
            $table->string('email')->index();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('password');
            $table->string('google_id')->nullable();
            $table->boolean('was_admin')->default(false);
            $table->timestamp('original_created_at')->nullable();
            // Full nested snapshot: each family member plus every related
            // record (reports, medications, vaccinations, allergies, etc.)
            // as it existed at deletion time.
            $table->json('data');
            // [{report_id, original_filename, archived_path}, ...] — the
            // uploaded files themselves are moved on disk, not duplicated.
            $table->json('archived_files')->nullable();
            $table->timestamp('archived_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('archived_accounts');
    }
};
