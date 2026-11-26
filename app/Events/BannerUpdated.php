<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Broadcasting\ShouldRescue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BannerUpdated implements ShouldBroadcastNow, ShouldRescue
{
    use Dispatchable, SerializesModels;

    public function broadcastOn(): array
    {
        return [new Channel('meeting-banner')];
    }

    public function broadcastAs(): string
    {
        return 'banner.updated';
    }
}
