<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantMcpServer extends Model
{
    use BelongsToTenant, HasFactory;

    public const TRANSPORTS = ['http', 'sse'];
    // 'stdio' is excluded for now — it requires forking a subprocess per
    // request which doesn't fit our PHP-FPM model. Add when we have a
    // worker pool to manage long-lived stdio processes.

    protected $fillable = [
        'name',
        'url',
        'transport',
        'credentials',
        'is_active',
        'last_health_check_at',
        'last_health_status',
        'tools_cache',
        'tools_cached_at',
    ];

    protected $hidden = [
        'credentials',
    ];

    protected $casts = [
        'credentials' => 'encrypted:array',
        'is_active' => 'boolean',
        'tools_cache' => 'array',
        'last_health_check_at' => 'datetime',
        'tools_cached_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Authorization header value for outbound MCP calls. Tenants can
     * configure either bearer-token, basic-auth, or no auth at all.
     */
    public function authHeader(): ?string
    {
        $type = $this->credentials['auth_type'] ?? null;
        if ($type === 'bearer' && !empty($this->credentials['token'])) {
            return 'Bearer ' . $this->credentials['token'];
        }
        if ($type === 'basic' && !empty($this->credentials['username'])) {
            $pair = $this->credentials['username'] . ':' . ($this->credentials['password'] ?? '');
            return 'Basic ' . base64_encode($pair);
        }
        return null;
    }
}
