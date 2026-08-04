<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pending_deployments', function (Blueprint $table) {
            $table->id();
            $table->string('commit_sha', 40)->unique();
            $table->text('commit_message');
            $table->string('author_name');
            $table->string('author_email')->nullable();
            $table->string('branch')->default('main');
            $table->timestamp('pushed_at');
            // approved is transient (set immediately before the Railway call,
            // then flipped to deployed/failed within the same request) — kept
            // as a distinct value for clarity mid-request, not a resting state.
            $table->enum('status', ['pending', 'approved', 'rejected', 'deployed', 'superseded', 'failed'])->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('railway_deployment_triggered_at')->nullable();
            $table->timestamps();

            $table->index(['branch', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pending_deployments');
    }
};
