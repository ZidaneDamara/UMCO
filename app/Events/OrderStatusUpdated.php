<?php

namespace App\Events;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public bool $afterCommit = true;

    public function __construct(
        public Order $order,
        public OrderStatus $status,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('cashier'),
            new \Illuminate\Broadcasting\Channel('order.' . $this->order->number),
        ];
    }

    public function broadcastAs(): string
    {
        return 'order.status';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->order->id,
            'number' => $this->order->number,
            'status' => $this->status->value,
            'table' => $this->order->table_label,
            'sound' => $this->status === OrderStatus::Ready ? 'ready' : null,
        ];
    }
}
