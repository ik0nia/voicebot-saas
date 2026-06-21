<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Laravel\Cashier\Billable;

class Tenant extends Model
{
    use HasFactory, Billable;

    protected $fillable = [
        'name',
        'slug',
        'plan',
        'plan_slug',
        'plan_overrides',
        'settings',
        'trial_ends_at',
        'stripe_id',
        'pm_type',
        'pm_last_four',
        'webhook_url',
        'webhook_secret',
        'company_name',
        'company_cif',
        'company_reg_number',
        'company_address',
        'company_city',
        'company_county',
        'company_country',
        'company_zip',
        'company_email',
        'company_phone',
        'company_contact_person',
        'company_bank',
        'company_iban',
        'billing_complete',
        'message_credits',
        'minute_credits',
        'product_credits',
        // Twilio subaccount for this tenant. Populated on first number
        // purchase via TwilioService::ensureSubaccount(). When null,
        // the tenant routes through the master Twilio account.
        'telephony_subaccount_sid',
        'telephony_subaccount_auth_token',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'plan_overrides' => 'array',
            'trial_ends_at' => 'datetime',
            // Encrypted at rest — Eloquent casts handle decryption on read
            // and encryption on write. A leaked DB dump stays short of
            // per-tenant Twilio API credentials.
            'telephony_subaccount_auth_token' => 'encrypted',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Tenant $tenant) {
            if (empty($tenant->slug)) {
                $tenant->slug = Str::slug($tenant->name);
            }
        });
    }

    /**
     * Canned responses (snippet-uri pre-definite) la nivel de tenant — toți
     * operatorii pot insera rapid în conversații. Stocate ca array de
     * {label, body} în settings.canned_responses.
     *
     * @return array<int, array{label:string,body:string}>
     */
    public function cannedResponses(): array
    {
        $list = $this->settings['canned_responses'] ?? [];
        if (!is_array($list)) {
            return [];
        }
        return array_values(array_filter(array_map(
            fn($r) => is_array($r) && !empty($r['label']) && !empty($r['body'])
                ? ['label' => (string) $r['label'], 'body' => (string) $r['body']]
                : null,
            $list
        )));
    }

    /**
     * Detalii GDPR/Privacy contact (DPO, privacy URL, etc.) — afișate în
     * widget footer + în email-uri legale automate.
     *
     * @return array{dpo_email:?string,privacy_policy_url:?string,terms_url:?string}
     */
    public function privacyContact(): array
    {
        $p = is_array($this->settings['privacy'] ?? null) ? $this->settings['privacy'] : [];
        $clean = fn($v) => is_string($v) && trim($v) !== '' ? $v : null;
        return [
            'dpo_email' => $clean($p['dpo_email'] ?? null),
            'privacy_policy_url' => $clean($p['privacy_policy_url'] ?? null),
            'terms_url' => $clean($p['terms_url'] ?? null),
        ];
    }

    /**
     * Politici de retenție GDPR per-tenant. Override din `tenant.settings.retention.*`
     * cu fallback la env (RETENTION_*_DAYS), apoi la default-uri standard.
     *
     * @return array{conversations_days:int,call_anonymise_days:int,recording_purge_days:int}
     */
    public function retentionSettings(): array
    {
        $r = is_array($this->settings['retention'] ?? null) ? $this->settings['retention'] : [];
        $convDays = is_numeric($r['conversations_days'] ?? null)
            ? max(7, min(3650, (int) $r['conversations_days']))
            : (int) env('RETENTION_CONVERSATIONS_DAYS', 90);
        $callDays = is_numeric($r['call_anonymise_days'] ?? null)
            ? max(7, min(3650, (int) $r['call_anonymise_days']))
            : (int) env('RETENTION_CALL_ANONYMISE_DAYS', 30);
        $recDays = is_numeric($r['recording_purge_days'] ?? null)
            ? max(1, min(3650, (int) $r['recording_purge_days']))
            : (int) env('RETENTION_RECORDING_PURGE_DAYS', 30);
        return [
            'conversations_days' => $convDays,
            'call_anonymise_days' => $callDays,
            'recording_purge_days' => $recDays,
        ];
    }

    // Relationships

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function bots(): HasMany
    {
        return $this->hasMany(Bot::class);
    }

    public function sites(): HasMany
    {
        return $this->hasMany(Site::class);
    }

    public function calls(): HasMany
    {
        return $this->hasMany(Call::class);
    }

    public function phoneNumbers(): HasMany
    {
        return $this->hasMany(PhoneNumber::class);
    }

    public function usageRecords(): HasMany
    {
        return $this->hasMany(UsageRecord::class);
    }

    public function usageTracking(): HasMany
    {
        return $this->hasMany(UsageTracking::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function planLimits(): ?PlanLimit
    {
        return PlanLimit::findBySlug($this->plan_slug ?? 'free');
    }

    // Methods

    public function isOnTrial(): bool
    {
        return $this->trial_ends_at !== null && $this->trial_ends_at->isFuture();
    }

    public function isOnPlan(string $plan): bool
    {
        return $this->plan === $plan;
    }

    public function hasFeature(string $feature): bool
    {
        $settings = $this->settings ?? [];
        $features = $settings['features'] ?? [];

        return in_array($feature, $features, true);
    }

    public function minutesUsedThisMonth(): float
    {
        return $this->calls()
            ->whereMonth('started_at', now()->month)
            ->whereYear('started_at', now()->year)
            ->sum('duration_seconds') / 60;
    }

    public function minutesRemaining(): float
    {
        $settings = $this->settings ?? [];
        $limit = $settings['minutes_limit'] ?? 0;

        return max(0, $limit - $this->minutesUsedThisMonth());
    }
}
