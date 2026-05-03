<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PushSubscription extends Model
{
    protected $fillable = [
        'user_id', 'endpoint', 'endpoint_hash',
        'p256dh', 'auth', 'user_agent', 'label', 'last_used_at',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Format pentru minishlink/web-push library.
     */
    public function toWebPushSubscription(): \Minishlink\WebPush\Subscription
    {
        return \Minishlink\WebPush\Subscription::create([
            'endpoint' => $this->endpoint,
            'publicKey' => $this->p256dh,
            'authToken' => $this->auth,
        ]);
    }
}
