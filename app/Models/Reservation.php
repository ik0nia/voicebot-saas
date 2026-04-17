<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reservation extends Model
{
    use BelongsToTenant, SoftDeletes;

    public const STATUS_REQUESTED  = 'requested';
    public const STATUS_CONFIRMED  = 'confirmed';
    public const STATUS_CHECKED_IN = 'checked_in';
    public const STATUS_COMPLETED  = 'completed';
    public const STATUS_CANCELED   = 'canceled';
    public const STATUS_NOSHOW     = 'noshow';

    protected $fillable = [
        'tenant_id', 'bot_id', 'resource_id', 'conversation_id',
        'customer_name', 'customer_phone', 'customer_email',
        'party_size', 'notes', 'occasion',
        'starts_at', 'ends_at',
        'status',
        'prepay_amount_cents', 'prepay_status', 'prepay_payment_id',
        'source', 'metadata',
        'canceled_at', 'cancel_reason',
    ];

    protected $casts = [
        'starts_at'   => 'datetime',
        'ends_at'     => 'datetime',
        'canceled_at' => 'datetime',
        'metadata'    => 'array',
        'party_size'  => 'integer',
    ];

    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }

    public function resource(): BelongsTo
    {
        return $this->belongsTo(ResourceInventory::class, 'resource_id');
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function isActive(): bool
    {
        return in_array($this->status, [
            self::STATUS_REQUESTED, self::STATUS_CONFIRMED, self::STATUS_CHECKED_IN,
        ], true);
    }
}
