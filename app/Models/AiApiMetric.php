<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiApiMetric extends Model
{
    protected $fillable = [
        'provider',
        'model',
        'input_tokens',
        'output_tokens',
        'cost_cents',
        'response_time_ms',
        'status',
        'error_type',
        'bot_id',
        'tenant_id',
    ];

    protected $casts = [
        'input_tokens' => 'integer',
        'output_tokens' => 'integer',
        'cost_cents' => 'decimal:4',
        'response_time_ms' => 'integer',
    ];
}
