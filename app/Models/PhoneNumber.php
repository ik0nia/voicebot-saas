<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhoneNumber extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'bot_id',
        'number',
        'provider',
        'friendly_name',
        'monthly_cost_cents',
        'is_active',
        'status',
        'telnyx_order_id',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_FAILED = 'failed';

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'monthly_cost_cents' => 'integer',
        ];
    }

    // Relationships

    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }

    // Methods

    public function formattedCost(): string
    {
        return number_format($this->monthly_cost_cents / 100, 2, ',', '.') . ' lei';
    }
}
