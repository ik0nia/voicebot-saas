<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Bot;
use App\Models\Channel;
use App\Models\Tenant;
use App\Services\ChannelMessagingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ChannelMessagingServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeChannel(string $type, array $credentials = [], ?string $externalId = null): Channel
    {
        $tenant = Tenant::factory()->create();
        $bot = Bot::factory()->create(['tenant_id' => $tenant->id]);
        $channel = Channel::create([
            'bot_id' => $bot->id,
            'type' => $type,
            'name' => 'Test',
            'external_id' => $externalId ?? 'ext-' . uniqid(),
            'webhook_secret' => 'tk',
            'is_active' => true,
        ]);

        if (!empty($credentials)) {
            $channel->credentials = $credentials;
            $channel->save();
        }

        return $channel->fresh();
    }

    public function test_whatsapp_uses_channel_credentials_over_env(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.PROVIDER123']]], 200),
        ]);

        $channel = $this->makeChannel(Channel::TYPE_WHATSAPP, [
            'access_token' => 'channel-token',
            'phone_number_id' => '999',
        ]);

        $service = app(ChannelMessagingService::class);
        $result = $service->sendOnChannel($channel, '40700000000', 'Salut');

        $this->assertTrue($result['success']);
        $this->assertSame('wamid.PROVIDER123', $result['message_id']);
        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/999/messages')
                && $request->hasHeader('Authorization', 'Bearer channel-token');
        });
    }

    public function test_whatsapp_falls_back_to_external_id_when_phone_number_id_missing(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.X']]], 200),
        ]);

        $channel = $this->makeChannel(Channel::TYPE_WHATSAPP, [
            'access_token' => 'tk',
        ], externalId: 'phone-from-external');

        app(ChannelMessagingService::class)->sendOnChannel($channel, '4070', 'hi');

        Http::assertSent(fn ($req) => str_contains($req->url(), '/phone-from-external/messages'));
    }

    public function test_returns_error_when_no_credentials_anywhere(): void
    {
        config()->set('services.whatsapp.token', null);
        config()->set('services.whatsapp.phone_number_id', null);
        putenv('WHATSAPP_TOKEN');
        putenv('WHATSAPP_PHONE_NUMBER_ID');

        $channel = $this->makeChannel(Channel::TYPE_WHATSAPP);
        $channel->external_id = null;
        $channel->save();

        $result = app(ChannelMessagingService::class)->sendOnChannel($channel, '40', 'hi');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('not configured', $result['error']);
    }

    public function test_facebook_uses_channel_page_access_token(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['message_id' => 'mid.FB'], 200),
        ]);

        $channel = $this->makeChannel(Channel::TYPE_FACEBOOK_MESSENGER, [
            'page_access_token' => 'fb-page-token',
        ]);

        $result = app(ChannelMessagingService::class)->sendOnChannel($channel, 'PSID', 'salut');

        $this->assertTrue($result['success']);
        $this->assertSame('mid.FB', $result['message_id']);
        Http::assertSent(fn ($req) => $req->hasHeader('Authorization', 'Bearer fb-page-token'));
    }

    public function test_instagram_uses_channel_page_access_token(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['message_id' => 'mid.IG'], 200),
        ]);

        $channel = $this->makeChannel(Channel::TYPE_INSTAGRAM_DM, [
            'page_access_token' => 'ig-page-token',
        ]);

        $result = app(ChannelMessagingService::class)->sendOnChannel($channel, 'igid', 'hi');

        $this->assertTrue($result['success']);
        Http::assertSent(fn ($req) => $req->hasHeader('Authorization', 'Bearer ig-page-token'));
    }

    public function test_unsupported_channel_type_returns_error(): void
    {
        $channel = $this->makeChannel(Channel::TYPE_VOICE);

        $result = app(ChannelMessagingService::class)->sendOnChannel($channel, '40', 'hi');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Unsupported', $result['error']);
    }

    public function test_credentials_round_trip_via_helper(): void
    {
        $channel = $this->makeChannel(Channel::TYPE_WHATSAPP);
        $channel->setCredential('access_token', 'abc')->save();

        $reloaded = Channel::find($channel->id);
        $this->assertSame('abc', $reloaded->getCredential('access_token'));
        $this->assertNull($reloaded->getCredential('missing'));
        $this->assertSame('default', $reloaded->getCredential('missing', 'default'));
    }

    public function test_credentials_are_encrypted_in_database(): void
    {
        $channel = $this->makeChannel(Channel::TYPE_WHATSAPP, [
            'access_token' => 'super-secret',
        ]);

        // Bypass cast to read the raw column value.
        $raw = \DB::table('channels')->where('id', $channel->id)->value('credentials');

        $this->assertNotNull($raw);
        $this->assertStringNotContainsString('super-secret', $raw, 'plaintext token leaked');
    }
}
