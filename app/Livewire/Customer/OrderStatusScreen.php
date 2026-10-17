<?php

namespace App\Livewire\Customer;

use App\Enums\OrderStatus;
use App\Models\Order;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.customer')]
class OrderStatusScreen extends Component
{
    public string $orderNumber = '';
    public ?Order $order = null;

    public function mount(string $orderNumber): void
    {
        $this->orderNumber = $orderNumber;
        $this->loadOrder();
    }

    public function loadOrder(): void
    {
        $this->order = Order::with(['items', 'table', 'payments', 'void'])
            ->where('number', $this->orderNumber)
            ->first();
    }

    public function refreshStatus(): void
    {
        $this->loadOrder();
    }

    public function render()
    {
        // Re-load on every poll/render
        $this->loadOrder();

        return view('livewire.customer.order-status-screen', [
            'order' => $this->order,
        ]);
    }
}
