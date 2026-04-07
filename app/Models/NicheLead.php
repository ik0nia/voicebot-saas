<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NicheLead extends Model
{
    use HasFactory;

    protected $table = 'niche_leads';

    protected $fillable = [
        'niche_id',
        'name',
        'business_name',
        'email',
        'phone',
        'message',
        'source_url',
        'ip',
    ];

    public function niche(): BelongsTo
    {
        return $this->belongsTo(Niche::class);
    }
}
