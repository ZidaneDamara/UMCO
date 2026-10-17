<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderPaid implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public bool $afterCommit = true;

    public function __construct(
        public Order $order,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('cashier')];
    }

    public function broadcastAs(): string
    {
        return 'order.paid';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->order->id,
            'number' => $this->order->number,
            'total' => $this->order->total,
            'customer' => $this->order->customer_name ?: 'Walk-in',
            'sound' => 'payment-success',
        ];
    }
}
