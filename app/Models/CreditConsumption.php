<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreditConsumption extends Model
{
    protected $fillable = [
        'tenant_id', 'unit', 'quantity', 'source', 'reference_id',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];
}
