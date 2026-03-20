<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CallResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'bot' => new BotResource($this->whenLoaded('bot')),
            'caller_number' => $this->caller_number,
            'direction' => $this->direction,
            'status' => $this->status,
            'duration_seconds' => $this->duration_seconds,
            'cost_cents' => $this->cost_cents,
            'recording_url' => $this->recording_url,
            'metadata' => $this->metadata,
            'started_at' => $this->started_at?->toIso8601String(),
            'ended_at' => $this->ended_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'transcripts' => TranscriptResource::collection($this->whenLoaded('transcripts')),
        ];
    }
}
