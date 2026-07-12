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
        Schema::create('chronic_conditions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_member_id')->constrained()->restrictOnDelete();
            $table->string('condition_name');
            $table->date('diagnosed_date')->nullable();
            $table->enum('status', ['active', 'managed', 'resolved'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('family_member_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chronic_conditions');
    }
};
