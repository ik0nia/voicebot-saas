<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Bot;
use App\Models\Channel;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FacebookManualConnectWizardTest extends TestCase
{
    use RefreshDatabase;

    private function tenantUser(array $allowedChannels = ['facebook_messenger']): array
    {
        Role::findOrCreate('tenant_admin', 'web');
        Role::findOrCreate('tenant_manager', 'web');

        $tenant = Tenant::factory()->create([
            'settings' => ['allowed_channels' => $allowedChannels],
        ]);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $user->assignRole('tenant_admin');
        $bot = Bot::factory()->create(['tenant_id' => $tenant->id]);

        return [$tenant, $user, $bot];
    }

    public function test_wizard_renders_for_authorized_user(): void
    {
        [$tenant, $user, $bot] = $this->tenantUser();

        $this->actingAs($user)
            ->get(route('dashboard.bots.channels.facebook.connect', $bot))
            ->assertOk()
            ->assertSee('Page ID')
            ->assertSee('Page Access Token');
    }

    public function test_wizard_blocks_when_plan_does_not_include_facebook(): void
    {
        [$tenant, $user, $bot] = $this->tenantUser(allowedChannels: ['voice']);

        $this->actingAs($user)
            ->get(route('dashboard.bots.channels.facebook.connect', $bot))
            ->assertRedirect(route('dashboard.bots.channels.index', $bot))
            ->assertSessionHasErrors('type');
    }

    public function test_store_creates_channel_with_encrypted_credentials(): void
    {
        [$tenant, $user, $bot] = $this->tenantUser();

        $longToken = 'EAA' . str_repeat('A', 197);

        $this->actingAs($user)
            ->post(route('dashboard.bots.channels.facebook.store', $bot), [
                'name' => 'FB Salon',
                'page_id' => '123456789012345',
                'page_access_token' => $longToken,
                'app_secret' => 'a1b2c3d4e5f60718293a4b5c6d7e8f90',
            ])
            ->assertRedirectContains('connected');

        $channel = Channel::where('external_id', '123456789012345')->first();
        $this->assertNotNull($channel);
        $this->assertSame(Channel::TYPE_FACEBOOK_MESSENGER, $channel->type);
        $this->assertSame($tenant->id, $channel->tenant_id);
        $this->assertSame($bot->id, $channel->bot_id);
        $this->assertSame('123456789012345', $channel->getCredential('page_id'));
        $this->assertSame($longToken, $channel->getCredential('page_access_token'));
        $this->assertSame('a1b2c3d4e5f60718293a4b5c6d7e8f90', $channel->getCredential('app_secret'));
        $this->assertNotEmpty($channel->webhook_secret);
        $this->assertGreaterThanOrEqual(40, strlen($channel->webhook_secret));

        // Plaintext token must not appear in raw column
        $raw = \DB::table('channels')->where('id', $channel->id)->value('credentials');
        $this->assertStringNotContainsString($longToken, $raw);
    }

    public function test_store_rejects_cross_tenant_page_id_collision(): void
    {
        // Tenant A claims a page_id first.
        [$tenantA, $userA, $botA] = $this->tenantUser();
        Channel::create([
            'bot_id' => $botA->id,
            'type' => Channel::TYPE_FACEBOOK_MESSENGER,
            'name' => 'A FB',
            'external_id' => '999888777',
            'webhook_secret' => 'sec-a',
            'is_active' => true,
        ]);

        // Tenant B tries the same page — must be blocked even though
        // the BelongsToTenant scope hides tenant A's row.
        [$tenantB, $userB, $botB] = $this->tenantUser();

        $this->actingAs($userB)
            ->post(route('dashboard.bots.channels.facebook.store', $botB), [
                'page_id' => '999888777',
                'page_access_token' => 'EAA' . str_repeat('B', 197),
            ])
            ->assertSessionHasErrors('page_id');

        $this->assertSame(0, Channel::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
            ->where('tenant_id', $tenantB->id)
            ->where('external_id', '999888777')
            ->count());
    }

    public function test_store_rejects_too_short_access_token(): void
    {
        [$tenant, $user, $bot] = $this->tenantUser();

        $this->actingAs($user)
            ->post(route('dashboard.bots.channels.facebook.store', $bot), [
                'page_id' => '888888888',
                'page_access_token' => 'EAA' . str_repeat('A', 30),
            ])
            ->assertSessionHasErrors('page_access_token');
    }

    public function test_store_validates_required_fields(): void
    {
        [$tenant, $user, $bot] = $this->tenantUser();

        $this->actingAs($user)
            ->post(route('dashboard.bots.channels.facebook.store', $bot), [])
            ->assertSessionHasErrors(['page_id', 'page_access_token']);
    }

    public function test_store_rejects_non_numeric_page_id(): void
    {
        [$tenant, $user, $bot] = $this->tenantUser();

        $this->actingAs($user)
            ->post(route('dashboard.bots.channels.facebook.store', $bot), [
                'page_id' => 'abc-not-numeric',
                'page_access_token' => 'EAA' . str_repeat('A', 197),
            ])
            ->assertSessionHasErrors('page_id');
    }

    public function test_connected_screen_shows_webhook_url_and_verify_token(): void
    {
        [$tenant, $user, $bot] = $this->tenantUser();

        $channel = Channel::create([
            'bot_id' => $bot->id,
            'type' => Channel::TYPE_FACEBOOK_MESSENGER,
            'name' => 'FB',
            'external_id' => '555555555',
            'webhook_secret' => 'verifytoken-fb-xyz',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard.bots.channels.facebook.connected', ['bot' => $bot, 'channel' => $channel]))
            ->assertOk()
            ->assertSee('verifytoken-fb-xyz')
            ->assertSee('webhook/facebook')
            ->assertSee('555555555');
    }

    public function test_connected_screen_404_for_non_facebook_channel(): void
    {
        [$tenant, $user, $bot] = $this->tenantUser();
        $voice = Channel::create([
            'bot_id' => $bot->id,
            'type' => Channel::TYPE_VOICE,
            'name' => 'Voice',
            'external_id' => '+40700000000',
            'webhook_secret' => 'tk',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard.bots.channels.facebook.connected', ['bot' => $bot, 'channel' => $voice]))
            ->assertNotFound();
    }

    public function test_connected_screen_404_for_other_tenant_channel(): void
    {
        [$tenantA, $userA, $botA] = $this->tenantUser();
        [$tenantB, $userB, $botB] = $this->tenantUser();

        $channelB = Channel::create([
            'bot_id' => $botB->id,
            'type' => Channel::TYPE_FACEBOOK_MESSENGER,
            'name' => 'B FB',
            'external_id' => '777',
            'webhook_secret' => 'tk-b',
            'is_active' => true,
        ]);

        $this->actingAs($userA)
            ->get(route('dashboard.bots.channels.facebook.connected', ['bot' => $botA, 'channel' => $channelB]))
            ->assertNotFound();
    }

    public function test_unauthenticated_user_cannot_access_wizard(): void
    {
        [$tenant, $user, $bot] = $this->tenantUser();

        $this->get(route('dashboard.bots.channels.facebook.connect', $bot))
            ->assertRedirect();
    }
}
