<?php

namespace App\Observers;

use App\Models\AdminAuditLog;
use App\Models\Plan;

class PlanObserver
{
    public function created(Plan $plan): void
    {
        AdminAuditLog::record('plan.created', $plan, $plan->only([
            'name', 'slug', 'type', 'price_monthly', 'price_yearly', 'is_active', 'tenant_id',
        ]));
    }

    public function updated(Plan $plan): void
    {
        // Record only the actually changed fields so the log is useful.
        $dirty = $plan->getChanges();
        unset($dirty['updated_at']);
        if (empty($dirty)) {
            return;
        }

        $original = array_intersect_key($plan->getOriginal(), $dirty);
        $diff = [];
        foreach ($dirty as $field => $newValue) {
            $diff[$field] = [$original[$field] ?? null, $newValue];
        }
        AdminAuditLog::record('plan.updated', $plan, $diff);
    }

    public function deleted(Plan $plan): void
    {
        AdminAuditLog::record('plan.deleted', $plan, ['slug' => $plan->slug, 'name' => $plan->name]);
    }
}
