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
        Schema::create('assistant_embeddings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_member_id')->constrained()->cascadeOnDelete();
            // Which record this chunk came from — 'source_type' + 'source_id'
            // together let EmbedRecordJob upsert instead of duplicating rows
            // every time a report/medication/vaccination is re-saved.
            $table->string('source_type');
            $table->unsignedBigInteger('source_id');
            $table->text('chunk_text');
            // Brute-force cosine similarity in PHP over these JSON arrays is
            // simpler than a dedicated vector store at this app's per-member
            // row scale (dozens to low hundreds) — see RagRetriever.
            $table->json('embedding');
            $table->timestamps();

            $table->index('family_member_id');
            $table->unique(['source_type', 'source_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assistant_embeddings');
    }
};
