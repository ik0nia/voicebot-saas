<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditPurchase extends Model
{
    protected $fillable = [
        'tenant_id', 'plan_id', 'bundle_index', 'unit',
        'quantity', 'price_cents', 'currency',
        'stripe_session_id', 'stripe_payment_intent_id', 'status',
    ];

    protected $casts = [
        'bundle_index' => 'integer',
        'quantity' => 'integer',
        'price_cents' => 'integer',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}
