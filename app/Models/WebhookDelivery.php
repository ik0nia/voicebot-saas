<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Istoricul unei livrări de webhook (succes sau eșec).
 *
 * NU folosește BelongsToTenant — scope-ul vine prin endpoint
 * (filtrăm WHERE webhook_endpoint_id IN (tenant's endpoints)).
 */
class WebhookDelivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'webhook_endpoint_id', 'event', 'payload',
        'attempt', 'succeeded',
        'response_code', 'response_body', 'error_message', 'duration_ms',
    ];

    protected $casts = [
        'payload' => 'array',
        'succeeded' => 'boolean',
    ];

    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(WebhookEndpoint::class, 'webhook_endpoint_id');
    }
}
