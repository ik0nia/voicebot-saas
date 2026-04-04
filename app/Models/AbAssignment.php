<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbAssignment extends Model
{
    protected $fillable = [
        'experiment_id',
        'conversation_id',
        'variant_id',
        'metrics',
    ];

    protected function casts(): array
    {
        return [
            'metrics' => 'array',
        ];
    }

    public function experiment(): BelongsTo
    {
        return $this->belongsTo(AbExperiment::class, 'experiment_id');
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }
}
