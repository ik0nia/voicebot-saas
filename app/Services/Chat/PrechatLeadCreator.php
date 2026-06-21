<?php

declare(strict_types=1);

namespace App\Services\Chat;

use App\Models\Bot;
use App\Models\Conversation;
use App\Models\Lead;
use Illuminate\Support\Facades\Log;

/**
 * Persists (or enriches) a Lead row from prechat form data submitted
 * by the widget before the user sent the first message.
 *
 * A prechat lead is always marked `qualified` — the user explicitly
 * handed over contact info, which is a stronger signal than what
 * we infer from chat text in auto-extraction. When a lead already
 * exists for the conversation (auto-extracted earlier), we only fill
 * in missing fields so the score never drops.
 *
 * The scoring table (email +30, phone +20, name +10, cap 100) is
 * kept aligned with {@see \App\Http\Controllers\Api\ChatbotApiController::tryExtractChatLead}
 * — diverging the two makes capture_source-level comparison in the
 * leads dashboard meaningless.
 */
final class PrechatLeadCreator
{
    private const SCORE_EMAIL = 30;
    private const SCORE_PHONE = 20;
    private const SCORE_NAME = 10;
    private const SCORE_MAX = 100;

    public function create(
        Bot $bot,
        Conversation $conversation,
        ?string $name,
        ?string $email,
        ?string $phone,
    ): void {
        try {
            $email = $this->normalizeEmail($email);
            $phone = $this->normalizePhone($phone);
            $name = $name !== null ? trim($name) : null;

            if ($email === null && $phone === null) {
                return;
            }

            // Dedup în ordine de specificitate: conversație curentă →
            // același vizitator (visitor_id, ultimele 30 zile pe același bot)
            // → email canonic → phone canonic. Reduce duplicate masive
            // pentru retail unde același vizitator are 5+ conversații.
            $existingLead = $this->findExistingLead($bot->id, $conversation, $email, $phone);
            if ($existingLead !== null) {
                $this->enrichExistingLead($existingLead, $name, $email, $phone);
                // Re-asociază lead-ul cu conversația curentă dacă era pe alta veche
                if ((int) $existingLead->conversation_id !== (int) $conversation->id) {
                    $existingLead->update(['conversation_id' => $conversation->id]);
                }
                return;
            }

            $score = ($email !== null ? self::SCORE_EMAIL : 0)
                + ($phone !== null ? self::SCORE_PHONE : 0)
                + ($name !== null ? self::SCORE_NAME : 0);

            $lead = Lead::create([
                'tenant_id' => $bot->tenant_id,
                'bot_id' => $bot->id,
                'conversation_id' => $conversation->id,
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'status' => 'qualified',
                'qualification_score' => $score,
                'capture_source' => 'chat',
                'capture_reason' => 'prechat_form',
            ]);

            \App\Events\LeadCaptured::dispatch($lead, 'prechat');

            Log::info("Prechat lead created for conversation {$conversation->id}", [
                'lead_id' => $lead->id,
                'has_email' => $email !== null,
                'has_phone' => $phone !== null,
                'has_name' => $name !== null,
            ]);

            $conversation->update([
                'lead_score' => max($conversation->lead_score ?? 0, $score),
            ]);
        } catch (\Throwable $e) {
            Log::debug("Prechat lead creation failed for conversation {$conversation->id}", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Găsește lead duplicat cross-conversation pentru același vizitator.
     * Ordinea de match contează: cea mai specifică prima.
     */
    private function findExistingLead(int $botId, Conversation $conversation, ?string $email, ?string $phone): ?Lead
    {
        // 1. Lead deja asociat cu conversația curentă
        $byConv = Lead::where('conversation_id', $conversation->id)->first();
        if ($byConv) return $byConv;

        // 2. Același visitor_id pe același bot, ultimele 30 zile
        if ($conversation->visitor_id) {
            $byVisitor = Lead::where('bot_id', $botId)
                ->whereHas('conversation', function ($q) use ($conversation) {
                    $q->where('visitor_id', $conversation->visitor_id);
                })
                ->where('created_at', '>=', now()->subDays(30))
                ->orderByDesc('id')
                ->first();
            if ($byVisitor) return $byVisitor;
        }

        // 3. Email match (canonic, lowercase) pe același bot
        if ($email) {
            $byEmail = Lead::where('bot_id', $botId)->where('email', $email)->orderByDesc('id')->first();
            if ($byEmail) return $byEmail;
        }

        // 4. Phone match (canonic 07xxxxxxxx) pe același bot
        if ($phone) {
            $byPhone = Lead::where('bot_id', $botId)->where('phone', $phone)->orderByDesc('id')->first();
            if ($byPhone) return $byPhone;
        }

        return null;
    }

    private function enrichExistingLead(Lead $lead, ?string $name, ?string $email, ?string $phone): void
    {
        $updates = [];
        if ($email !== null && !$lead->email) {
            $updates['email'] = $email;
        }
        if ($phone !== null && !$lead->phone) {
            $updates['phone'] = $phone;
        }
        if ($name !== null && !$lead->name) {
            $updates['name'] = $name;
        }
        if ($updates === []) {
            return;
        }

        $score = $lead->qualification_score;
        if (isset($updates['email'])) {
            $score += self::SCORE_EMAIL;
        }
        if (isset($updates['phone'])) {
            $score += self::SCORE_PHONE;
        }
        if (isset($updates['name'])) {
            $score += self::SCORE_NAME;
        }
        $updates['qualification_score'] = min(self::SCORE_MAX, $score);

        if (!$lead->email && !$lead->phone && ($email !== null || $phone !== null)) {
            $updates['status'] = 'qualified';
        }

        $lead->update($updates);
    }

    private function normalizeEmail(?string $email): ?string
    {
        if ($email === null) {
            return null;
        }
        $normalized = mb_strtolower(trim($email));
        return filter_var($normalized, FILTER_VALIDATE_EMAIL) ? $normalized : null;
    }

    /**
     * Accept only Romanian mobile formats (07xxxxxxxx / 407xxxxxxxx)
     * and normalize to the 07xxxxxxxx canonical representation.
     */
    private function normalizePhone(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }
        $raw = trim($phone);
        $digitsOnly = preg_replace('/[^\d]/', '', $raw);
        // RO mobile (07xxxxxxxx / 407xxxxxxxx) — canonic 07XXXXXXXX.
        if (preg_match('/^(07\d{8})$/', $digitsOnly)) {
            return $digitsOnly;
        }
        if (preg_match('/^(407\d{8})$/', $digitsOnly)) {
            return '0' . substr($digitsOnly, 2);
        }
        // International cu prefix +CC. Acceptăm 7-15 cifre după CC.
        // Format canonic: +CC + restul cifrelor (E.164-like, fără spații).
        if (preg_match('/^\+?(\d{1,3})(\d{6,14})$/', $digitsOnly, $m)) {
            $cc = $m[1];
            $rest = $m[2];
            if ($cc === '40' && str_starts_with($rest, '7') && strlen($rest) === 9) {
                return '0' . $rest;
            }
            return '+' . $cc . $rest;
        }
        return null;
    }
}
