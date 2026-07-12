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
        Schema::create('insurance_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_member_id')->constrained()->restrictOnDelete();
            $table->foreignId('report_id')->nullable()->constrained()->nullOnDelete();

            $table->string('provider_name');
            $table->string('policy_number');
            $table->string('policy_type')->nullable();
            $table->decimal('coverage_amount', 12, 2)->nullable();
            $table->decimal('premium_amount', 10, 2)->nullable();
            $table->date('start_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['family_member_id', 'expiry_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('insurance_policies');
    }
};
