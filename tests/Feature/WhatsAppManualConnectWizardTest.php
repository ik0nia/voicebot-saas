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

class WhatsAppManualConnectWizardTest extends TestCase
{
    use RefreshDatabase;

    private function tenantUser(array $allowedChannels = ['whatsapp']): array
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
            ->get(route('dashboard.bots.channels.whatsapp.connect', $bot))
            ->assertOk()
            ->assertSee('WABA ID')
            ->assertSee('Phone Number ID')
            ->assertSee('System User Access Token');
    }

    public function test_wizard_blocks_when_plan_does_not_include_whatsapp(): void
    {
        [$tenant, $user, $bot] = $this->tenantUser(allowedChannels: ['voice']);

        $this->actingAs($user)
            ->get(route('dashboard.bots.channels.whatsapp.connect', $bot))
            ->assertRedirect(route('dashboard.bots.channels.index', $bot))
            ->assertSessionHasErrors('type');
    }

    public function test_store_creates_channel_with_encrypted_credentials(): void
    {
        [$tenant, $user, $bot] = $this->tenantUser();

        $longToken = 'EAA' . str_repeat('A', 197);

        $this->actingAs($user)
            ->post(route('dashboard.bots.channels.whatsapp.store', $bot), [
                'name' => 'WA Salon',
                'waba_id' => '1234567890',
                'phone_number_id' => '9876543210',
                'access_token' => $longToken,
                'app_secret' => 'a1b2c3d4e5f60718293a4b5c6d7e8f90',
            ])
            ->assertRedirectContains('connected');

        $channel = Channel::where('external_id', '9876543210')->first();
        $this->assertNotNull($channel);
        $this->assertSame(Channel::TYPE_WHATSAPP, $channel->type);
        $this->assertSame($tenant->id, $channel->tenant_id);
        $this->assertSame($bot->id, $channel->bot_id);
        $this->assertSame('1234567890', $channel->getCredential('waba_id'));
        $this->assertSame('9876543210', $channel->getCredential('phone_number_id'));
        $this->assertSame($longToken, $channel->getCredential('access_token'));
        $this->assertSame('a1b2c3d4e5f60718293a4b5c6d7e8f90', $channel->getCredential('app_secret'));
        $this->assertNotEmpty($channel->webhook_secret);
        $this->assertGreaterThanOrEqual(40, strlen($channel->webhook_secret));

        // Plaintext token must not appear in raw column
        $raw = \DB::table('channels')->where('id', $channel->id)->value('credentials');
        $this->assertStringNotContainsString($longToken, $raw);
    }

    public function test_store_rejects_cross_tenant_phone_number_id_collision(): void
    {
        // Tenant A claims a phone_number_id first.
        [$tenantA, $userA, $botA] = $this->tenantUser();
        Channel::create([
            'bot_id' => $botA->id,
            'type' => Channel::TYPE_WHATSAPP,
            'name' => 'A WA',
            'external_id' => '7777777777',
            'webhook_secret' => 'sec-a',
            'is_active' => true,
        ]);

        // Tenant B tries the same number — must be blocked even though
        // the BelongsToTenant scope would hide tenant A's row.
        [$tenantB, $userB, $botB] = $this->tenantUser();

        $this->actingAs($userB)
            ->post(route('dashboard.bots.channels.whatsapp.store', $botB), [
                'waba_id' => '111',
                'phone_number_id' => '7777777777',
                'access_token' => 'EAA' . str_repeat('B', 197),
            ])
            ->assertSessionHasErrors('phone_number_id');

        $this->assertSame(0, Channel::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
            ->where('tenant_id', $tenantB->id)
            ->where('external_id', '7777777777')
            ->count());
    }

    public function test_store_rejects_duplicate_phone_number_id(): void
    {
        [$tenant, $user, $bot] = $this->tenantUser();

        Channel::create([
            'bot_id' => $bot->id,
            'type' => Channel::TYPE_WHATSAPP,
            'name' => 'Existing',
            'external_id' => '5555555555',
            'webhook_secret' => 'sec',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->post(route('dashboard.bots.channels.whatsapp.store', $bot), [
                'waba_id' => '111',
                'phone_number_id' => '5555555555',
                'access_token' => 'EAA' . str_repeat('C', 197),
            ])
            ->assertSessionHasErrors('phone_number_id');
    }

    public function test_store_rejects_too_short_access_token(): void
    {
        [$tenant, $user, $bot] = $this->tenantUser();

        $this->actingAs($user)
            ->post(route('dashboard.bots.channels.whatsapp.store', $bot), [
                'waba_id' => '111',
                'phone_number_id' => '8888888888',
                'access_token' => 'EAA' . str_repeat('A', 30), // <100 total
            ])
            ->assertSessionHasErrors('access_token');
    }

    public function test_store_rejects_token_with_whitespace_or_quotes(): void
    {
        [$tenant, $user, $bot] = $this->tenantUser();

        $this->actingAs($user)
            ->post(route('dashboard.bots.channels.whatsapp.store', $bot), [
                'waba_id' => '111',
                'phone_number_id' => '8888888888',
                'access_token' => 'EAA' . str_repeat('A', 100) . ' "trailing"',
            ])
            ->assertSessionHasErrors('access_token');
    }

    public function test_store_validates_required_fields(): void
    {
        [$tenant, $user, $bot] = $this->tenantUser();

        $this->actingAs($user)
            ->post(route('dashboard.bots.channels.whatsapp.store', $bot), [])
            ->assertSessionHasErrors(['waba_id', 'phone_number_id', 'access_token']);
    }

    public function test_store_rejects_non_numeric_ids(): void
    {
        [$tenant, $user, $bot] = $this->tenantUser();

        $this->actingAs($user)
            ->post(route('dashboard.bots.channels.whatsapp.store', $bot), [
                'waba_id' => 'abc-not-numeric',
                'phone_number_id' => '9876543210',
                'access_token' => 'EAA' . str_repeat('A', 197),
            ])
            ->assertSessionHasErrors('waba_id');
    }

    public function test_connected_screen_shows_webhook_url_and_verify_token(): void
    {
        [$tenant, $user, $bot] = $this->tenantUser();

        $channel = Channel::create([
            'bot_id' => $bot->id,
            'type' => Channel::TYPE_WHATSAPP,
            'name' => 'WA',
            'external_id' => '8888',
            'webhook_secret' => 'verifytoken-xyz123',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard.bots.channels.whatsapp.connected', ['bot' => $bot, 'channel' => $channel]))
            ->assertOk()
            ->assertSee('verifytoken-xyz123')
            ->assertSee('webhook/whatsapp')
            ->assertSee('8888');
    }

    public function test_connected_screen_404_for_non_whatsapp_channel(): void
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
            ->get(route('dashboard.bots.channels.whatsapp.connected', ['bot' => $bot, 'channel' => $voice]))
            ->assertNotFound();
    }

    public function test_connected_screen_404_for_other_tenant_channel(): void
    {
        [$tenantA, $userA, $botA] = $this->tenantUser();
        [$tenantB, $userB, $botB] = $this->tenantUser();

        $channelB = Channel::create([
            'bot_id' => $botB->id,
            'type' => Channel::TYPE_WHATSAPP,
            'name' => 'B WA',
            'external_id' => '777',
            'webhook_secret' => 'tk-b',
            'is_active' => true,
        ]);

        $this->actingAs($userA)
            ->get(route('dashboard.bots.channels.whatsapp.connected', ['bot' => $botA, 'channel' => $channelB]))
            ->assertNotFound();
    }

    public function test_unauthenticated_user_cannot_access_wizard(): void
    {
        [$tenant, $user, $bot] = $this->tenantUser();

        $this->get(route('dashboard.bots.channels.whatsapp.connect', $bot))
            ->assertRedirect();
    }
}
