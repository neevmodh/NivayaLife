<?php

namespace App\Services\Assistant;

use App\Models\FamilyMember;
use App\Services\Ollama\OllamaClient;

/**
 * Retrieval half of the Assistant's RAG pipeline: embeds the question, then
 * ranks a family member's indexed chunks (AssistantEmbedding, kept current by
 * EmbedRecordJob) by cosine similarity. Brute-force in PHP rather than a
 * vector DB — per-member row counts are small enough (dozens to low
 * hundreds) that this is simpler than standing up pgvector/a vector store.
 */
class RagRetriever
{
    private const TOP_K = 8;

    public function __construct(private readonly OllamaClient $ollama) {}

    /** @return string[] The top-K most relevant chunk texts, most relevant first. */
    public function retrieve(FamilyMember $member, string $question): array
    {
        $chunks = $member->assistantEmbeddings()->get();

        if ($chunks->isEmpty()) {
            return [];
        }

        $queryEmbedding = $this->ollama->embed($question);

        if ($queryEmbedding === []) {
            return [];
        }

        return $chunks
            ->map(fn ($chunk) => ['text' => $chunk->chunk_text, 'score' => $chunk->similarityTo($queryEmbedding)])
            ->sortByDesc('score')
            ->take(self::TOP_K)
            ->pluck('text')
            ->values()
            ->all();
    }
}
