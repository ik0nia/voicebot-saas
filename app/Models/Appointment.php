<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Appointment extends Model
{
    use BelongsToTenant, SoftDeletes;

    public const STATUS_REQUESTED     = 'requested';
    public const STATUS_CONFIRMED     = 'confirmed';
    public const STATUS_REMINDER_SENT = 'reminder_sent';
    public const STATUS_COMPLETED     = 'completed';
    public const STATUS_CANCELED      = 'canceled';
    public const STATUS_NOSHOW        = 'noshow';

    protected $fillable = [
        'tenant_id', 'bot_id',
        'service_type_id', 'staff_member_id',
        'lead_id', 'conversation_id',
        'customer_name', 'customer_phone', 'customer_email', 'notes',
        'starts_at', 'ends_at',
        'status', 'source',
        'rescheduled_from_id',
        'metadata',
        'canceled_at', 'cancel_reason',
    ];

    protected $casts = [
        'starts_at'    => 'datetime',
        'ends_at'      => 'datetime',
        'canceled_at'  => 'datetime',
        'metadata'     => 'array',
    ];

    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceType::class);
    }

    public function staffMember(): BelongsTo
    {
        return $this->belongsTo(StaffMember::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function rescheduledFrom(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'rescheduled_from_id');
    }

    public function isActive(): bool
    {
        return in_array($this->status, [
            self::STATUS_REQUESTED, self::STATUS_CONFIRMED, self::STATUS_REMINDER_SENT,
        ], true);
    }
}
