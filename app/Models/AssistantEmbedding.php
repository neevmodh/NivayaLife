<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssistantEmbedding extends Model
{
    protected $fillable = [
        'family_member_id',
        'source_type',
        'source_id',
        'chunk_text',
        'embedding',
    ];

    protected function casts(): array
    {
        return [
            'embedding' => 'array',
        ];
    }

    public function familyMember(): BelongsTo
    {
        return $this->belongsTo(FamilyMember::class);
    }

    /** Cosine similarity between this chunk's embedding and a query vector — the only thing RagRetriever needs from a row. */
    public function similarityTo(array $queryEmbedding): float
    {
        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        foreach ($this->embedding as $i => $value) {
            $dot += $value * ($queryEmbedding[$i] ?? 0);
            $normA += $value ** 2;
            $normB += ($queryEmbedding[$i] ?? 0) ** 2;
        }

        if ($normA === 0.0 || $normB === 0.0) {
            return 0.0;
        }

        return $dot / (sqrt($normA) * sqrt($normB));
    }
}
