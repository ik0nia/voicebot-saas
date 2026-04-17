<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Location extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'bot_id', 'name', 'slug',
        'address_line', 'city', 'county', 'postal_code', 'country',
        'phone', 'timezone', 'latitude', 'longitude',
        'metadata', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'metadata'   => 'array',
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
        'latitude'   => 'float',
        'longitude'  => 'float',
    ];

    protected static function booted(): void
    {
        static::creating(function (Location $l) {
            if (empty($l->slug)) {
                $l->slug = Str::slug($l->name);
            }
        });
    }

    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function resources(): HasMany
    {
        return $this->hasMany(BookableResource::class);
    }
}
