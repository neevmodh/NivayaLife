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
        Schema::create('vaccinations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_member_id')->constrained()->restrictOnDelete();
            $table->string('vaccine_name');
            $table->unsignedInteger('dose_number')->default(1);
            $table->date('date_administered');
            $table->date('next_due_date')->nullable();
            $table->string('location')->nullable();
            $table->timestamps();

            $table->index('family_member_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vaccinations');
    }
};
