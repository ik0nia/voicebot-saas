<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ServiceType extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'bot_id', 'name', 'slug', 'description',
        'duration_minutes', 'buffer_minutes', 'price', 'currency',
        'is_urgent', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'duration_minutes' => 'integer',
        'buffer_minutes' => 'integer',
        'price' => 'decimal:2',
        'is_urgent' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (ServiceType $s) {
            if (empty($s->slug)) {
                $s->slug = Str::slug($s->name);
            }
        });
    }

    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}
