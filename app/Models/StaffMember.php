<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StaffMember extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'bot_id', 'user_id', 'name', 'role',
        'email', 'phone', 'bio', 'avatar_url',
        'service_type_ids',
        'google_calendar_id', 'google_refresh_token',
        'is_active', 'sort_order',
    ];

    protected $casts = [
        'service_type_ids' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function workingHours(): HasMany
    {
        return $this->hasMany(WorkingHour::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Does this staff member deliver the given service?
     * Null service_type_ids → solo operator handles everything.
     */
    public function canHandle(ServiceType $service): bool
    {
        if (empty($this->service_type_ids)) return true;
        return in_array($service->id, $this->service_type_ids, true);
    }
}
