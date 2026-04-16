<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToTenant;

class AiApiMetric extends Model
{
    use BelongsToTenant;
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
        'call_id',
        'conversation_id',
        'message_id',
        'purpose',
    ];

    protected $casts = [
        'input_tokens' => 'integer',
        'output_tokens' => 'integer',
        'cost_cents' => 'decimal:4',
        'response_time_ms' => 'integer',
    ];

    public function call()
    {
        return $this->belongsTo(Call::class);
    }

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function message()
    {
        return $this->belongsTo(Message::class);
    }
}
