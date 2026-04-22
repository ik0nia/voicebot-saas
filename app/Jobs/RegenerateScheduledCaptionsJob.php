<?php

namespace App\Jobs;

use App\Models\SocialPost;
use App\Services\Social\GeminiContentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Regenerates the CAPTION TEXT of every scheduled post group using the current
 * `GeminiContentService::generatePost()` (which now has the corrected brand
 * framing: WebChat + Voice agents, not document management). One GPT-4o-mini
 * call per group — the FB post text is cascaded to its IG sibling so the two
 * stay in sync. Stories are left untouched (separate short creative).
 */
class RegenerateScheduledCaptionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 7200; // 2h
    public int $tries = 1;

    public function __construct(public ?int $limit = null)
    {
        $this->onQueue('knowledge');
    }

    public function handle(): void
    {
        $gemini = app(GeminiContentService::class);

        $groupIds = SocialPost::where('status', 'scheduled')
            ->whereNotNull('group_id')
            ->distinct()
            ->orderBy('group_id')
            ->pluck('group_id');

        if ($this->limit) {
            $groupIds = $groupIds->take($this->limit);
        }

        $processed = 0;
        $failed = 0;

        foreach ($groupIds as $gid) {
            $fb = SocialPost::where('group_id', $gid)
                ->where('status', 'scheduled')
                ->where('platform', 'facebook')
                ->where('post_type', 'post')
                ->first();
            if (!$fb) continue;

            $topic = $fb->metadata['topic'] ?? mb_substr(strip_tags((string) $fb->content), 0, 200);
            if (!$topic) continue;

            try {
                $fbResult = $gemini->generatePost('facebook', $topic, [], 'ro');
                if (!empty($fbResult['content'])) {
                    $fb->update([
                        'content' => $fbResult['content'],
                        'hashtags' => $fbResult['hashtags'] ?? $fb->hashtags,
                    ]);
                }

                // Instagram sibling gets its own platform-style caption.
                $ig = SocialPost::where('group_id', $gid)
                    ->where('status', 'scheduled')
                    ->where('platform', 'instagram')
                    ->where('post_type', 'post')
                    ->first();
                if ($ig) {
                    $igResult = $gemini->generatePost('instagram', $topic, [], 'ro');
                    if (!empty($igResult['content'])) {
                        $ig->update([
                            'content' => $igResult['content'],
                            'hashtags' => $igResult['hashtags'] ?? $ig->hashtags,
                        ]);
                    }
                }

                $processed++;
                sleep(1);
            } catch (\Throwable $e) {
                Log::warning('RegenerateScheduledCaptionsJob: group failed', [
                    'group_id' => $gid,
                    'error' => $e->getMessage(),
                ]);
                $failed++;
            }
        }

        Log::info('RegenerateScheduledCaptionsJob: finished', [
            'processed' => $processed,
            'failed' => $failed,
        ]);
    }
}
