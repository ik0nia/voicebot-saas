<?php

namespace App\Services;

use App\Models\BotKnowledge;
use Illuminate\Support\Facades\DB;
use OpenAI\Laravel\Facades\OpenAI;

class KnowledgeSearchService
{
    public function search(int $botId, string $query, int $limit = 5): array
    {
        try {
            $response = OpenAI::embeddings()->create([
                'model' => 'text-embedding-3-small',
                'input' => $query,
            ]);
            $queryEmbedding = $response->embeddings[0]->embedding;
            $embeddingStr = '[' . implode(',', $queryEmbedding) . ']';

            $results = DB::select("
                SELECT id, title, content, chunk_index,
                       1 - (embedding <=> ?) as similarity
                FROM bot_knowledge
                WHERE bot_id = ? AND status = 'ready' AND embedding IS NOT NULL
                ORDER BY embedding <=> ?
                LIMIT ?
            ", [$embeddingStr, $botId, $embeddingStr, $limit]);

            return $results;
        } catch (\Exception $e) {
            return [];
        }
    }

    public function buildContext(int $botId, string $query): string
    {
        $results = $this->search($botId, $query, 3);

        if (empty($results)) {
            return '';
        }

        $context = "Informații relevante din baza de cunoștințe:\n\n";
        foreach ($results as $result) {
            $context .= "--- {$result->title} ---\n{$result->content}\n\n";
        }

        return $context;
    }
}
