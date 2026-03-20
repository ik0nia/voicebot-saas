<?php

namespace App\Events;

use App\Models\Bot;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BotStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Bot $bot) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('tenant.' . $this->bot->tenant_id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->bot->id,
            'name' => $this->bot->name,
            'is_active' => $this->bot->is_active,
        ];
    }
}
