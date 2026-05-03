<?php

namespace App\Models;

use App\Models\Traits\Auditable;
use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Endpoint la care Sambla face POST pentru evenimente tenantului.
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $name
 * @property string $url
 * @property string $secret
 * @property array $events
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $last_success_at
 * @property \Illuminate\Support\Carbon|null $last_failure_at
 * @property int $failure_count
 *
 * @property-read \Illuminate\Database\Eloquent\Collection<int, WebhookDelivery> $deliveries
 */
class WebhookEndpoint extends Model
{
    use HasFactory;
    use BelongsToTenant;
    use Auditable;

    /** Lista canonică de evenimente disponibile. UI o folosește pentru
     *  checkbox-uri; dispatcher-ul filtrează endpoint-urile pe ele. */
    public const AVAILABLE_EVENTS = [
        'lead.created'         => 'Lead nou capturat',
        'callback.requested'   => 'Cerere de callback',
        'conversation.completed' => 'Conversație încheiată (chat)',
        'call.ended'           => 'Apel telefonic încheiat',
        'appointment.created'  => 'Programare creată',
    ];

    protected $fillable = [
        'tenant_id', 'name', 'url', 'secret', 'events', 'is_active',
        'last_success_at', 'last_failure_at', 'failure_count',
    ];

    protected $casts = [
        'events' => 'array',
        'is_active' => 'boolean',
        'last_success_at' => 'datetime',
        'last_failure_at' => 'datetime',
    ];

    protected $hidden = ['secret']; // never expose în API/JSON responses

    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class);
    }

    /** Generează secret cripto-safe pentru semnătura HMAC. */
    public static function generateSecret(): string
    {
        return 'whsec_' . Str::random(48);
    }

    public function auditExcludedAttributes(): array
    {
        return ['last_success_at', 'last_failure_at', 'failure_count', 'secret'];
    }
}
