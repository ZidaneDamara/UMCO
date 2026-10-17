<?php

namespace App\Events;

use App\Models\Order;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VoidRequested implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public bool $afterCommit = true;

    public function __construct(
        public Order $order,
        public User $requester,
        public string $reason,
        public ?string $reasonDetail = null,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('manager')];
    }

    public function broadcastAs(): string
    {
        return 'void.requested';
    }

    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->order->id,
            'order_number' => $this->order->number,
            'total' => $this->order->total,
            'customer' => $this->order->customer_name ?: 'Walk-in',
            'requester_name' => $this->requester->name,
            'reason' => $this->reason,
            'reason_detail' => $this->reasonDetail,
            'sound' => 'alert',
        ];
    }
}
