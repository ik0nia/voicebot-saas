<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyOutcomeRollup extends Model
{
    protected $fillable = [
        'tenant_id', 'bot_id', 'date',
        'bookings_requested', 'bookings_confirmed', 'bookings_canceled', 'bookings_noshow',
        'leads_generated', 'quotes_sent', 'callbacks_requested', 'contact_messages_received',
        'orders_influenced', 'carts_abandoned', 'revenue_booked_cents',
        'conversations_count', 'voice_calls_count', 'unique_visitors',
        'usd_ron_rate',
    ];

    protected $casts = [
        'date' => 'date',
        'bookings_requested' => 'integer',
        'bookings_confirmed' => 'integer',
        'bookings_canceled' => 'integer',
        'bookings_noshow' => 'integer',
        'leads_generated' => 'integer',
        'quotes_sent' => 'integer',
        'callbacks_requested' => 'integer',
        'contact_messages_received' => 'integer',
        'orders_influenced' => 'integer',
        'carts_abandoned' => 'integer',
        'revenue_booked_cents' => 'decimal:2',
        'conversations_count' => 'integer',
        'voice_calls_count' => 'integer',
        'unique_visitors' => 'integer',
        'usd_ron_rate' => 'decimal:4',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }
}
