<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('family_members', function (Blueprint $table) {
            // One of the illustrated presets (see App\Support\AvatarPresets).
            // Null means fall through to a photo, then initials — a photo
            // always wins over a preset, since it's the more personal choice.
            $table->string('avatar_preset', 20)->nullable()->after('photo_path');
        });
    }

    public function down(): void
    {
        Schema::table('family_members', function (Blueprint $table) {
            $table->dropColumn('avatar_preset');
        });
    }
};
