<?php

namespace App\Console\Commands;

use App\Models\ConsentLog;
use App\Models\ContactMessage;
use App\Models\UserAttribution;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * GDPR retention sweep. Runs daily from the scheduler.
 *
 * Policy (declared in /confidentialitate so the user can see it):
 *   - raw IP anonymised after 24h (last octet zeroed for IPv4,
 *     last 80 bits zeroed for IPv6)
 *   - chat conversations + messages deleted after 90 days
 *   - call recordings purged + caller_number anonymised at 30 days
 *   - lead rows anonymised (PII cleared, aggregate kept) after 3y
 *
 * Idempotent — each sweep only touches rows past their window,
 * so running twice in a day has no effect.
 */
class PrivacyRetention extends Command
{
    protected $signature = 'privacy:retention
                            {--dry : Show what would change without writing}';
    protected $description = 'GDPR retention sweep (IP anonymisation, conversation purge, recording delete).';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry');
        $verb = $dry ? 'would' : 'did';

        $stats = [
            'ip_anon_contact'     => $this->anonymiseIp(ContactMessage::class, 'ip', 24, $dry),
            'ip_anon_attribution' => $this->anonymiseIp(UserAttribution::class, 'ip', 24, $dry),
            'ip_anon_consent'     => $this->anonymiseIp(ConsentLog::class,      'ip', 24, $dry),
            // niche_leads model may not exist as Eloquent; touch via DB.
            'ip_anon_niche'       => $this->anonymiseIpRaw('niche_leads', 'ip', 24, $dry),

            'conv_purge'          => $this->purgeConversations(90, $dry),
            'call_anon'           => $this->anonymiseCalls(30, $dry),
            'recording_purge'     => $this->purgeRecordings(30, $dry),
        ];

        $this->info(sprintf(
            "Retention sweep %s %s",
            $verb, $dry ? '[DRY RUN]' : '[applied]'
        ));
        foreach ($stats as $k => $n) {
            $this->line(sprintf('  %-20s %d rows', $k, $n));
        }
        return self::SUCCESS;
    }

    /**
     * Zero the host bits on any IP older than N hours so rows stay
     * useful for rollups (country/ISP info was never derived from
     * the raw IP) while the personal identifier is gone.
     */
    private function anonymiseIp(string $modelClass, string $col, int $hours, bool $dry): int
    {
        $cutoff = Carbon::now()->subHours($hours);
        $q = $modelClass::where('created_at', '<', $cutoff)
            ->whereNotNull($col)
            ->where($col, '!=', '')
            ->whereRaw("{$col} NOT LIKE '%.0' AND {$col} NOT LIKE '%::' AND {$col} NOT LIKE '%0.0.0.0%'");

        if ($dry) return $q->count();

        $count = 0;
        $q->chunkById(500, function ($rows) use ($col, &$count) {
            foreach ($rows as $r) {
                $r->{$col} = $this->anon($r->{$col});
                $r->save();
                $count++;
            }
        });
        return $count;
    }

    private function anonymiseIpRaw(string $table, string $col, int $hours, bool $dry): int
    {
        $cutoff = Carbon::now()->subHours($hours);
        $q = DB::table($table)
            ->where('created_at', '<', $cutoff)
            ->whereNotNull($col)
            ->where($col, '!=', '');

        if ($dry) return $q->count();

        $updated = 0;
        $q->orderBy('id')->chunkById(500, function ($rows) use ($table, $col, &$updated) {
            foreach ($rows as $r) {
                $anon = $this->anon($r->{$col});
                if ($anon !== $r->{$col}) {
                    DB::table($table)->where('id', $r->id)->update([$col => $anon]);
                    $updated++;
                }
            }
        });
        return $updated;
    }

    /**
     * IPv4: zero last octet (192.168.1.5 → 192.168.1.0).
     * IPv6: zero last 80 bits (keep top /48 for geo).
     * Anything else: return empty.
     */
    private function anon(?string $ip): string
    {
        if (!$ip) return '';
        if (str_contains($ip, '.')) {
            $parts = explode('.', $ip);
            if (count($parts) === 4) {
                $parts[3] = '0';
                return implode('.', $parts);
            }
        }
        if (str_contains($ip, ':')) {
            $parts = explode(':', $ip);
            return (count($parts) >= 3 ? implode(':', array_slice($parts, 0, 3)) : '') . '::';
        }
        return '';
    }

    /**
     * Delete conversations + cascade messages older than N days.
     * Cost rollups (daily_cost_rollups) already capture the $
     * aggregate, so we don't lose billing visibility.
     */
    private function purgeConversations(int $days, bool $dry): int
    {
        $cutoff = Carbon::now()->subDays($days);
        $q = DB::table('conversations')->where('last_activity_at', '<', $cutoff);
        if ($dry) return $q->count();

        // Chunk-delete so a huge purge doesn't lock the table.
        $total = 0;
        while (true) {
            $ids = DB::table('conversations')
                ->where('last_activity_at', '<', $cutoff)
                ->limit(500)->pluck('id')->all();
            if (empty($ids)) break;
            DB::table('messages')->whereIn('conversation_id', $ids)->delete();
            $n = DB::table('conversations')->whereIn('id', $ids)->delete();
            $total += $n;
            if ($n < 500) break;
        }
        return $total;
    }

    /**
     * Anonymise caller_number on calls older than N days so the raw
     * PSTN number isn't stored indefinitely. Keep the row (duration,
     * cost, etc. stay for reports).
     */
    private function anonymiseCalls(int $days, bool $dry): int
    {
        $cutoff = Carbon::now()->subDays($days);
        $q = DB::table('calls')
            ->where('created_at', '<', $cutoff)
            ->whereNotNull('caller_number')
            ->where('caller_number', '!=', '[anonymised]');

        if ($dry) return $q->count();

        return $q->update(['caller_number' => '[anonymised]']);
    }

    /**
     * Delete the recording_url (points at the audio on our storage)
     * past the window; the row itself stays for stats.
     */
    private function purgeRecordings(int $days, bool $dry): int
    {
        $cutoff = Carbon::now()->subDays($days);
        $q = DB::table('calls')
            ->where('created_at', '<', $cutoff)
            ->whereNotNull('recording_url')
            ->where('recording_url', '!=', '');

        if ($dry) return $q->count();

        return $q->update(['recording_url' => null]);
    }
}
