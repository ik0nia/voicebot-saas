<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Lead;
use App\Services\Telephony\TwilioLookupService;
use Illuminate\Support\Facades\Log;

/**
 * Normalizează telefonul la E.164 înainte de save folosind Twilio Lookup v2.
 * Acoperă toate cele 4 căi de creare lead (chat extract, prechat, voice,
 * channel inbound). Best-effort — eșecul Lookup nu blochează save-ul.
 *
 * Marchează numerele invalide cu un flag în `metadata.phone_invalid = true`
 * pentru ca dashboard-ul să le poată afișa highlight + operator să decidă
 * dacă merită urmărit.
 */
class LeadObserver
{
    public function creating(Lead $lead): void
    {
        $this->normalize($lead);
    }

    public function updating(Lead $lead): void
    {
        if ($lead->isDirty('phone')) {
            $this->normalize($lead);
        }
    }

    private function normalize(Lead $lead): void
    {
        $raw = (string) ($lead->phone ?? '');
        if (trim($raw) === '') return;

        try {
            $lookup = app(TwilioLookupService::class)->validate($raw, 'RO', 'basic');
            if (!empty($lookup['e164'])) {
                $lead->phone = $lookup['e164'];
            }
            if (!$lookup['valid']) {
                $meta = is_array($lead->metadata) ? $lead->metadata : [];
                $meta['phone_invalid'] = true;
                $lead->metadata = $meta;
            }
        } catch (\Throwable $e) {
            Log::debug('LeadObserver lookup skipped', ['err' => $e->getMessage()]);
        }
    }
}
