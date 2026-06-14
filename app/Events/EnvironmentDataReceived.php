<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EnvironmentDataReceived implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $data;

    public function __construct(array $data)
    {
        array_walk_recursive($data, function (&$value) {
            if (is_float($value) && (is_infinite($value) || is_nan($value))) {
                $value = 0.0;
            }
        });

        $this->data = $data;
    }

    public function broadcastOn(): array
    {
        return [new Channel('environment-monitor')];
    }

    public function broadcastAs(): string
    {
        return 'data.received';
    }
}
