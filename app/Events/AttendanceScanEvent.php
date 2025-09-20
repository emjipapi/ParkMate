<?php

// app/Events/AttendanceScanEvent.php
namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AttendanceScanEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $scanData;

    public function __construct($scanData)
    {
        $this->scanData = $scanData;
    }

    public function broadcastOn()
    {
        return new Channel('attendance-updates');
    }

    public function broadcastAs()
    {
        return 'scan.updated';
    }

    public function broadcastWith()
    {
        return [
            'scan' => $this->scanData,
            'timestamp' => now()->toISOString()
        ];
    }
}