<?php

namespace App\Engines;

class LeadEngine extends BaseBotEngine
{
    public function type(): string { return 'lead'; }
    public function displayName(): string { return 'Lead-uri și oferte'; }

    protected function defaultCapabilities(): array
    {
        return ['lead_capture', 'quote_calculator', 'follow_up'];
    }

    protected function defaultKpiKeys(): array
    {
        return ['new_leads_today', 'quotes_sent', 'conversion_rate'];
    }
}
