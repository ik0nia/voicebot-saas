<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\LeadCaptured;
use App\Models\User;
use App\Notifications\NewLeadCapturedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Trimite email tenant admins/managers când un lead nou e capturat.
 * Idempotency: cache 1h pe lead id ca să nu trimitem din nou dacă
 * event-ul e re-dispatched la edit ulterior.
 */
class SendNewLeadCapturedEmail implements ShouldQueue
{
    public function handle(LeadCaptured $event): void
    {
        $lead = $event->lead;
        $cacheKey = "lead_email_sent:{$lead->id}";
        if (\Cache::has($cacheKey)) {
            return;
        }

        // Auto-assign la operator pe baza skills (dacă bot are
        // preferred_skills configurate). Best-effort, fără să blocăm email.
        try {
            app(\App\Services\LeadAutoAssignService::class)->assignToOperator($lead);
        } catch (\Throwable $e) {
            Log::debug('LeadAutoAssign in listener failed', ['error' => $e->getMessage()]);
        }

        try {
            $recipients = User::query()
                ->withoutGlobalScopes()
                ->where('tenant_id', $lead->tenant_id)
                ->whereNotNull('email')
                ->where(function ($q) {
                    $q->whereHas('roles', function ($r) {
                        $r->whereIn('name', ['tenant_admin', 'tenant_manager']);
                    })->orWhereDoesntHave('roles');
                })
                ->limit(5)
                ->get();

            if ($recipients->isEmpty()) {
                Log::info('SendNewLeadCapturedEmail: no recipients', ['lead_id' => $lead->id, 'tenant_id' => $lead->tenant_id]);
                return;
            }

            Notification::send($recipients, new NewLeadCapturedNotification($lead, $event->source));
            \Cache::put($cacheKey, true, now()->addHours(24));
        } catch (\Throwable $e) {
            Log::warning('SendNewLeadCapturedEmail: failed', ['lead_id' => $lead->id, 'error' => $e->getMessage()]);
        }
    }
}
