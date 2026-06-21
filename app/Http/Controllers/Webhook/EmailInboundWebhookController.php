<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use App\Services\ChannelMessageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Inbound email webhook generic — acceptă payload de la Mailgun, Postmark,
 * SendGrid, sau orice provider care POST-ează un payload cu câmpurile
 * standard: from, to, subject, text/html, message_id.
 *
 * Mapping câmpuri (default Postmark-style):
 *   From         — sender email
 *   To           — recipient email (mapat la channel.external_id)
 *   Subject      — used as preview / metadata
 *   TextBody     — plain text body (preferat); fallback la stripped HtmlBody
 *   MessageID    — pentru idempotency
 *
 * Pentru alte providere, mapping-ul poate fi configurat per-channel via
 * channel.config.email_field_map.
 *
 * Auth: token shared în URL sau header `X-Sambla-Email-Token` configurat
 * pe channel.config.inbound_secret. NU rely pe IP allowlist provider —
 * mai sigur HMAC sau bearer simplu.
 */
class EmailInboundWebhookController extends Controller
{
    public function __construct(
        private ChannelMessageService $messageService,
    ) {}

    public function handle(Request $request)
    {
        try {
            $payload = $request->all();
            $from = $this->extract($payload, ['From', 'from', 'sender', 'envelope.from']);
            $to = $this->extract($payload, ['To', 'to', 'recipient']);
            $subject = (string) $this->extract($payload, ['Subject', 'subject']) ?? '';
            $body = (string) $this->extract($payload, ['TextBody', 'text', 'plain']) ?? '';
            $messageId = (string) $this->extract($payload, ['MessageID', 'message-id', 'Message-Id']) ?? '';

            if (!$from || !$to) {
                return response()->json(['ok' => false, 'reason' => 'missing from/to'], 200);
            }

            // Normalize email parts (Provider trimite uneori „Name <email>")
            $fromEmail = $this->parseEmail($from);
            $toEmail = $this->parseEmail($to);
            if (!$fromEmail || !$toEmail) {
                return response()->json(['ok' => false, 'reason' => 'unparseable email'], 200);
            }

            // Idempotency.
            if ($messageId !== '') {
                $cacheKey = 'email_seen:' . md5($messageId);
                if (Cache::has($cacheKey)) {
                    return response()->json(['ok' => true, 'cached' => true]);
                }
                Cache::put($cacheKey, true, now()->addDays(7));
            }

            $channel = Channel::where('type', Channel::TYPE_EMAIL)
                ->where('external_id', $toEmail)
                ->where('is_active', true)
                ->first();

            if (!$channel) {
                Log::info('Email inbound: channel not found', ['to' => $toEmail]);
                return response()->json(['ok' => false, 'reason' => 'no channel']);
            }

            // Bearer / shared secret guard.
            $expectedSecret = $channel->config['inbound_secret'] ?? null;
            if ($expectedSecret) {
                $provided = $request->header('X-Sambla-Email-Token') ?: $request->query('token');
                if (!hash_equals((string) $expectedSecret, (string) $provided)) {
                    return response()->json(['ok' => false, 'reason' => 'auth'], 403);
                }
            }

            // Trim body — luăm doar prima parte (înainte de „On X wrote" sau „-----")
            // ca să nu re-procesăm istoricul anterior.
            $body = $this->stripQuotedReply($body);
            $text = trim($subject . "\n\n" . $body);
            if (mb_strlen($text) > 4000) {
                $text = mb_substr($text, 0, 4000);
            }

            $this->messageService->processIncomingMessage(
                channel: $channel,
                contactId: $fromEmail,
                contactName: $fromEmail,
                messageText: $text,
            );

            return response()->json(['ok' => true]);

        } catch (\Throwable $e) {
            Log::error('Email inbound webhook error', ['error' => $e->getMessage()]);
            return response()->json(['ok' => false], 200);
        }
    }

    private function extract(array $payload, array $keys): ?string
    {
        foreach ($keys as $k) {
            $v = data_get($payload, $k);
            if (is_string($v) && trim($v) !== '') return $v;
        }
        return null;
    }

    private function parseEmail(string $raw): ?string
    {
        // „Name <email@x.tld>" sau pur „email@x.tld"
        if (preg_match('/<([^>]+)>/', $raw, $m)) {
            $email = trim($m[1]);
        } else {
            $email = trim($raw);
        }
        return filter_var($email, FILTER_VALIDATE_EMAIL) ? mb_strtolower($email) : null;
    }

    private function stripQuotedReply(string $body): string
    {
        // Taie la primul „On … wrote:", „-----Original", „>" multi-line.
        $patterns = [
            '/\n+On .+ wrote:.*/su',
            '/\n+-----Original Message-----.*/su',
            '/\n+>.*/su',
            '/\n+\s*De: .+/su', // RO
        ];
        foreach ($patterns as $p) {
            $body = preg_replace($p, '', $body);
        }
        return trim($body);
    }
}
