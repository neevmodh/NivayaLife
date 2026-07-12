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
        Schema::create('family_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('primary_account_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('linked_user_id')->nullable()->unique()->constrained('users')->nullOnDelete();

            $table->enum('relation', ['self', 'spouse', 'father', 'mother', 'son', 'daughter', 'grandfather', 'grandmother', 'other']);
            $table->string('full_name');
            $table->date('date_of_birth')->nullable();
            $table->string('gender')->nullable();
            $table->string('blood_group')->nullable();
            $table->decimal('height_cm', 5, 1)->nullable();
            $table->decimal('weight_kg', 5, 1)->nullable();
            $table->string('photo_path')->nullable();

            $table->string('address_line1')->nullable();
            $table->string('address_line2')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('pincode')->nullable();
            $table->string('country')->nullable();

            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->string('emergency_contact_relation')->nullable();

            $table->string('unique_health_id', 20)->unique();

            $table->enum('access_type', ['linked', 'dependent']);
            $table->enum('status', ['active', 'invited', 'pending'])->default('active');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['primary_account_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('family_members');
    }
};
