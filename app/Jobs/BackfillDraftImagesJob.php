<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

/**
 * Fills images for drafts whose image_url is still NULL (e.g. created by
 * GenerateSocialDraft but whose image step timed out on the short-timeout
 * worker). Runs `social:backfill-images --status=draft` on the long-timeout
 * `knowledge` supervisor so individual gpt-image-2 calls actually complete.
 */
class BackfillDraftImagesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600; // 1h
    public int $tries = 1;

    public function __construct(public ?int $limit = null)
    {
        $this->onQueue('knowledge');
    }

    public function handle(): void
    {
        $args = [
            '--status' => ['draft'],
        ];
        if ($this->limit !== null && $this->limit > 0) {
            $args['--limit'] = $this->limit;
        }

        Log::info('BackfillDraftImagesJob: starting', $args);
        Artisan::call('social:backfill-images', $args);
        $output = Artisan::output();
        Log::info('BackfillDraftImagesJob: finished', [
            'output_tail' => mb_substr($output, -2000),
        ]);
    }
}
