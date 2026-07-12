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
        Schema::create('shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('family_member_id')->nullable()->constrained()->cascadeOnDelete();

            $table->string('token', 32)->unique();
            $table->enum('access_type', ['single_report', 'full_summary', 'emergency_card']);
            $table->string('shared_with_label')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('revoked_at')->nullable();
            $table->unsignedInteger('view_count')->default(0);
            $table->timestamps();

            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shares');
    }
};
