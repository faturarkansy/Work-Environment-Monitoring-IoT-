<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WorkerLogUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    // Amankan data berpotensi inf/nan khusus jika ada kalkulasi pmv yang ekstrem
    public array $logData;

    public function __construct(array $logData)
    {
        array_walk_recursive($logData, function (&$value) {
            if (is_float($value) && (is_infinite($value) || is_nan($value))) {
                $value = 0.0;
            }
        });
        $this->logData = $logData;
    }

    public function broadcastOn(): array
    {
        return [new Channel('environment-monitor')];
    }

    public function broadcastAs(): string
    {
        return 'worker.updated';
    }
}
