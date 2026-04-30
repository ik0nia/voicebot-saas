<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Bot;
use App\Models\Channel;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChannelTenantScopingTest extends TestCase
{
    use RefreshDatabase;

    public function test_channel_create_derives_tenant_id_from_bot_when_unauthenticated(): void
    {
        $tenant = Tenant::factory()->create();
        $bot = Bot::factory()->create(['tenant_id' => $tenant->id]);

        $channel = Channel::create([
            'bot_id' => $bot->id,
            'type' => Channel::TYPE_WHATSAPP,
            'name' => 'WA test',
            'external_id' => 'phone-derive-test',
            'webhook_secret' => 'token',
            'is_active' => true,
        ]);

        $this->assertSame($tenant->id, $channel->tenant_id, 'Channel should auto-derive tenant_id from bot');
    }

    public function test_channel_factory_sets_tenant_id_via_bot(): void
    {
        $channel = Channel::factory()->whatsapp()->create();

        $this->assertNotNull($channel->tenant_id);
        $this->assertSame($channel->bot->tenant_id, $channel->tenant_id);
    }

    public function test_authenticated_user_only_sees_own_tenant_channels(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();
        $botA = Bot::factory()->create(['tenant_id' => $tenantA->id]);
        $botB = Bot::factory()->create(['tenant_id' => $tenantB->id]);

        Channel::create(['bot_id' => $botA->id, 'type' => 'whatsapp', 'external_id' => 'A', 'webhook_secret' => 'a']);
        Channel::create(['bot_id' => $botB->id, 'type' => 'whatsapp', 'external_id' => 'B', 'webhook_secret' => 'b']);

        $userA = User::factory()->create(['tenant_id' => $tenantA->id]);
        $this->actingAs($userA);

        $visible = Channel::all();
        $this->assertCount(1, $visible);
        $this->assertSame('A', $visible->first()->external_id);
    }

    public function test_webhook_lookup_runs_unauthenticated_and_sees_all_channels(): void
    {
        // Webhooks have no auth context; TenantScope skips filtering.
        // This test characterizes that behavior so we don't regress it.
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();
        $botA = Bot::factory()->create(['tenant_id' => $tenantA->id]);
        $botB = Bot::factory()->create(['tenant_id' => $tenantB->id]);

        Channel::create(['bot_id' => $botA->id, 'type' => 'whatsapp', 'external_id' => 'phone-A', 'webhook_secret' => 'sec-a']);
        Channel::create(['bot_id' => $botB->id, 'type' => 'whatsapp', 'external_id' => 'phone-B', 'webhook_secret' => 'sec-b']);

        // No actingAs() — unauthenticated, like webhook handler.
        $this->assertNotNull(Channel::where('external_id', 'phone-A')->first());
        $this->assertNotNull(Channel::where('external_id', 'phone-B')->first());
    }

    public function test_tenant_id_persisted_after_save(): void
    {
        $tenant = Tenant::factory()->create();
        $bot = Bot::factory()->create(['tenant_id' => $tenant->id]);
        $channel = Channel::create([
            'bot_id' => $bot->id,
            'type' => Channel::TYPE_WHATSAPP,
            'external_id' => 'phone-persist',
            'webhook_secret' => 'tk',
        ]);

        $reloaded = Channel::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
            ->find($channel->id);

        $this->assertSame($tenant->id, $reloaded->tenant_id);
    }
}
