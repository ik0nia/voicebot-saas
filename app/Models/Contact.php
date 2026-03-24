<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contact extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'phone',
        'whatsapp_id',
        'facebook_psid',
        'instagram_id',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    /**
     * Find or create a contact by channel identifier.
     */
    public static function findOrCreateByChannel(int $tenantId, string $channel, string $channelId, string $name = ''): self
    {
        $field = match ($channel) {
            'whatsapp' => 'whatsapp_id',
            'facebook' => 'facebook_psid',
            'instagram' => 'instagram_id',
            default => 'phone',
        };

        return static::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where($field, $channelId)
            ->first()
            ?? static::create([
                'tenant_id' => $tenantId,
                $field => $channelId,
                'name' => $name,
            ]);
    }
}
