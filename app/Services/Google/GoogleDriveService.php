<?php

namespace App\Services\Google;

use App\Models\GoogleOAuthToken;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Thin wrapper over Google Drive REST v3.
 *
 * Scope: drive.file (only files the app created or that the user picked
 * via Google Picker are visible). We do NOT use drive.readonly anywhere.
 */
class GoogleDriveService
{
    private const FILES_ENDPOINT = 'https://www.googleapis.com/drive/v3/files';
    private const UPLOAD_ENDPOINT = 'https://www.googleapis.com/upload/drive/v3/files';

    /**
     * Native Google MIME types → export MIME for plain-text/CSV download.
     * For native Google formats we use the /export endpoint instead of /alt=media.
     */
    private const EXPORT_MAP = [
        'application/vnd.google-apps.document' => 'text/plain',
        'application/vnd.google-apps.spreadsheet' => 'text/csv',
        'application/vnd.google-apps.presentation' => 'text/plain',
    ];

    public function __construct(private GoogleOAuthService $oauth) {}

    /**
     * Get metadata for a single file by ID.
     */
    public function getFileMetadata(GoogleOAuthToken $token, string $fileId): array
    {
        $access = $this->oauth->getValidAccessToken($token);

        $response = Http::withToken($access)->get(self::FILES_ENDPOINT . '/' . $fileId, [
            'fields' => 'id,name,mimeType,iconLink,webViewLink,modifiedTime,size',
            'supportsAllDrives' => 'true',
        ]);

        if (! $response->successful()) {
            throw new RuntimeException("Nu am putut prelua metadatele fișierului Drive ($fileId): " . $response->body());
        }

        return $response->json();
    }

    /**
     * Download a Drive file (or export it for native Google formats) and store
     * it under storage/app/knowledge/google-drive/. Returns the relative path
     * (compatible with the existing ProcessKnowledgeDocument extractor).
     *
     * Returns: ['path' => string|null, 'text' => string|null, 'kb_type' => 'pdf'|'docx'|'txt'|'csv'|'text']
     *
     * For binary formats (pdf/docx) we save to disk and let the existing
     * extractor handle them. For text formats we just hand back the string.
     */
    public function downloadForKnowledge(GoogleOAuthToken $token, string $fileId, string $name, string $mimeType): array
    {
        $access = $this->oauth->getValidAccessToken($token);

        // Native Google format → export
        if (isset(self::EXPORT_MAP[$mimeType])) {
            $exportMime = self::EXPORT_MAP[$mimeType];

            $response = Http::withToken($access)
                ->withOptions(['stream' => false])
                ->get(self::FILES_ENDPOINT . '/' . $fileId . '/export', [
                    'mimeType' => $exportMime,
                ]);

            if (! $response->successful()) {
                throw new RuntimeException("Export Google Doc/Sheet a eșuat: " . $response->body());
            }

            return [
                'path' => null,
                'text' => $response->body(),
                'kb_type' => $exportMime === 'text/csv' ? 'csv' : 'text',
            ];
        }

        // Binary download via alt=media
        $response = Http::withToken($access)
            ->withOptions(['stream' => false])
            ->get(self::FILES_ENDPOINT . '/' . $fileId, [
                'alt' => 'media',
                'supportsAllDrives' => 'true',
            ]);

        if (! $response->successful()) {
            throw new RuntimeException("Download Drive a eșuat ($fileId): " . $response->body());
        }

        $kbType = $this->mimeToKbType($mimeType, $name);

        // Plain text payloads can be returned directly without hitting disk.
        if ($kbType === 'text') {
            return [
                'path' => null,
                'text' => $response->body(),
                'kb_type' => 'text',
            ];
        }

        // Binary: save to local disk under knowledge/google-drive/<unique>.<ext>
        $extension = pathinfo($name, PATHINFO_EXTENSION) ?: $this->mimeToExtension($mimeType);
        $relativePath = 'knowledge/google-drive/' . Str::uuid() . '.' . $extension;

        Storage::disk('local')->put($relativePath, $response->body());

        return [
            'path' => $relativePath,
            'text' => null,
            'kb_type' => $kbType,
        ];
    }

    /**
     * Create a "Sambla Knowledge Base" folder in the user's Drive on first
     * connect, as a convention. The user can then drop files there manually
     * and select them via Picker. We store the folder ID for the future.
     */
    public function createKbFolder(GoogleOAuthToken $token): ?string
    {
        $access = $this->oauth->getValidAccessToken($token);

        $folderName = config('services.google.kb_folder_name', 'Sambla Knowledge Base');

        try {
            $response = Http::withToken($access)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post(self::FILES_ENDPOINT, [
                    'name' => $folderName,
                    'mimeType' => 'application/vnd.google-apps.folder',
                ]);

            if ($response->successful()) {
                return $response->json('id');
            }

            Log::warning('Google Drive folder creation failed', [
                'tenant_id' => $token->tenant_id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Google Drive folder creation exception', [
                'tenant_id' => $token->tenant_id,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Map a Drive MIME type to the bot_knowledge.type column value the
     * existing extractor (ProcessKnowledgeDocument) understands.
     */
    private function mimeToKbType(string $mime, string $name): string
    {
        $mime = strtolower($mime);
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        if ($mime === 'application/pdf' || $ext === 'pdf') {
            return 'pdf';
        }
        if ($mime === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' || $ext === 'docx') {
            return 'docx';
        }
        if ($mime === 'text/csv' || $ext === 'csv') {
            return 'csv';
        }
        if (str_starts_with($mime, 'text/') || in_array($ext, ['txt', 'md'], true)) {
            return 'text';
        }
        // Fall back to txt — the user picked it knowingly.
        return 'text';
    }

    private function mimeToExtension(string $mime): string
    {
        return match (strtolower($mime)) {
            'application/pdf' => 'pdf',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'text/csv' => 'csv',
            'text/plain' => 'txt',
            default => 'bin',
        };
    }
}
