<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseAttribution extends Model
{
    use HasFactory;

    protected $table = 'purchase_attributions';

    protected $fillable = [
        'tenant_id',
        'bot_id',
        'conversation_id',
        'session_id',
        'wc_order_id',
        'order_total_cents',
        'attribution_mode',
        'attribution_reason',
        'attribution_window_hours',
        'products_in_order',
        'products_shown_in_chat',
    ];

    protected function casts(): array
    {
        return [
            'products_in_order' => 'array',
            'products_shown_in_chat' => 'array',
            'order_total_cents' => 'decimal:0',
            'attribution_window_hours' => 'integer',
        ];
    }

    // ─── Relationships ───

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }
}
