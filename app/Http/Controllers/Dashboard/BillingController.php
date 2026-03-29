<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Services\PlanLimitService;

class BillingController extends Controller
{
    public function __construct(
        private PlanLimitService $planLimitService,
    ) {}

    public function index()
    {
        $tenant = auth()->user()->tenant;

        if (!$tenant) {
            return view('dashboard.billing.index', [
                'tenant' => null,
                'usage' => null,
                'webchatPlans' => collect(),
                'voicePlans' => collect(),
            ]);
        }

        $usage = $this->planLimitService->getUsageSummary($tenant);

        // Plans for upgrade comparison
        $webchatPlans = Plan::active()->webchat()->orderBy('sort_order')->get();
        $voicePlans = Plan::active()->voice()->orderBy('sort_order')->get();

        return view('dashboard.billing.index', compact(
            'tenant', 'usage', 'webchatPlans', 'voicePlans'
        ));
    }
}
