<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Lead;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Dispatched ori de câte ori un lead nou e capturat dintr-o conversație
 * (chat, voice, prechat form). Listener-ele se ocupă de side-effects:
 * email tenant admins, push, webhooks CRM, etc.
 */
class LeadCaptured
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Lead $lead,
        public string $source = 'chat',
    ) {}
}
