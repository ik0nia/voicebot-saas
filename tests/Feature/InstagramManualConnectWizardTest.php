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

class InstagramManualConnectWizardTest extends TestCase
{
    use RefreshDatabase;

    private function tenantUser(array $allowedChannels = ['instagram_dm']): array
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
            ->get(route('dashboard.bots.channels.instagram.connect', $bot))
            ->assertOk()
            ->assertSee('Instagram Business Account ID')
            ->assertSee('Page ID')
            ->assertSee('Page Access Token');
    }

    public function test_wizard_blocks_when_plan_does_not_include_instagram(): void
    {
        [$tenant, $user, $bot] = $this->tenantUser(allowedChannels: ['voice']);

        $this->actingAs($user)
            ->get(route('dashboard.bots.channels.instagram.connect', $bot))
            ->assertRedirect(route('dashboard.bots.channels.index', $bot))
            ->assertSessionHasErrors('type');
    }

    public function test_store_creates_channel_with_encrypted_credentials(): void
    {
        [$tenant, $user, $bot] = $this->tenantUser();

        $longToken = 'EAA' . str_repeat('A', 197);

        $this->actingAs($user)
            ->post(route('dashboard.bots.channels.instagram.store', $bot), [
                'name' => 'IG Salon',
                'instagram_id' => '17841401234567890',
                'page_id' => '123456789012345',
                'page_access_token' => $longToken,
                'instagram_username' => 'salon.bucuresti',
                'app_secret' => 'a1b2c3d4e5f60718293a4b5c6d7e8f90',
            ])
            ->assertRedirectContains('connected');

        $channel = Channel::where('external_id', '17841401234567890')->first();
        $this->assertNotNull($channel);
        $this->assertSame(Channel::TYPE_INSTAGRAM_DM, $channel->type);
        $this->assertSame($tenant->id, $channel->tenant_id);
        $this->assertSame($bot->id, $channel->bot_id);
        $this->assertSame('17841401234567890', $channel->getCredential('instagram_id'));
        $this->assertSame('123456789012345', $channel->getCredential('page_id'));
        $this->assertSame($longToken, $channel->getCredential('page_access_token'));
        $this->assertSame('salon.bucuresti', $channel->getCredential('instagram_username'));
        $this->assertSame('a1b2c3d4e5f60718293a4b5c6d7e8f90', $channel->getCredential('app_secret'));
        $this->assertNotEmpty($channel->webhook_secret);
        $this->assertGreaterThanOrEqual(40, strlen($channel->webhook_secret));

        // Plaintext token must not appear in raw column
        $raw = \DB::table('channels')->where('id', $channel->id)->value('credentials');
        $this->assertStringNotContainsString($longToken, $raw);
    }

    public function test_store_uses_username_as_default_name(): void
    {
        [$tenant, $user, $bot] = $this->tenantUser();

        $this->actingAs($user)
            ->post(route('dashboard.bots.channels.instagram.store', $bot), [
                'instagram_id' => '17841400000000001',
                'page_id' => '123',
                'page_access_token' => 'EAA' . str_repeat('A', 197),
                'instagram_username' => 'mysalon',
            ]);

        $channel = Channel::where('external_id', '17841400000000001')->first();
        $this->assertSame('@mysalon', $channel->name);
    }

    public function test_store_rejects_cross_tenant_instagram_id_collision(): void
    {
        [$tenantA, $userA, $botA] = $this->tenantUser();
        Channel::create([
            'bot_id' => $botA->id,
            'type' => Channel::TYPE_INSTAGRAM_DM,
            'name' => 'A IG',
            'external_id' => '17841401111111111',
            'webhook_secret' => 'sec-a',
            'is_active' => true,
        ]);

        [$tenantB, $userB, $botB] = $this->tenantUser();

        $this->actingAs($userB)
            ->post(route('dashboard.bots.channels.instagram.store', $botB), [
                'instagram_id' => '17841401111111111',
                'page_id' => '987',
                'page_access_token' => 'EAA' . str_repeat('B', 197),
            ])
            ->assertSessionHasErrors('instagram_id');

        $this->assertSame(0, Channel::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
            ->where('tenant_id', $tenantB->id)
            ->where('external_id', '17841401111111111')
            ->count());
    }

    public function test_store_validates_required_fields(): void
    {
        [$tenant, $user, $bot] = $this->tenantUser();

        $this->actingAs($user)
            ->post(route('dashboard.bots.channels.instagram.store', $bot), [])
            ->assertSessionHasErrors(['instagram_id', 'page_id', 'page_access_token']);
    }

    public function test_store_rejects_invalid_username(): void
    {
        [$tenant, $user, $bot] = $this->tenantUser();

        $this->actingAs($user)
            ->post(route('dashboard.bots.channels.instagram.store', $bot), [
                'instagram_id' => '17841402222222222',
                'page_id' => '123',
                'page_access_token' => 'EAA' . str_repeat('A', 197),
                'instagram_username' => 'invalid username with spaces',
            ])
            ->assertSessionHasErrors('instagram_username');
    }

    public function test_connected_screen_shows_webhook_url_and_verify_token(): void
    {
        [$tenant, $user, $bot] = $this->tenantUser();

        $channel = Channel::create([
            'bot_id' => $bot->id,
            'type' => Channel::TYPE_INSTAGRAM_DM,
            'name' => 'IG',
            'external_id' => '17841403333333333',
            'webhook_secret' => 'verifytoken-ig-xyz',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard.bots.channels.instagram.connected', ['bot' => $bot, 'channel' => $channel]))
            ->assertOk()
            ->assertSee('verifytoken-ig-xyz')
            ->assertSee('webhook/instagram')
            ->assertSee('17841403333333333');
    }

    public function test_connected_screen_404_for_non_instagram_channel(): void
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
            ->get(route('dashboard.bots.channels.instagram.connected', ['bot' => $bot, 'channel' => $voice]))
            ->assertNotFound();
    }

    public function test_unauthenticated_user_cannot_access_wizard(): void
    {
        [$tenant, $user, $bot] = $this->tenantUser();

        $this->get(route('dashboard.bots.channels.instagram.connect', $bot))
            ->assertRedirect();
    }
}
