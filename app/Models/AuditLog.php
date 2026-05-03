<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Înregistrare în audit log per tenant.
 *
 * @property int $id
 * @property int|null $tenant_id
 * @property int|null $user_id
 * @property string $action
 * @property string|null $auditable_type
 * @property int|null $auditable_id
 * @property array|null $changes
 * @property string|null $ip
 * @property string|null $user_agent
 * @property string|null $route
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 *
 * @property-read \App\Models\User|null $user
 * @property-read \Illuminate\Database\Eloquent\Model|null $auditable
 */
class AuditLog extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $table = 'audit_log';

    protected $fillable = [
        'tenant_id', 'user_id', 'action',
        'auditable_type', 'auditable_id',
        'changes', 'ip', 'user_agent', 'route',
    ];

    protected $casts = [
        'changes' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Etichetă RO pentru UI — derivată din action.
     */
    public function actionLabel(): string
    {
        return match ($this->action) {
            'bot.created'        => 'Agent AI creat',
            'bot.updated'        => 'Agent AI editat',
            'bot.deleted'        => 'Agent AI șters',
            'channel.created'    => 'Canal adăugat',
            'channel.updated'    => 'Canal actualizat',
            'channel.deleted'    => 'Canal eliminat',
            'phone_number.created' => 'Număr telefonic adăugat',
            'phone_number.deleted' => 'Număr telefonic eliminat',
            'site.created'       => 'Site adăugat',
            'site.updated'       => 'Site actualizat',
            'site.deleted'       => 'Site eliminat',
            'api_token.created'  => 'API key generat',
            'api_token.deleted'  => 'API key revocat',
            'knowledge_connector.created' => 'Conector adăugat',
            'knowledge_connector.deleted' => 'Conector eliminat',
            'webhook_endpoint.created' => 'Webhook configurat',
            'webhook_endpoint.updated' => 'Webhook actualizat',
            'webhook_endpoint.deleted' => 'Webhook eliminat',
            'user.invited'       => 'Coleg invitat',
            'user.removed'       => 'Coleg eliminat',
            default => str_replace(['_', '.'], [' ', ' · '], $this->action),
        };
    }
}
