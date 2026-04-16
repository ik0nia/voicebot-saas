<?php

namespace Tests\Feature;

use App\Jobs\ProcessChannelMessage;
use App\Models\Bot;
use App\Models\Channel;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Meta (WhatsApp / Facebook / Instagram) webhook signatures have no
 * timestamp in them, so a captured payload is replayable indefinitely as
 * long as the app secret is unchanged. The in-controller dedupe on the
 * per-message id (wamid / mid) blocks two things at once:
 *
 *   1. Replay attacks — attacker resends a captured payload, every copy
 *      normally would dispatch another ProcessChannelMessage (real LLM
 *      spend, real DB writes). The cache Cache::add key short-circuits
 *      every copy after the first.
 *   2. Legitimate Meta retries — Meta retries delivery on any non-2xx or
 *      timeout. Before this change, a slow response caused duplicate
 *      bot replies to the same user message.
 *
 * The test spawns one WhatsApp channel, posts the same webhook twice,
 * and asserts exactly one job was dispatched.
 */
class MetaWebhookIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // The signature middleware is fail-closed on empty app_secret.
        config(['services.meta.app_secret' => 'test-app-secret']);
    }

    private function sign(string $payload): string
    {
        return 'sha256=' . hash_hmac('sha256', $payload, 'test-app-secret');
    }

    public function test_whatsapp_duplicate_message_dispatches_job_only_once(): void
    {
        Queue::fake();

        $tenant = Tenant::factory()->create();
        $bot = Bot::factory()->create(['tenant_id' => $tenant->id]);
        $channel = Channel::create([
            'bot_id' => $bot->id,
            'type' => Channel::TYPE_WHATSAPP,
            'name' => 'WA test',
            'external_id' => 'phone-123',
            'webhook_secret' => 'verify-token',
            'is_active' => true,
        ]);

        $payload = [
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'messaging_product' => 'whatsapp',
                        'metadata' => ['phone_number_id' => 'phone-123'],
                        'contacts' => [['profile' => ['name' => 'Ion']]],
                        'messages' => [[
                            'id' => 'wamid.ABCD123',
                            'from' => '40700000000',
                            'type' => 'text',
                            'text' => ['body' => 'salut'],
                        ]],
                    ],
                ]],
            ]],
        ];
        $body = json_encode($payload);

        $r1 = $this->call(
            'POST',
            '/webhook/whatsapp',
            [],
            [],
            [],
            ['HTTP_X-Hub-Signature-256' => $this->sign($body), 'CONTENT_TYPE' => 'application/json'],
            $body,
        );
        $r1->assertStatus(200);
        Queue::assertPushed(ProcessChannelMessage::class, 1);

        // Replay the exact same payload (same wamid) — second request must
        // be accepted at the signature layer but dedupe before dispatch.
        $r2 = $this->call(
            'POST',
            '/webhook/whatsapp',
            [],
            [],
            [],
            ['HTTP_X-Hub-Signature-256' => $this->sign($body), 'CONTENT_TYPE' => 'application/json'],
            $body,
        );
        $r2->assertStatus(200);
        Queue::assertPushed(ProcessChannelMessage::class, 1);
    }

    public function test_whatsapp_distinct_wamids_each_dispatch(): void
    {
        Queue::fake();

        $tenant = Tenant::factory()->create();
        $bot = Bot::factory()->create(['tenant_id' => $tenant->id]);
        Channel::create([
            'bot_id' => $bot->id,
            'type' => Channel::TYPE_WHATSAPP,
            'name' => 'WA test',
            'external_id' => 'phone-321',
            'webhook_secret' => 'verify-token',
            'is_active' => true,
        ]);

        $makeBody = fn (string $wamid) => json_encode([
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'messaging_product' => 'whatsapp',
                        'metadata' => ['phone_number_id' => 'phone-321'],
                        'contacts' => [['profile' => ['name' => 'Ion']]],
                        'messages' => [[
                            'id' => $wamid,
                            'from' => '40700000000',
                            'type' => 'text',
                            'text' => ['body' => 'mesaj'],
                        ]],
                    ],
                ]],
            ]],
        ]);

        foreach (['wamid.AAA', 'wamid.BBB'] as $wamid) {
            $body = $makeBody($wamid);
            $this->call(
                'POST',
                '/webhook/whatsapp',
                [],
                [],
                [],
                ['HTTP_X-Hub-Signature-256' => $this->sign($body), 'CONTENT_TYPE' => 'application/json'],
                $body,
            )->assertStatus(200);
        }

        Queue::assertPushed(ProcessChannelMessage::class, 2);
    }

    public function test_facebook_duplicate_message_dispatches_job_only_once(): void
    {
        Queue::fake();

        $tenant = Tenant::factory()->create();
        $bot = Bot::factory()->create(['tenant_id' => $tenant->id]);
        Channel::create([
            'bot_id' => $bot->id,
            'type' => Channel::TYPE_FACEBOOK_MESSENGER,
            'name' => 'FB test',
            'external_id' => 'page-123',
            'webhook_secret' => 'verify-token',
            'is_active' => true,
        ]);

        $payload = [
            'entry' => [[
                'messaging' => [[
                    'sender' => ['id' => 'psid-42'],
                    'recipient' => ['id' => 'page-123'],
                    'message' => ['mid' => 'm_xyz', 'text' => 'salut'],
                ]],
            ]],
        ];
        $body = json_encode($payload);
        $headers = ['HTTP_X-Hub-Signature-256' => $this->sign($body), 'CONTENT_TYPE' => 'application/json'];

        $this->call('POST', '/webhook/facebook', [], [], [], $headers, $body)->assertStatus(200);
        $this->call('POST', '/webhook/facebook', [], [], [], $headers, $body)->assertStatus(200);

        Queue::assertPushed(ProcessChannelMessage::class, 1);
    }

    public function test_instagram_duplicate_message_dispatches_job_only_once(): void
    {
        Queue::fake();

        $tenant = Tenant::factory()->create();
        $bot = Bot::factory()->create(['tenant_id' => $tenant->id]);
        Channel::create([
            'bot_id' => $bot->id,
            'type' => Channel::TYPE_INSTAGRAM_DM,
            'name' => 'IG test',
            'external_id' => 'ig-999',
            'webhook_secret' => 'verify-token',
            'is_active' => true,
        ]);

        $payload = [
            'entry' => [[
                'messaging' => [[
                    'sender' => ['id' => 'igsid-1'],
                    'recipient' => ['id' => 'ig-999'],
                    'message' => ['mid' => 'ig_mid_1', 'text' => 'salut'],
                ]],
            ]],
        ];
        $body = json_encode($payload);
        $headers = ['HTTP_X-Hub-Signature-256' => $this->sign($body), 'CONTENT_TYPE' => 'application/json'];

        $this->call('POST', '/webhook/instagram', [], [], [], $headers, $body)->assertStatus(200);
        $this->call('POST', '/webhook/instagram', [], [], [], $headers, $body)->assertStatus(200);

        Queue::assertPushed(ProcessChannelMessage::class, 1);
    }

    public function test_dedupe_key_is_scoped_per_channel(): void
    {
        // If two different channels happened to receive a message with the
        // same mid (unlikely but possible cross-tenant), the dedupe key must
        // not collide — key format includes channel id.
        Queue::fake();
        Cache::flush();

        $tenant = Tenant::factory()->create();
        $bot = Bot::factory()->create(['tenant_id' => $tenant->id]);
        $channelA = Channel::create([
            'bot_id' => $bot->id,
            'type' => Channel::TYPE_WHATSAPP,
            'name' => 'A',
            'external_id' => 'phone-A',
            'webhook_secret' => 'tok-a',
            'is_active' => true,
        ]);
        $channelB = Channel::create([
            'bot_id' => $bot->id,
            'type' => Channel::TYPE_WHATSAPP,
            'name' => 'B',
            'external_id' => 'phone-B',
            'webhook_secret' => 'tok-b',
            'is_active' => true,
        ]);

        $makeBody = fn (string $phoneId) => json_encode([
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'messaging_product' => 'whatsapp',
                        'metadata' => ['phone_number_id' => $phoneId],
                        'contacts' => [['profile' => ['name' => 'Ion']]],
                        'messages' => [[
                            'id' => 'wamid.SHARED',
                            'from' => '40700000000',
                            'type' => 'text',
                            'text' => ['body' => 'x'],
                        ]],
                    ],
                ]],
            ]],
        ]);

        $bodyA = $makeBody('phone-A');
        $bodyB = $makeBody('phone-B');

        $this->call(
            'POST',
            '/webhook/whatsapp',
            [],
            [],
            [],
            ['HTTP_X-Hub-Signature-256' => $this->sign($bodyA), 'CONTENT_TYPE' => 'application/json'],
            $bodyA,
        )->assertStatus(200);

        $this->call(
            'POST',
            '/webhook/whatsapp',
            [],
            [],
            [],
            ['HTTP_X-Hub-Signature-256' => $this->sign($bodyB), 'CONTENT_TYPE' => 'application/json'],
            $bodyB,
        )->assertStatus(200);

        Queue::assertPushed(ProcessChannelMessage::class, 2);
    }
}
