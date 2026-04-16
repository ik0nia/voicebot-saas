<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\TwilioService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Twilio\Exceptions\RestException;
use Twilio\Rest\Client;

/**
 * Aggregated Twilio usage per tenant.
 *
 * Reads directly from Twilio's Usage Records API for each subaccount
 * we've provisioned. Gives the operator a per-client cost / minutes /
 * message count breakdown without leaving the Sambla admin — the
 * Twilio console equivalent requires drilling into each subaccount
 * individually.
 *
 * Results cached for 5 minutes (USAGE_CACHE_TTL) because the Usage
 * API costs billable API calls and the numbers only change every few
 * minutes on Twilio's side anyway.
 */
class TwilioUsageController extends Controller
{
    private const USAGE_CACHE_TTL = 300; // seconds

    public function index(Request $request)
    {
        $category = $request->get('category', 'totalprice');
        $window = $request->get('window', 'today');

        $tenants = Tenant::whereNotNull('telephony_subaccount_sid')
            ->orderBy('name')
            ->get();

        $rows = [];
        foreach ($tenants as $tenant) {
            try {
                $rows[] = array_merge(
                    ['tenant_id' => $tenant->id, 'tenant_name' => $tenant->name],
                    $this->subaccountUsage($tenant, $window),
                );
            } catch (\Throwable $e) {
                // Per-tenant failure must not break the whole page;
                // surface the error inline so the operator sees which
                // subaccount is mis-configured.
                Log::warning('TwilioUsageController: subaccount fetch failed', [
                    'tenant_id' => $tenant->id,
                    'error' => $e->getMessage(),
                ]);
                $rows[] = [
                    'tenant_id' => $tenant->id,
                    'tenant_name' => $tenant->name,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return view('admin.twilio_usage', compact('rows', 'category', 'window'));
    }

    /**
     * Pull usage records for a single subaccount over the requested
     * window. Twilio's usage API gives counts + USD totals per
     * category; we return a flattened dict keyed by category.
     */
    private function subaccountUsage(Tenant $tenant, string $window): array
    {
        $cacheKey = "twilio_usage:{$tenant->id}:{$window}";
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $client = new Client(
            $tenant->telephony_subaccount_sid,
            $tenant->telephony_subaccount_auth_token,
        );

        // Twilio's sub-resources on the usage.records endpoint map
        // directly onto the windows we want.
        $accessor = match ($window) {
            'yesterday' => $client->usage->records->yesterday,
            'thisMonth' => $client->usage->records->thisMonth,
            'lastMonth' => $client->usage->records->lastMonth,
            'allTime'   => $client->usage->records,
            default     => $client->usage->records->today,
        };

        $records = $accessor->read();
        $out = [
            'calls_seconds' => 0,
            'calls_price_usd' => 0.0,
            'inbound_seconds' => 0,
            'inbound_price_usd' => 0.0,
            'outbound_seconds' => 0,
            'outbound_price_usd' => 0.0,
            'sms_count' => 0,
            'sms_price_usd' => 0.0,
            'total_price_usd' => 0.0,
            'numbers_owned' => 0,
        ];

        foreach ($records as $r) {
            $usd = (float) ($r->price ?? 0);
            $count = (int) ($r->count ?? 0);
            $usage = (float) ($r->usage ?? 0);
            switch ($r->category) {
                case 'calls':
                    $out['calls_seconds'] += $usage;
                    $out['calls_price_usd'] += $usd;
                    break;
                case 'calls-inbound':
                    $out['inbound_seconds'] += $usage;
                    $out['inbound_price_usd'] += $usd;
                    break;
                case 'calls-outbound':
                    $out['outbound_seconds'] += $usage;
                    $out['outbound_price_usd'] += $usd;
                    break;
                case 'sms':
                    $out['sms_count'] += $count;
                    $out['sms_price_usd'] += $usd;
                    break;
                case 'totalprice':
                    $out['total_price_usd'] += $usd;
                    break;
                case 'phonenumbers':
                    $out['numbers_owned'] += $count;
                    break;
            }
        }

        Cache::put($cacheKey, $out, self::USAGE_CACHE_TTL);
        return $out;
    }
}
