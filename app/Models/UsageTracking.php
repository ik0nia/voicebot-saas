<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class UsageTracking extends Model
{
    use BelongsToTenant;

    protected $table = 'usage_tracking';

    protected $fillable = [
        'tenant_id',
        'period',
        'feature',
        'value',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'integer',
        ];
    }

    // ─── Metrici disponibile ───

    public const FEATURE_AGENT_RUNS = 'agent_runs';
    public const FEATURE_TOKENS_USED = 'tokens_used';
    public const FEATURE_PAGES_SCANNED = 'pages_scanned';
    public const FEATURE_MESSAGES = 'messages';
    public const FEATURE_VOICE_MINUTES = 'voice_minutes';

    // ═══════════════════════════════════════════════════════════════
    //  Static helpers
    // ═══════════════════════════════════════════════════════════════

    /**
     * Incrementează valoarea unui feature pentru un tenant într-o perioadă.
     * Dacă nu există rândul, îl creează cu value=0 și apoi incrementează.
     *
     * @param int    $tenantId  ID-ul tenant-ului
     * @param string $period    Perioada în format 'YYYY-MM' (ex: '2026-03')
     * @param string $feature   Numele feature-ului (ex: 'agent_runs')
     * @param int    $amount    Cu cât se incrementează (default: 1)
     */
    public static function incrementUsage(int $tenantId, string $period, string $feature, int $amount = 1): self
    {
        $record = static::firstOrCreate(
            [
                'tenant_id' => $tenantId,
                'period' => $period,
                'feature' => $feature,
            ],
            ['value' => 0]
        );

        $record->increment('value', $amount);

        return $record;
    }

    /**
     * Returnează valoarea curentă a unui feature pentru un tenant în luna curentă.
     *
     * @param int    $tenantId  ID-ul tenant-ului
     * @param string $feature   Numele feature-ului (ex: 'agent_runs')
     * @return int
     */
    public static function getCurrentValue(int $tenantId, string $feature): int
    {
        $period = now()->format('Y-m');

        return (int) (static::where('tenant_id', $tenantId)
            ->where('period', $period)
            ->where('feature', $feature)
            ->value('value') ?? 0);
    }

    /**
     * Returnează valoarea unui feature pentru o perioadă specifică.
     */
    public static function getValueForPeriod(int $tenantId, string $feature, string $period): int
    {
        return (int) (static::where('tenant_id', $tenantId)
            ->where('period', $period)
            ->where('feature', $feature)
            ->value('value') ?? 0);
    }

    /**
     * Returnează toate valorile pentru un tenant într-o perioadă dată.
     */
    public static function getAllForPeriod(int $tenantId, string $period): array
    {
        return static::where('tenant_id', $tenantId)
            ->where('period', $period)
            ->pluck('value', 'feature')
            ->toArray();
    }

    /**
     * Cleanup vechi: păstrăm N luni de istoric.
     * Nu e nevoie de cron pentru resetare lunară - fiecare lună are propria perioadă.
     * Când se schimbă luna, firstOrCreate creează automat un rând nou cu value=0.
     */
    public static function cleanupOldRecords(int $monthsToKeep = 12): int
    {
        $cutoff = now()->subMonths($monthsToKeep)->format('Y-m');

        return static::where('period', '<', $cutoff)->delete();
    }
}
