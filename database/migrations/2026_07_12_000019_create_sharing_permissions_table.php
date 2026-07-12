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
        Schema::create('sharing_permissions', function (Blueprint $table) {
            $table->id();

            // The linked member's own profile — the data being granted access to.
            $table->foreignId('family_member_id')->constrained()->cascadeOnDelete();

            // Who is being granted view access — the primary account, in practice.
            $table->foreignId('granted_to_user_id')->constrained('users')->cascadeOnDelete();

            $table->enum('scope', ['full', 'reports_only', 'summary_only'])->default('full');
            $table->timestamp('granted_at');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->unique(['family_member_id', 'granted_to_user_id'], 'sharing_permissions_member_grantee_unique');
            $table->index('granted_to_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sharing_permissions');
    }
};
