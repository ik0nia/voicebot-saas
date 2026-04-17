<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BookableResource extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'bot_id', 'department_id',
        'kind', 'name', 'role', 'bio', 'avatar_url',
        'capacity', 'service_type_ids',
        'legacy_staff_member_id', 'legacy_resource_inventory_id',
        'external_calendar_provider', 'external_calendar_id',
        'external_refresh_token', 'external_last_synced_at',
        'metadata', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'service_type_ids' => 'array',
        'metadata' => 'array',
        'capacity' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'external_last_synced_at' => 'datetime',
    ];

    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function availabilityRules(): HasMany
    {
        return $this->hasMany(ResourceAvailabilityRule::class);
    }

    public function availabilityBlocks(): HasMany
    {
        return $this->hasMany(ResourceAvailabilityBlock::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function canHandle(ServiceType $service): bool
    {
        if (empty($this->service_type_ids)) return true;
        return in_array($service->id, $this->service_type_ids, true);
    }
}
