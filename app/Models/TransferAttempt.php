<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransferAttempt extends Model
{
    use HasFactory, BelongsToTenant;

    public const STATUS_INITIATING        = 'initiating';
    public const STATUS_RINGING           = 'ringing';
    public const STATUS_OPERATOR_ANSWERED = 'operator_answered';
    public const STATUS_BRIDGED           = 'bridged';
    public const STATUS_COMPLETED         = 'completed';
    public const STATUS_NO_ANSWER         = 'no_answer';
    public const STATUS_REJECTED          = 'rejected';
    public const STATUS_FAILED            = 'failed';

    protected $fillable = [
        'tenant_id',
        'bot_id',
        'call_id',
        'inbound_call_sid',
        'operator_call_sid',
        'operator_number',
        'requested_reason',
        'summary',
        'status',
        'failure_reason',
        'initiated_at',
        'operator_answered_at',
        'bridged_at',
        'ended_at',
        'bridged_seconds',
    ];

    protected $casts = [
        'initiated_at'         => 'datetime',
        'operator_answered_at' => 'datetime',
        'bridged_at'           => 'datetime',
        'ended_at'             => 'datetime',
        'bridged_seconds'      => 'integer',
    ];

    public function call(): BelongsTo
    {
        return $this->belongsTo(Call::class);
    }

    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }
}
