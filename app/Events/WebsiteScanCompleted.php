<?php

namespace App\Events;

use App\Models\WebsiteScan;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WebsiteScanCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public WebsiteScan $scan,
        public int $pagesProcessed,
    ) {}
}
