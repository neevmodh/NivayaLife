<?php

namespace App\Jobs;

use App\Models\AssistantEmbedding;
use App\Services\Ollama\OllamaClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Embeds one record's text for the Assistant's RAG retrieval path
 * (RagRetriever). Purely additive: if Ollama isn't configured or the call
 * fails, this just doesn't create a row — the assistant still works via the
 * existing full-context prompt (AssistantContextBuilder::build()).
 */
class EmbedRecordJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(
        public int $familyMemberId,
        public string $sourceType,
        public int $sourceId,
        public string $chunkText,
    ) {}

    public function handle(OllamaClient $ollama): void
    {
        if (! $ollama->isConfigured() || trim($this->chunkText) === '') {
            return;
        }

        try {
            $embedding = $ollama->embed($this->chunkText);

            if ($embedding === []) {
                return;
            }

            AssistantEmbedding::updateOrCreate(
                ['source_type' => $this->sourceType, 'source_id' => $this->sourceId],
                [
                    'family_member_id' => $this->familyMemberId,
                    'chunk_text' => $this->chunkText,
                    'embedding' => $embedding,
                ],
            );
        } catch (Throwable $e) {
            report($e);
        }
    }
}
