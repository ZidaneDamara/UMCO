<?php

namespace App\Events;

use App\Enums\OrderSource;
use App\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public bool $afterCommit = true;

    public function __construct(
        public Order $order,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('cashier'),
            new PrivateChannel('manager'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'order.created';
    }

    public function broadcastWith(): array
    {
        $this->order->loadMissing(['items', 'table']);

        $sourceVal = $this->order->source instanceof OrderSource
            ? $this->order->source->value
            : (string) $this->order->source;

        return [
            'id' => $this->order->id,
            'number' => $this->order->number,
            'source' => $sourceVal,
            'table' => $this->order->table_label,
            'customer' => $this->order->customer_name ?: 'Pelanggan ' . $this->order->table_label,
            'total' => $this->order->total,
            'items_count' => (int) $this->order->items->sum('qty'),
            'sound' => 'new-order',
            'created_at' => $this->order->created_at?->format('H:i') ?? now()->format('H:i'),
        ];
    }
}
