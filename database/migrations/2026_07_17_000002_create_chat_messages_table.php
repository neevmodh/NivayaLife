<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asked_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->enum('role', ['user', 'assistant']);
            $table->longText('content');
            $table->unsignedInteger('input_tokens')->nullable();
            $table->unsignedInteger('output_tokens')->nullable();

            $table->timestamps();

            $table->index(['family_member_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};
