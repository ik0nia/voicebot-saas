<?php

namespace App\Jobs;

use App\Models\KnowledgeConnector;
use App\Services\Connectors\WooCommerceConnectorService;
use App\Services\Connectors\WordPressConnectorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncConnector implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $backoff = 60;
    public int $timeout = 300;

    public function __construct(public KnowledgeConnector $connector)
    {
        $this->onQueue('default');
    }

    public function handle(): void
    {
        match ($this->connector->type) {
            'wordpress' => app(WordPressConnectorService::class)->sync($this->connector),
            'woocommerce' => app(WooCommerceConnectorService::class)->sync($this->connector),
            default => null,
        };
    }

    public function failed(\Throwable $e): void
    {
        $this->connector->update(['status' => 'error']);

        \Log::error('SyncConnector job failed', [
            'connector_id' => $this->connector->id,
            'bot_id' => $this->connector->bot_id,
            'type' => $this->connector->type,
            'error' => $e->getMessage(),
        ]);
    }
}
