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
 * Bulk image regeneration for scheduled posts.
 * Runs `social:regenerate-images --status=scheduled --backup` on the queue
 * (Horizon) so the HTTP endpoint that triggers it doesn't time out.
 * Each image takes ~100s via gpt-image-2; 300 posts = ~8h.
 */
class RegenerateScheduledImagesBulkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 43200; // 12h
    public int $tries = 1;

    public function __construct(
        public ?int $limit = null,
    ) {}

    public function handle(): void
    {
        $args = [
            '--status' => ['scheduled'],
            '--backup' => true,
            '--sleep' => 3,
        ];
        if ($this->limit !== null && $this->limit > 0) {
            $args['--limit'] = $this->limit;
        }

        Log::info('RegenerateScheduledImagesBulkJob: starting', $args);
        Artisan::call('social:regenerate-images', $args);
        $output = Artisan::output();
        Log::info('RegenerateScheduledImagesBulkJob: finished', [
            'output_tail' => mb_substr($output, -2000),
        ]);
    }
}
