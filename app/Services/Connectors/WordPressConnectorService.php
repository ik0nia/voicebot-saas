<?php

namespace App\Services\Connectors;

use App\Models\BotKnowledge;
use App\Models\KnowledgeConnector;
use App\Jobs\ProcessKnowledgeDocument;
use App\Services\Security\SsrfGuard;
use Illuminate\Support\Facades\Http;

class WordPressConnectorService
{
    public function testConnection(KnowledgeConnector $connector): bool
    {
        try {
            SsrfGuard::validateUrl($connector->site_url);

            $response = Http::timeout(10)->get(
                rtrim($connector->site_url, '/') . '/wp-json/wp/v2/posts?per_page=1'
            );
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    public function sync(KnowledgeConnector $connector): int
    {
        // SSRF protection: re-validate URL at sync time (defense in depth)
        SsrfGuard::validateUrl($connector->site_url);

        $connector->update(['status' => 'syncing']);
        $imported = 0;

        try {
            $baseUrl = rtrim($connector->site_url, '/');
            $settings = $connector->sync_settings ?? ['posts' => true, 'pages' => true];

            if ($settings['pages'] ?? true) {
                $imported += $this->importEndpoint($connector, $baseUrl . '/wp-json/wp/v2/pages', 'page');
            }

            if ($settings['posts'] ?? true) {
                $imported += $this->importEndpoint($connector, $baseUrl . '/wp-json/wp/v2/posts', 'post');
            }

            $connector->update([
                'status' => 'connected',
                'last_synced_at' => now(),
            ]);

        } catch (\Exception $e) {
            $connector->update(['status' => 'error']);
            throw $e;
        }

        return $imported;
    }

    private function importEndpoint(KnowledgeConnector $connector, string $endpoint, string $type): int
    {
        $imported = 0;
        $page = 1;
        $perPage = 20;

        do {
            $response = Http::timeout(30)->get($endpoint, [
                'per_page' => $perPage,
                'page' => $page,
                'status' => 'publish',
            ]);

            if (!$response->successful()) break;

            $items = $response->json();
            if (empty($items)) break;

            foreach ($items as $item) {
                $title = html_entity_decode(strip_tags($item['title']['rendered'] ?? ''), ENT_QUOTES, 'UTF-8');
                $content = strip_tags($item['content']['rendered'] ?? '');
                $content = html_entity_decode($content, ENT_QUOTES, 'UTF-8');
                $content = preg_replace('/\s+/', ' ', $content);
                $content = trim($content);

                if (strlen($content) < 50) continue;

                $knowledge = BotKnowledge::create([
                    'bot_id' => $connector->bot_id,
                    'type' => 'text',
                    'source_type' => 'connector',
                    'source_id' => $connector->id,
                    'title' => "[WP {$type}] " . $title,
                    'content' => $content,
                    'status' => 'pending',
                    'metadata' => [
                        'connector_id' => $connector->id,
                        'connector_type' => 'wordpress',
                        'wp_type' => $type,
                        'wp_id' => $item['id'],
                        'wp_url' => $item['link'] ?? '',
                    ],
                ]);

                ProcessKnowledgeDocument::dispatch($knowledge);
                $imported++;
            }

            $page++;
            $totalPages = (int) ($response->header('X-WP-TotalPages') ?? 1);
        } while ($page <= $totalPages);

        return $imported;
    }
}
