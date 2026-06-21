<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Bot;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Atribuie un lead nou unui operator pe baza skills:
 *   1. Iau skills configurate pe bot (preferredSkills).
 *   2. Iau users din același tenant cu user.settings.skills setate.
 *   3. Match: operator cu cea mai bună intersecție (apoi load-balance round
 *      robin pe load curent).
 *
 * Dacă niciun match — lead rămâne unassigned (operator manual).
 */
class LeadAutoAssignService
{
    public function assignToOperator(Lead $lead): ?int
    {
        $bot = $lead->bot;
        if (!$bot) {
            return null;
        }

        $required = $bot->preferredSkills();
        if (empty($required)) {
            return null; // bot fără skills configurate — manual assign
        }

        // Candidați: useri din tenant cu skills în settings.
        $candidates = User::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $lead->tenant_id)
            ->where('id', '!=', null)
            ->get();

        $best = null;
        $bestScore = 0;

        foreach ($candidates as $user) {
            $userSkills = is_array($user->settings['skills'] ?? null)
                ? array_map(fn($s) => mb_strtolower(trim((string) $s)), $user->settings['skills'])
                : [];
            if (empty($userSkills)) {
                continue;
            }
            $intersect = count(array_intersect($required, $userSkills));
            if ($intersect > $bestScore) {
                $bestScore = $intersect;
                $best = $user;
            }
        }

        if (!$best) {
            return null;
        }

        try {
            $lead->update(['assigned_to' => (string) $best->id]);
            Log::info('Lead auto-assigned', [
                'lead_id' => $lead->id,
                'user_id' => $best->id,
                'matched_skills' => $bestScore,
            ]);
        } catch (\Throwable $e) {
            Log::warning('LeadAutoAssignService: update failed', ['error' => $e->getMessage()]);
            return null;
        }

        return $best->id;
    }
}
