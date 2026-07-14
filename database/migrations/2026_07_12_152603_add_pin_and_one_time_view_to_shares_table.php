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
        Schema::table('shares', function (Blueprint $table) {
            $table->string('pin_hash')->nullable()->after('shared_with_label');
            $table->boolean('is_one_time')->default(false)->after('pin_hash');
            $table->timestamp('first_viewed_at')->nullable()->after('is_one_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shares', function (Blueprint $table) {
            $table->dropColumn(['pin_hash', 'is_one_time', 'first_viewed_at']);
        });
    }
};
