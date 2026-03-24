<?php

namespace App\Events;

use App\Models\ClonedVoice;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VoiceCloningStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ClonedVoice $clonedVoice) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('tenant.' . $this->clonedVoice->tenant_id)];
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->clonedVoice->id,
            'name' => $this->clonedVoice->name,
            'status' => $this->clonedVoice->status,
            'error_message' => $this->clonedVoice->error_message,
        ];
    }
}
