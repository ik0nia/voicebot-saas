<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RetrievalFeedback extends Model
{
    protected $table = 'retrieval_feedback';

    protected $fillable = [
        'bot_id',
        'conversation_id',
        'message_id',
        'query',
        'rating',
        'chunk_ids',
        'product_ids',
        'retrieval_type',
        'top_similarity',
    ];

    protected function casts(): array
    {
        return [
            'chunk_ids' => 'array',
            'product_ids' => 'array',
            'rating' => 'integer',
            'top_similarity' => 'float',
        ];
    }
}
