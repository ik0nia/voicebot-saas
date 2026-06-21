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
 * Twilio SMS inbound webhook. Convertește mesajul primit într-un mesaj de
 * conversație și apelează ChannelMessageService — același flow ca pentru
 * WhatsApp/Messenger.
 *
 * Twilio trimite POST x-www-form-urlencoded cu:
 *   From, To, Body, MessageSid, AccountSid, NumMedia, ...
 *
 * Verificare semnătură: middleware existent `VerifyTwilioSignature`
 * (registered global pentru webhook-uri Twilio).
 */
class TwilioSmsWebhookController extends Controller
{
    public function __construct(
        private ChannelMessageService $messageService,
    ) {}

    public function handle(Request $request)
    {
        try {
            $from = (string) $request->input('From', '');
            $to = (string) $request->input('To', '');
            $body = trim((string) $request->input('Body', ''));
            $messageSid = (string) $request->input('MessageSid', '');

            if ($from === '' || $to === '' || $body === '') {
                return $this->twiml('');
            }

            // Idempotency per MessageSid (24h cache).
            if ($messageSid !== '') {
                $cacheKey = 'twilio_sms_seen:' . $messageSid;
                if (Cache::has($cacheKey)) {
                    return $this->twiml('');
                }
                Cache::put($cacheKey, true, now()->addDay());
            }

            // Găsește channel după numărul To (numărul nostru).
            $channel = Channel::where('type', Channel::TYPE_SMS)
                ->where('external_id', $to)
                ->where('is_active', true)
                ->first();

            if (!$channel) {
                Log::warning('Twilio SMS channel not found', ['to' => $to]);
                return $this->twiml('');
            }

            // STOP / UNSUBSCRIBE handling (carrier compliance + GDPR).
            $normalized = mb_strtolower($body);
            if (in_array($normalized, ['stop', 'unsubscribe', 'cancel', 'renunt', 'renunț'], true)) {
                Log::info('SMS opt-out received', ['from' => $from, 'channel_id' => $channel->id]);
                return $this->twiml('Te-am scos de pe lista de mesaje. Răspunde START pentru a reactiva.');
            }

            $result = $this->messageService->processIncomingMessage(
                channel: $channel,
                contactId: $from,
                contactName: $from,
                messageText: $body,
            );

            $reply = (string) ($result['response'] ?? '');
            return $this->twiml($reply);

        } catch (\Throwable $e) {
            Log::error('Twilio SMS webhook error', ['error' => $e->getMessage()]);
            return $this->twiml('');
        }
    }

    /**
     * Răspuns TwiML — Twilio livrează direct ca SMS reply dacă <Message> e
     * setat, altfel doar acknowledge.
     */
    private function twiml(string $reply): \Illuminate\Http\Response
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?><Response>';
        if ($reply !== '') {
            $xml .= '<Message>' . htmlspecialchars(mb_substr($reply, 0, 1500), ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</Message>';
        }
        $xml .= '</Response>';
        return response($xml, 200, ['Content-Type' => 'text/xml']);
    }
}
