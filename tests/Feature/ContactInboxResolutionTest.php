<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Bot;
use App\Models\Channel;
use App\Models\Contact;
use App\Models\ContactInbox;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactInboxResolutionTest extends TestCase
{
    use RefreshDatabase;

    private function tenantWithChannel(string $type = Channel::TYPE_WHATSAPP): array
    {
        $tenant = Tenant::factory()->create();
        $bot = Bot::factory()->create(['tenant_id' => $tenant->id]);
        $channel = Channel::create([
            'bot_id' => $bot->id,
            'type' => $type,
            'name' => 'test',
            'external_id' => 'ext-' . $type,
            'webhook_secret' => 'sec',
            'is_active' => true,
        ]);
        return [$tenant, $bot, $channel];
    }

    public function test_resolve_for_inbound_creates_contact_and_pivot_when_new(): void
    {
        [$tenant, $bot, $channel] = $this->tenantWithChannel();

        $pivot = ContactInbox::resolveForInbound(
            channel: $channel,
            sourceId: '40700111222',
            contactName: 'Maria Pop',
            sourceMetadata: ['profile_pic' => 'https://example/pic.jpg']
        );

        $this->assertNotNull($pivot->id);
        $this->assertSame($channel->id, $pivot->channel_id);
        $this->assertSame('40700111222', $pivot->source_id);
        $this->assertSame('Maria Pop', $pivot->contact->name);
        $this->assertSame($tenant->id, $pivot->contact->tenant_id);
        $this->assertSame('https://example/pic.jpg', $pivot->source_metadata['profile_pic']);

        // Legacy column was stamped
        $this->assertSame('40700111222', $pivot->contact->whatsapp_id);
    }

    public function test_resolve_for_inbound_returns_existing_pivot_on_collision(): void
    {
        [$tenant, $bot, $channel] = $this->tenantWithChannel();

        $first = ContactInbox::resolveForInbound($channel, '40700111222', 'Maria');
        $second = ContactInbox::resolveForInbound($channel, '40700111222', 'Different Name');

        $this->assertSame($first->id, $second->id);
        $this->assertSame($first->contact_id, $second->contact_id);
        // We do NOT overwrite the existing contact name on collision —
        // the first inbound wins until manual reconciliation in admin UI.
        $this->assertSame('Maria', $second->contact->name);
    }

    public function test_resolve_merges_into_existing_contact_via_phone_hint(): void
    {
        [$tenant, $bot, $waChannel] = $this->tenantWithChannel(Channel::TYPE_WHATSAPP);

        // Pre-existing voice Contact with phone +40700111222
        $existing = Contact::create([
            'tenant_id' => $tenant->id,
            'name' => 'Maria Pop (voice)',
            'phone' => '40700111222',
        ]);

        // WhatsApp inbound arrives for the same phone — should merge into
        // the existing Contact, NOT create a duplicate.
        $pivot = ContactInbox::resolveForInbound(
            channel: $waChannel,
            sourceId: '40700111222',
            contactName: 'Maria',
            contactMatchHints: ['phone' => '40700111222']
        );

        $this->assertSame($existing->id, $pivot->contact_id);
        $this->assertSame(1, Contact::where('tenant_id', $tenant->id)->count(),
            'No duplicate Contact should be created when phone hint matches');

        // The merged Contact retains its original name (we do not overwrite)
        $this->assertSame('Maria Pop (voice)', $pivot->contact->fresh()->name);
        // But picks up the whatsapp_id stamping
        $this->assertSame('40700111222', $pivot->contact->fresh()->whatsapp_id);
    }

    public function test_unique_constraint_per_channel_id_source_id(): void
    {
        [$tenant1, $bot1, $channelA] = $this->tenantWithChannel();
        [$tenant2, $bot2, $channelB] = $this->tenantWithChannel();

        // Same source_id on TWO different channels is fine — they are
        // different conversations from this seam's POV (could be the same
        // human, but we don't auto-merge across channels without hints).
        $pivotA = ContactInbox::resolveForInbound($channelA, '40700111222', 'Maria');
        $pivotB = ContactInbox::resolveForInbound($channelB, '40700111222', 'Maria');

        $this->assertNotSame($pivotA->id, $pivotB->id);
        $this->assertSame($channelA->id, $pivotA->channel_id);
        $this->assertSame($channelB->id, $pivotB->channel_id);
    }

    public function test_source_metadata_is_merged_not_replaced_on_recurring_inbound(): void
    {
        [$tenant, $bot, $channel] = $this->tenantWithChannel();

        ContactInbox::resolveForInbound($channel, 'src', null, [], ['locale' => 'ro_RO']);
        $second = ContactInbox::resolveForInbound($channel, 'src', null, [], ['profile_pic' => 'p.jpg']);

        $this->assertSame('ro_RO', $second->source_metadata['locale']);
        $this->assertSame('p.jpg', $second->source_metadata['profile_pic']);
    }

    public function test_legacy_facebook_psid_stamped_on_facebook_channel(): void
    {
        [$tenant, $bot, $fbChannel] = $this->tenantWithChannel(Channel::TYPE_FACEBOOK_MESSENGER);

        $pivot = ContactInbox::resolveForInbound($fbChannel, 'fb-psid-123');

        $this->assertSame('fb-psid-123', $pivot->contact->facebook_psid);
        $this->assertNull($pivot->contact->whatsapp_id);
        $this->assertNull($pivot->contact->instagram_id);
    }

    public function test_legacy_instagram_id_stamped_on_instagram_channel(): void
    {
        [$tenant, $bot, $igChannel] = $this->tenantWithChannel(Channel::TYPE_INSTAGRAM_DM);

        $pivot = ContactInbox::resolveForInbound($igChannel, 'ig-sender-123');

        $this->assertSame('ig-sender-123', $pivot->contact->instagram_id);
        $this->assertNull($pivot->contact->whatsapp_id);
        $this->assertNull($pivot->contact->facebook_psid);
    }

    public function test_legacy_id_not_overwritten_when_already_set(): void
    {
        [$tenant, $bot, $waChannel] = $this->tenantWithChannel(Channel::TYPE_WHATSAPP);

        // Contact already has whatsapp_id from somewhere else
        $contact = Contact::create([
            'tenant_id' => $tenant->id,
            'name' => 'Maria',
            'phone' => '40700111222',
            'whatsapp_id' => 'existing-wa-id',
        ]);

        ContactInbox::resolveForInbound(
            channel: $waChannel,
            sourceId: '40700999888',
            contactMatchHints: ['phone' => '40700111222']
        );

        // Existing whatsapp_id is preserved (we don't clobber)
        $this->assertSame('existing-wa-id', $contact->fresh()->whatsapp_id);
    }
}
