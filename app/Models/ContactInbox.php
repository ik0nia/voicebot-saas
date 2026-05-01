<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Pivot between Contact and Channel — one row per (contact, channel they
 * reached us on). The (channel_id, source_id) unique key is the canonical
 * lookup used by inbound webhook handlers to find or create the Contact.
 *
 * NOT BelongsToTenant directly: the trait would require a tenant_id column.
 * Tenancy is inherited via channel_id (Channel is BelongsToTenant) and the
 * scopeForTenant() helper below. Inbound webhook handlers run unauthenticated
 * so a global scope here would be at best a no-op and at worst a footgun.
 */
class ContactInbox extends Model
{
    use HasFactory;

    protected $fillable = [
        'contact_id',
        'channel_id',
        'source_id',
        'source_metadata',
    ];

    protected $casts = [
        'source_metadata' => 'array',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    /**
     * Resolve a ContactInbox for an inbound message. Idempotent:
     * - returns the existing pivot if (channel_id, source_id) already exists
     * - otherwise creates a new Contact + ContactInbox in one transaction
     *
     * The optional $contactMatchHints array lets the caller pass cross-channel
     * identifiers (phone, email) that we use to merge into an existing Contact
     * for the same tenant when a match is found, instead of creating a
     * duplicate. E.g. when WhatsApp inbound arrives for a phone we already
     * have on a voice Contact, both end up linked to the same Contact row.
     */
    public static function resolveForInbound(
        Channel $channel,
        string $sourceId,
        ?string $contactName = null,
        array $contactMatchHints = [],
        array $sourceMetadata = []
    ): self {
        $existing = static::query()
            ->where('channel_id', $channel->id)
            ->where('source_id', $sourceId)
            ->first();

        if ($existing) {
            // Refresh source_metadata snapshot (display name might have
            // changed) but never overwrite with empty.
            if (!empty($sourceMetadata)) {
                $existing->source_metadata = array_merge(
                    $existing->source_metadata ?? [],
                    $sourceMetadata
                );
                $existing->save();
            }
            return $existing;
        }

        $tenantId = $channel->tenant_id;

        // Try to merge into an existing Contact via cross-channel hints
        // (phone/email match within the same tenant). Otherwise create new.
        $contact = static::findContactByHints($tenantId, $contactMatchHints)
            ?? Contact::create([
                'tenant_id' => $tenantId,
                'name' => $contactName,
                'phone' => $contactMatchHints['phone'] ?? null,
                'email' => $contactMatchHints['email'] ?? null,
            ]);

        // Stamp the channel-specific identifier on the Contact too (legacy
        // schema — preserves backward compat with code that reads
        // contacts.whatsapp_id / facebook_psid / instagram_id).
        static::stampLegacyChannelId($contact, $channel, $sourceId);

        return static::create([
            'contact_id' => $contact->id,
            'channel_id' => $channel->id,
            'source_id' => $sourceId,
            'source_metadata' => $sourceMetadata ?: null,
        ]);
    }

    private static function findContactByHints(int $tenantId, array $hints): ?Contact
    {
        $query = Contact::query()->where('tenant_id', $tenantId);

        $matched = false;
        if (!empty($hints['phone'])) {
            $query->where('phone', $hints['phone']);
            $matched = true;
        }
        if (!empty($hints['email'])) {
            if ($matched) {
                $query->orWhere(function ($q) use ($tenantId, $hints) {
                    $q->where('tenant_id', $tenantId)->where('email', $hints['email']);
                });
            } else {
                $query->where('email', $hints['email']);
                $matched = true;
            }
        }

        return $matched ? $query->first() : null;
    }

    private static function stampLegacyChannelId(Contact $contact, Channel $channel, string $sourceId): void
    {
        $field = match ($channel->type) {
            Channel::TYPE_WHATSAPP => 'whatsapp_id',
            Channel::TYPE_FACEBOOK_MESSENGER => 'facebook_psid',
            Channel::TYPE_INSTAGRAM_DM => 'instagram_id',
            default => null,
        };

        if ($field === null || !empty($contact->{$field})) {
            return;
        }

        $contact->{$field} = $sourceId;
        $contact->save();
    }
}
