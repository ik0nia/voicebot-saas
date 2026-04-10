<?php

namespace App\Jobs;

use App\Models\BotKnowledge;
use App\Models\GoogleDriveFile;
use App\Models\GoogleOAuthToken;
use App\Services\Google\GoogleDriveService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Imports a single Google Drive file into a bot's knowledge base:
 *   1. Downloads the file (or exports it for native Google formats)
 *   2. Creates a BotKnowledge row with the right type/content/metadata,
 *      tagged with the user-chosen category
 *   3. Dispatches ProcessKnowledgeDocument to embed it
 */
class ImportGoogleDriveFile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300;
    public array $backoff = [30, 120, 300];

    public function __construct(public GoogleDriveFile $driveFile)
    {
        $this->onQueue('knowledge');
    }

    public function handle(GoogleDriveService $drive): void
    {
        $this->driveFile->update(['status' => 'importing', 'error_message' => null]);

        $connector = $this->driveFile->connector;
        if (! $connector) {
            $this->driveFile->update(['status' => 'failed', 'error_message' => 'Conectorul a fost șters.']);
            return;
        }

        $bot = $connector->bot;
        $tenant = $bot?->tenant;
        if (! $bot || ! $tenant) {
            $this->driveFile->update(['status' => 'failed', 'error_message' => 'Botul sau tenantul lipsește.']);
            return;
        }

        $token = GoogleOAuthToken::where('tenant_id', $tenant->id)->first();
        if (! $token) {
            $this->driveFile->update(['status' => 'failed', 'error_message' => 'Tenantul nu mai este conectat la Google.']);
            return;
        }

        try {
            $download = $drive->downloadForKnowledge(
                $token,
                $this->driveFile->drive_file_id,
                $this->driveFile->name,
                $this->driveFile->mime_type ?? 'application/octet-stream',
            );

            $categoryConfig = config("google-drive-categories.{$this->driveFile->category}")
                ?? config('google-drive-categories.other');
            $categoryLabel = $categoryConfig['label'];
            $categoryPrompt = $categoryConfig['prompt'];

            // Prefix the title with the category label so it's visible in the docs list.
            $title = "[{$categoryLabel}] " . $this->driveFile->name;

            $metadata = [
                'source' => 'google_drive',
                'connector_id' => $connector->id,
                'drive_file_id' => $this->driveFile->drive_file_id,
                'drive_file_name' => $this->driveFile->name,
                'drive_mime_type' => $this->driveFile->mime_type,
                'drive_web_view_link' => $this->driveFile->web_view_link,
                'kb_category' => $this->driveFile->category,
                'kb_category_label' => $categoryLabel,
                'kb_category_prompt' => $categoryPrompt,
                'user_description' => $this->driveFile->user_description,
            ];

            // Build the BotKnowledge row.
            // For text-based payloads we can prepend the category prompt + user
            // description directly into content (which becomes part of the
            // embedding). For binary payloads we just point at the stored file
            // path and rely on metadata + title for category context.
            if ($download['text'] !== null) {
                $content = $this->buildTextContent(
                    $categoryPrompt,
                    $this->driveFile->user_description,
                    $download['text'],
                );

                $knowledge = BotKnowledge::create([
                    'bot_id' => $bot->id,
                    'type' => $download['kb_type'], // text or csv
                    'source_type' => 'connector',
                    'source_id' => $connector->id,
                    'title' => $title,
                    'content' => $content,
                    'status' => 'pending',
                    'metadata' => $metadata,
                ]);
            } else {
                $knowledge = BotKnowledge::create([
                    'bot_id' => $bot->id,
                    'type' => $download['kb_type'], // pdf, docx
                    'source_type' => 'connector',
                    'source_id' => $connector->id,
                    'title' => $title,
                    'content' => $download['path'], // existing pipeline expects a relative path
                    'status' => 'pending',
                    'metadata' => $metadata,
                ]);
            }

            $this->driveFile->update([
                'status' => 'imported',
                'knowledge_id' => $knowledge->id,
                'last_synced_at' => now(),
            ]);

            ProcessKnowledgeDocument::dispatch($knowledge);
        } catch (\Throwable $e) {
            Log::error('ImportGoogleDriveFile failed', [
                'drive_file_row_id' => $this->driveFile->id,
                'drive_file_id' => $this->driveFile->drive_file_id,
                'error' => $e->getMessage(),
            ]);
            $this->driveFile->update([
                'status' => 'failed',
                'error_message' => mb_substr($e->getMessage(), 0, 1000),
            ]);
            throw $e;
        }
    }

    private function buildTextContent(string $categoryPrompt, ?string $userDescription, string $text): string
    {
        $header = "[CONTEXT KNOWLEDGE BASE]\n" . $categoryPrompt;
        if ($userDescription) {
            $header .= "\nNotă de la administrator: " . trim($userDescription);
        }
        $header .= "\n[/CONTEXT]\n\n";

        return $header . $text;
    }
}
