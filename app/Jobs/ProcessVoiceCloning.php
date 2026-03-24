<?php

namespace App\Jobs;

use App\Events\VoiceCloningStatusChanged;
use App\Models\ClonedVoice;
use App\Services\ElevenLabsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessVoiceCloning implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public array $backoff = [30, 120];
    public int $timeout = 180;

    public function __construct(
        private int $clonedVoiceId,
    ) {
        $this->onQueue('default');
    }

    public function handle(ElevenLabsService $elevenLabs): void
    {
        $voice = ClonedVoice::find($this->clonedVoiceId);

        if (!$voice || $voice->status === ClonedVoice::STATUS_READY) {
            return;
        }

        $voice->update(['status' => ClonedVoice::STATUS_PROCESSING]);
        VoiceCloningStatusChanged::dispatch($voice);

        if (!$elevenLabs->isConfigured()) {
            $voice->update([
                'status' => ClonedVoice::STATUS_FAILED,
                'error_message' => 'ElevenLabs API key nu este configurat.',
            ]);
            VoiceCloningStatusChanged::dispatch($voice->fresh());
            return;
        }

        $audioPath = Storage::path($voice->audio_path);

        if (!file_exists($audioPath)) {
            $voice->update([
                'status' => ClonedVoice::STATUS_FAILED,
                'error_message' => 'Fișierul audio nu a fost găsit.',
            ]);
            VoiceCloningStatusChanged::dispatch($voice->fresh());
            return;
        }

        $result = $elevenLabs->createVoice($voice->name, $audioPath);

        if ($result && !empty($result['voice_id'])) {
            $voice->update([
                'status' => ClonedVoice::STATUS_READY,
                'elevenlabs_voice_id' => $result['voice_id'],
                'metadata' => array_merge($voice->metadata ?? [], [
                    'created_at_elevenlabs' => now()->toIso8601String(),
                ]),
            ]);

            Log::info('Voice cloning completed', [
                'voice_id' => $voice->id,
                'elevenlabs_id' => $result['voice_id'],
            ]);
        } else {
            $voice->update([
                'status' => ClonedVoice::STATUS_FAILED,
                'error_message' => 'Clonarea vocii a eșuat. Verificați calitatea înregistrării.',
            ]);
        }

        VoiceCloningStatusChanged::dispatch($voice->fresh());
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessVoiceCloning failed permanently', [
            'voice_id' => $this->clonedVoiceId,
            'error' => $exception->getMessage(),
        ]);

        $voice = ClonedVoice::find($this->clonedVoiceId);
        if ($voice) {
            $voice->update([
                'status' => ClonedVoice::STATUS_FAILED,
                'error_message' => 'Eroare internă la clonarea vocii. Încercați din nou.',
            ]);
            VoiceCloningStatusChanged::dispatch($voice);
        }
    }
}
