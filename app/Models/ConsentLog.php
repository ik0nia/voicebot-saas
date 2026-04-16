<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsentLog extends Model
{
    protected $fillable = [
        'user_id', 'tenant_id', 'session_id', 'source',
        'consent', 'ip', 'user_agent', 'page',
    ];

    protected $casts = [
        'consent' => 'array',
    ];
}
