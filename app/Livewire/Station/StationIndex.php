<?php

namespace App\Livewire\Station;

use App\Enums\OrderStatus;
use App\Enums\StationType;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class StationIndex extends Component
{
    public string $station = 'bar'; // 'bar' | 'kitchen'

    public function mount(string $station = 'bar'): void
    {
        $this->station = $station;
    }

    /**
     * Advance order status (confirmed -> preparing -> ready -> completed).
     */
    public function advanceStatus(int $orderId): void
    {
        $order = Order::findOrFail($orderId);

        $nextStatus = match ($order->status) {
            OrderStatus::Confirmed => OrderStatus::Preparing,
            OrderStatus::Preparing => OrderStatus::Ready,
            OrderStatus::Ready => OrderStatus::Completed,
            default => null,
        };

        if ($nextStatus) {
            $order->status = $nextStatus;
            $order->save();

            // Update item statuses for this station
            $stationEnum = $this->station === 'bar' ? StationType::Bar : StationType::Kitchen;
            $order->items()
                ->where('station', $stationEnum)
                ->update(['status' => $nextStatus->value]);

            event(new \App\Events\OrderStatusUpdated($order, $nextStatus));
        }
    }

    public function render()
    {
        $stationEnum = $this->station === 'bar' ? StationType::Bar : StationType::Kitchen;

        // Fetch active orders that have items for this station
        $orders = Order::whereIn('status', [
                OrderStatus::Confirmed,
                OrderStatus::Preparing,
                OrderStatus::Ready,
            ])
            ->whereHas('items', function ($query) use ($stationEnum) {
                $query->where('station', $stationEnum);
            })
            ->with(['table', 'cashier', 'items' => function ($query) use ($stationEnum) {
                $query->where('station', $stationEnum);
            }])
            ->oldest()
            ->get();

        $newOrders = $orders->where('status', OrderStatus::Confirmed);
        $preparingOrders = $orders->where('status', OrderStatus::Preparing);
        $readyOrders = $orders->where('status', OrderStatus::Ready);

        $stationTitle = $this->station === 'bar' ? 'Layar Produksi Bar (Minuman)' : 'Layar Dapur UMC (Makanan)';

        return view('livewire.station.station-index', [
            'stationTitle' => $stationTitle,
            'newOrders' => $newOrders,
            'preparingOrders' => $preparingOrders,
            'readyOrders' => $readyOrders,
        ])->title($stationTitle);
    }
}
