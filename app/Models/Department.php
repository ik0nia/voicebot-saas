<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Department extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'bot_id', 'name', 'slug', 'description',
        'is_active', 'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (Department $d) {
            if (empty($d->slug)) {
                $d->slug = Str::slug($d->name);
            }
        });
    }

    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }

    public function resources(): HasMany
    {
        return $this->hasMany(BookableResource::class);
    }
}
