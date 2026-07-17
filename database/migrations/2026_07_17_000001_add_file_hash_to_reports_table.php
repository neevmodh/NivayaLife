<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->string('file_hash', 64)->nullable()->after('mime_type');
            $table->index(['family_member_id', 'file_hash']);
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropIndex(['family_member_id', 'file_hash']);
            $table->dropColumn('file_hash');
        });
    }
};
