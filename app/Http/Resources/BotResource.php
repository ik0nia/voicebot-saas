<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BotResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'language' => $this->language,
            'voice' => $this->voice,
            'system_prompt' => $this->system_prompt,
            'is_active' => $this->is_active,
            'calls_count' => $this->when($this->calls_count !== null, $this->calls_count),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
