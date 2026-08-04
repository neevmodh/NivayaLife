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
            $table->string('phone_country_code', 6)->nullable()->after('phone');
        });

        // Existing accounts predate this field — default them to India (+91),
        // the same default the signup form's country selector starts on.
        DB::table('users')->whereNotNull('phone')->update(['phone_country_code' => '+91']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('phone_country_code');
        });
    }
};
