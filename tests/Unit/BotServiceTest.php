<?php

namespace Tests\Unit;

use App\Models\Bot;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class BotServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_bot_slug_is_auto_generated(): void
    {
        $tenant = Tenant::factory()->create();

        $bot = Bot::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'My Amazing Bot',
        ]);

        $this->assertNotNull($bot->slug);
        $this->assertNotEmpty($bot->slug);
        // Slug should contain a slugified version of something
        $this->assertMatchesRegularExpression('/^[a-z0-9A-Z\-]+$/', $bot->slug);
    }

    public function test_bot_default_settings_are_applied(): void
    {
        $tenant = Tenant::factory()->create();

        $bot = Bot::factory()->create(['tenant_id' => $tenant->id]);

        $this->assertIsArray($bot->settings);
        $this->assertArrayHasKey('vad_threshold', $bot->settings);
        $this->assertArrayHasKey('temperature', $bot->settings);
        $this->assertEquals(0.5, $bot->settings['vad_threshold']);
        $this->assertEquals(0.7, $bot->settings['temperature']);
    }

    public function test_bot_can_be_activated(): void
    {
        $tenant = Tenant::factory()->create();

        $bot = Bot::factory()->inactive()->create(['tenant_id' => $tenant->id]);

        $this->assertFalse($bot->is_active);

        $bot->update(['is_active' => true]);

        $this->assertTrue($bot->fresh()->is_active);
    }

    public function test_bot_can_be_deactivated(): void
    {
        $tenant = Tenant::factory()->create();

        $bot = Bot::factory()->create([
            'tenant_id' => $tenant->id,
            'is_active' => true,
        ]);

        $this->assertTrue($bot->is_active);

        $bot->update(['is_active' => false]);

        $this->assertFalse($bot->fresh()->is_active);
    }
}
