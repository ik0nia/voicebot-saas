<?php

/*
|--------------------------------------------------------------------------
| Billing & cost-control knobs (Iteration B)
|--------------------------------------------------------------------------
|
| These settings control the per-tenant daily AI cost ceiling that fronts
| the public chatbot endpoints (ChatbotApiController::message /
| messageStream). They are additive: when the feature flag is off the
| ceiling is not enforced — existing flows continue unchanged.
|
| Flip `daily_cost_ceiling_enabled` to true once the metrics in
| `ai_api_metrics` are trusted and we've sampled real tenants' spend to
| pick a sensible default_daily_ai_limit_ron.
|
*/

return [

    /*
    | Feature flag. When false, DailyCostCeiling::canSpend() always
    | returns allowed=true. Controllers should still call it — the
    | overhead is a single boolean check — so rollout is a single env
    | flip with no code redeploy.
    */
    'daily_cost_ceiling_enabled' => env('BILLING_DAILY_COST_CEILING_ENABLED', false),

    /*
    | Fallback daily limit in RON when the tenant's plan does not expose
    | a `daily_ai_limit_ron` override. 10 RON ≈ $2/day which comfortably
    | covers a small free-plan conversation volume but caps runaway
    | spend from scripts / abuse on public webhook endpoints.
    */
    'default_daily_ai_limit_ron' => env('BILLING_DEFAULT_DAILY_AI_LIMIT_RON', 10.0),

];
