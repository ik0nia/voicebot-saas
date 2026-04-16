<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContactMessage extends Model
{
    protected $fillable = [
        'name', 'email', 'phone', 'company', 'subject', 'message',
        'source', 'source_url',
        'utm_source', 'utm_medium', 'utm_campaign', 'gclid', 'fbclid',
        'ip', 'user_agent',
        'status', 'handled_at',
    ];

    protected $casts = [
        'handled_at' => 'datetime',
    ];

    public function replies(): HasMany
    {
        return $this->hasMany(ContactMessageReply::class)->orderBy('created_at');
    }
}
