<?php

namespace App\Livewire\Customer;

use App\Models\Order;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.customer')]
class CustomerDisplay extends Component
{
    public ?Order $activeOrder = null;
    public string $viewMode = 'idle'; // 'idle' | 'checkout' | 'paid'

    public function mount(): void
    {
        $this->refreshDisplay();
    }

    public function refreshDisplay(): void
    {
        // Find latest active order created or updated in the last 10 minutes
        $latest = Order::with(['items', 'table', 'payments'])
            ->latest('updated_at')
            ->first();

        if ($latest && $latest->updated_at->diffInMinutes(now()) <= 10) {
            $this->activeOrder = $latest;
            if ($latest->payment_status->value === 'paid' && $latest->paid_at?->diffInSeconds(now()) <= 60) {
                $this->viewMode = 'paid';
            } elseif ($latest->status->value === 'pending_confirmation' || $latest->payment_status->value === 'unpaid') {
                $this->viewMode = 'checkout';
            } else {
                $this->viewMode = 'idle';
            }
        } else {
            $this->activeOrder = null;
            $this->viewMode = 'idle';
        }
    }

    public function render()
    {
        $this->refreshDisplay();

        return view('livewire.customer.customer-display', [
            'order' => $this->activeOrder,
        ]);
    }
}
