<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderConfirmed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public bool $afterCommit = true;

    /**
     * Create a new event instance.
     *
     * @param  Order  $order
     * @param  string  $station  'bar' | 'kitchen'
     */
    public function __construct(
        public Order $order,
        public string $station,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel($this->station)];
    }

    public function broadcastAs(): string
    {
        return 'order.confirmed';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->order->id,
            'number' => $this->order->number,
            'table' => $this->order->table_label,
            'customer' => $this->order->customer_name ?: 'Walk-in',
            'items' => $this->order->itemsForStation($this->station)
                ->map(fn ($i) => [
                    'name' => $i->name,
                    'qty' => $i->qty,
                    'note' => $i->note,
                ])->values()->all(),
            'sound' => 'ticket',
            'station' => $this->station,
            'created_at' => $this->order->created_at->format('H:i'),
        ];
    }
}
