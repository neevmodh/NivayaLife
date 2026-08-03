<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // The `password` column itself can't distinguish a real,
            // user-known password from the random unusable one generated
            // for Google-only signups (the column is NOT NULL, so it can
            // never just be empty) — this is the explicit signal instead.
            // Defaults true: existing accounts predate Google-only signup
            // and are assumed to have a real password unless known
            // otherwise.
            $table->boolean('has_password')->default(true)->after('password');
        });

        DB::table('users')->whereNotNull('google_id')->update(['has_password' => false]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('has_password');
        });
    }
};
