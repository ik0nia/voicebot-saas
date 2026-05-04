<?php

declare(strict_types=1);

namespace App\Services\Chat;

use App\Models\Bot;
use App\Models\Conversation;
use App\Models\Lead;
use App\Models\Message;
use App\Services\ConversationEventService;
use App\Services\EventTaxonomy;
use Illuminate\Support\Facades\Log;

/**
 * Scans an inbound user turn for contact data (email, phone, name) and
 * persists a Lead row when enough is found. Runs every turn — cheap
 * regex work, no LLM call — so the moment a visitor types
 * "ma cheama Maria, numarul meu e 0722..." we have a qualified lead
 * without another round-trip to the model.
 *
 * Rules (aligned with PrechatLeadCreator to keep capture-source
 * analytics comparable):
 *   - email: lowercased, basic RFC format match
 *   - phone: Romanian mobile 07xxxxxxxx or 407xxxxxxxx, with forgiving
 *     spacing/punctuation; canonicalised to 07xxxxxxxx
 *   - name: captured after "mă numesc"/"sunt"/"mă cheamă"/etc.
 *   - contact-info alone → always create (status=qualified)
 *   - only-a-name → require bot context (last three outbound messages
 *     mention contact/email/telefon/etc.) OR lead_score ≥ 20
 *   - scoring: email +30, phone +20, name +10, cap 100
 *
 * Re-running on a conversation that already has a qualified lead
 * enriches it with missing fields and bumps the score, never
 * downgrades. Silent on errors — this runs in the hot path and
 * must not break the /message response on a regex glitch.
 */
final class ChatLeadExtractor
{
    private const SCORE_EMAIL = 30;
    private const SCORE_PHONE = 20;
    private const SCORE_NAME = 10;
    private const SCORE_MAX = 100;
    private const LEAD_SCORE_THRESHOLD_NAME_ONLY = 20;
    private const RECENT_BOT_MESSAGES_LOOKBACK = 3;

    public function __construct(
        private readonly ConversationEventService $eventService,
    ) {}

    /**
     * @param array<int, array<string, mixed>> $products
     * @param array<string, mixed>             $eventCtx When provided,
     *                                                   a LEAD_COMPLETED
     *                                                   event is tracked
     *                                                   alongside the
     *                                                   insert.
     */
    public function extract(
        Bot $bot,
        Conversation $conversation,
        string $userMessage,
        array $products,
        array $eventCtx = [],
    ): void {
        try {
            $email = $this->extractEmail($userMessage);
            $phone = $this->extractPhone($userMessage);
            $name = $this->extractName($userMessage);

            if ($email === null && $phone === null && $name === null) {
                return;
            }

            // Dedup în ordine de specificitate: conversație curentă →
            // același vizitator (visitor_id, ultimele 30 zile) → email
            // canonic → phone canonic. Dacă găsim, enrich + re-asociem
            // lead-ul cu conversația curentă.
            $existingLead = $this->findExistingLead($bot->id, $conversation, $email, $phone);

            if ($existingLead !== null) {
                $this->enrichExistingLead($existingLead, $conversation, $name, $email, $phone);
                if ((int) $existingLead->conversation_id !== (int) $conversation->id) {
                    $existingLead->update(['conversation_id' => $conversation->id]);
                }
                return;
            }

            // Hard rule: a lead requires actual reachability (email or
            // phone). A name alone — even matched against a "mă numesc"
            // pattern — isn't a lead; it's just a chat where someone
            // said hello. Previously we created status=partial leads
            // for name-only matches when the bot had asked for contact
            // or lead_score was high; that produced unreachable junk
            // ("cuie", "costyrile", "convins" — words mid-sentence that
            // happened to follow a trigger word like "Sunt").
            $hasContact = $email !== null || $phone !== null;
            if (!$hasContact) {
                return;
            }

            $score = self::SCORE_EMAIL * ($email !== null ? 1 : 0)
                + self::SCORE_PHONE * ($phone !== null ? 1 : 0)
                + self::SCORE_NAME * ($name !== null ? 1 : 0);

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
                'capture_reason' => 'contact_info_provided',
                'products_shown' => $this->productsShownFromMemory($conversation),
            ]);

            Log::info("Chat lead auto-captured for conversation {$conversation->id}", [
                'lead_id' => $lead->id,
                'has_email' => $email !== null,
                'has_phone' => $phone !== null,
                'has_name' => $name !== null,
            ]);

            if (!empty($eventCtx)) {
                $this->eventService->track(
                    EventTaxonomy::LEAD_COMPLETED,
                    [
                        'lead_id' => $lead->id,
                        'source' => 'chat',
                        'has_email' => $email !== null,
                        'has_phone' => $phone !== null,
                        'has_name' => $name !== null,
                    ],
                    array_merge($eventCtx, [
                        'idempotency_key' => "chat_lead:{$conversation->id}:{$lead->id}",
                    ]),
                );
            }

            $conversation->update([
                'lead_score' => max($conversation->lead_score ?? 0, $score),
            ]);
        } catch (\Throwable $e) {
            Log::debug("Chat lead extraction failed for conversation {$conversation->id}", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function extractEmail(string $userMessage): ?string
    {
        if (preg_match('/[\w.+-]+@[\w.-]+\.\w{2,}/', $userMessage, $m)) {
            return mb_strtolower($m[0]);
        }
        return null;
    }

    private function extractPhone(string $userMessage): ?string
    {
        $digitsOnly = preg_replace('/[^\d]/', '', $userMessage);
        if (preg_match('/(07\d{8})/', $digitsOnly, $m)) {
            return $m[1];
        }
        if (preg_match('/(407\d{8})/', $digitsOnly, $m)) {
            return '0' . substr(preg_replace('/\D/', '', $m[1]), 2);
        }
        // Tolerate spaced/punctuated layouts like "07xx xxx xxx".
        if (preg_match('/0\s*7[\s.-]?\d[\s.-]?\d[\s.-]?\d[\s.-]?\d[\s.-]?\d[\s.-]?\d[\s.-]?\d[\s.-]?\d/', $userMessage, $m)) {
            return preg_replace('/[\s.-]/', '', $m[0]);
        }
        return null;
    }

    private function extractName(string $userMessage): ?string
    {
        $pattern = '/(?:mă numesc|ma numesc|sunt|numele meu e|numele meu este|eu sunt|mă cheamă|ma cheama)\s+([A-ZĂÂÎȘȚ][a-zăâîșț]+(?:\s+[A-ZĂÂÎȘȚ][a-zăâîșț]+)?)/ui';
        if (preg_match($pattern, $userMessage, $m)) {
            return trim($m[1]);
        }
        return null;
    }

    /**
     * Găsește lead duplicat cross-conversation pentru același vizitator.
     * Ordinea: conversație curentă → visitor_id (30d) → email → phone.
     * Aceeași logică ca PrechatLeadCreator::findExistingLead.
     */
    private function findExistingLead(int $botId, Conversation $conversation, ?string $email, ?string $phone): ?Lead
    {
        $byConv = Lead::where('conversation_id', $conversation->id)
            ->where('status', 'qualified')
            ->first();
        if ($byConv) return $byConv;

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

        if ($email) {
            $byEmail = Lead::where('bot_id', $botId)->where('email', $email)->orderByDesc('id')->first();
            if ($byEmail) return $byEmail;
        }

        if ($phone) {
            $byPhone = Lead::where('bot_id', $botId)->where('phone', $phone)->orderByDesc('id')->first();
            if ($byPhone) return $byPhone;
        }

        return null;
    }

    private function enrichExistingLead(
        Lead $lead,
        Conversation $conversation,
        ?string $name,
        ?string $email,
        ?string $phone,
    ): void {
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

        $lead->update($updates);

        Log::info("Chat lead updated for conversation {$conversation->id}", [
            'lead_id' => $lead->id,
            'new_fields' => array_keys($updates),
        ]);
    }

    /**
     * Fix C: inspect the last few outbound turns, not just the most
     * recent one — if the bot asked for contact info two turns ago and
     * the user only now complied (mentioning just their name), we
     * still want to treat that as a bot-prompted capture.
     */
    private function recentBotMessagesAskedForContact(Conversation $conversation): bool
    {
        $recent = Message::where('conversation_id', $conversation->id)
            ->where('direction', 'outbound')
            ->orderByDesc('id')
            ->limit(self::RECENT_BOT_MESSAGES_LOOKBACK)
            ->pluck('content');

        $needles = ['email', 'telefon', 'contact', 'număr', 'numar', 'adresa ta', 'date de contact'];

        return $recent->contains(function ($msg) use ($needles): bool {
            if (!$msg) {
                return false;
            }
            foreach ($needles as $needle) {
                if (str_contains($msg, $needle)) {
                    return true;
                }
            }
            return false;
        });
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    private function productsShownFromMemory(Conversation $conversation): ?array
    {
        $lastCards = ($conversation->metadata ?? [])['last_product_cards'] ?? null;
        if (empty($lastCards)) {
            return null;
        }

        return array_map(
            fn ($p) => [
                'id' => $p['id'] ?? null,
                'name' => $p['name'] ?? '',
                'price' => $p['price'] ?? '',
                'currency' => $p['currency'] ?? 'RON',
            ],
            array_slice($lastCards, 0, 10),
        );
    }
}
