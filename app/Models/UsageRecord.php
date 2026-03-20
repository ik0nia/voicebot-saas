<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsageRecord extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'type',
        'quantity',
        'unit_cost_cents',
        'recorded_at',
        'period_start',
        'period_end',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'recorded_at' => 'datetime',
            'period_start' => 'date',
            'period_end' => 'date',
        ];
    }
}
