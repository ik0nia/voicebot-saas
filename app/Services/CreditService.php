<?php

namespace App\Services;

use App\Models\CreditConsumption;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

/**
 * Atomic credit balance accounting. Used as fallback when a tenant
 * exceeds their plan's monthly quota — instead of being blocked, the
 * matching credit balance is decremented.
 *
 * The Tenant model holds the running totals (message_credits,
 * minute_credits, product_credits); credit_consumptions logs every
 * decrement for audit / customer history.
 */
class CreditService
{
    private const UNIT_TO_COLUMN = [
        'messages' => 'message_credits',
        'minutes' => 'minute_credits',
        'products' => 'product_credits',
    ];

    public function balance(Tenant $tenant, string $unit): int
    {
        $col = self::UNIT_TO_COLUMN[$unit] ?? null;
        if (! $col) {
            return 0;
        }
        return (int) ($tenant->{$col} ?? 0);
    }

    /**
     * Atomically decrement the credit balance for the tenant in the given
     * unit. Returns true if the consumption succeeded (sufficient balance),
     * false if the balance is insufficient (no decrement happened).
     */
    public function consume(Tenant $tenant, string $unit, int $quantity, string $source, ?string $referenceId = null): bool
    {
        if ($quantity <= 0) {
            return true;
        }
        $col = self::UNIT_TO_COLUMN[$unit] ?? null;
        if (! $col) {
            return false;
        }

        return DB::transaction(function () use ($tenant, $col, $unit, $quantity, $source, $referenceId) {
            // Lock the row so two concurrent consumers cannot both succeed
            // when only one credit is left.
            $locked = Tenant::query()->whereKey($tenant->id)->lockForUpdate()->first();
            if (! $locked) {
                return false;
            }
            $current = (int) ($locked->{$col} ?? 0);
            if ($current < $quantity) {
                return false;
            }
            $locked->decrement($col, $quantity);

            CreditConsumption::create([
                'tenant_id' => $locked->id,
                'unit' => $unit,
                'quantity' => $quantity,
                'source' => $source,
                'reference_id' => $referenceId,
            ]);

            return true;
        });
    }
}
