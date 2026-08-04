<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('archived_accounts', function (Blueprint $table) {
            $table->string('phone_country_code', 6)->nullable()->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('archived_accounts', function (Blueprint $table) {
            $table->dropColumn('phone_country_code');
        });
    }
};
