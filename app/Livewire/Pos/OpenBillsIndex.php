<?php

namespace App\Livewire\Pos;

use App\Actions\PayOrder;
use App\Actions\VerifyManagerPinAndVoid;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class OpenBillsIndex extends Component
{
    public string $tab = 'unpaid'; // unpaid | settled
    public string $search = '';

    // Payment Settlement Modal
    public bool $showPayModal = false;
    public ?int $selectedOrderId = null;
    public string $paymentMethod = 'cash'; // cash | qris
    public int $cashReceived = 0;
    public string $qrisReference = '';

    // Print Bill Modal (BELUM DIBAYAR)
    public bool $showPrintBillModal = false;
    public ?int $printBillOrderId = null;

    // Void Modal (KF-07)
    public bool $showVoidModal = false;
    public ?int $voidOrderId = null;
    public string $voidReason = 'input_error';
    public string $voidDetail = '';
    public string $managerPin = '';
    public ?int $selectedManagerId = null;

    /**
     * Open settlement modal.
     */
    public function openPayModal(int $orderId): void
    {
        $order = Order::findOrFail($orderId);
        $this->selectedOrderId = $order->id;
        $this->cashReceived = $order->total;
        $this->paymentMethod = 'cash';
        $this->qrisReference = '';
        $this->showPayModal = true;
    }

    /**
     * Process settlement for open bill.
     */
    public function settlePayment(PayOrder $payOrder): void
    {
        $order = Order::findOrFail($this->selectedOrderId);

        if ($this->paymentMethod === 'cash' && $this->cashReceived < $order->total) {
            $this->addError('cashReceived', 'Nominal uang tunai kurang dari total tagihan.');
            return;
        }

        $payOrder->execute(
            order: $order,
            method: $this->paymentMethod === 'cash' ? PaymentMethod::Cash : PaymentMethod::Qris,
            cashReceived: $this->paymentMethod === 'cash' ? $this->cashReceived : $order->total,
            reference: $this->paymentMethod === 'qris' ? $this->qrisReference : null,
        );

        $this->showPayModal = false;
        $this->selectedOrderId = null;
        session()->flash('success', "Open Bill #{$order->number} berhasil dilunasi.");
    }

    /**
     * Open printable bill modal (KF-21).
     */
    public function openPrintBillModal(int $orderId): void
    {
        $this->printBillOrderId = $orderId;
        $this->showPrintBillModal = true;
    }

    /**
     * Open Void Modal with Manager PIN.
     */
    public function openVoidModal(int $orderId): void
    {
        $this->voidOrderId = $orderId;
        $this->voidReason = 'input_error';
        $this->voidDetail = '';
        $this->managerPin = '';
        $this->selectedManagerId = null;
        $this->resetErrorBag();
        $this->showVoidModal = true;
    }

    /**
     * Append digit to PIN from Numpad.
     */
    public function appendPin(string $digit): void
    {
        if (strlen($this->managerPin) < 6) {
            $this->managerPin .= $digit;
        }
    }

    public function clearPin(): void
    {
        $this->managerPin = '';
    }

    public function backspacePin(): void
    {
        $this->managerPin = substr($this->managerPin, 0, -1);
    }

    /**
     * Confirm void with manager PIN.
     */
    public function confirmVoid(VerifyManagerPinAndVoid $verifyAndVoid): void
    {
        $this->validate([
            'managerPin' => ['required', 'string', 'min:4', 'max:6'],
            'voidReason' => ['required', 'string'],
        ], [
            'managerPin.required' => 'PIN Manajer wajib diisi.',
            'managerPin.min' => 'PIN minimal 4 digit.',
        ]);

        $order = Order::findOrFail($this->voidOrderId);

        try {
            $verifyAndVoid->execute(
                order: $order,
                requester: auth()->user(),
                pin: $this->managerPin,
                reason: $this->voidReason,
                reasonDetail: $this->voidDetail,
                managerId: $this->selectedManagerId,
            );

            $this->showVoidModal = false;
            $this->voidOrderId = null;
            session()->flash('success', "Pesanan #{$order->number} berhasil dibatalkan (Void) dan stok telah dikembalikan.");
        } catch (\InvalidArgumentException $e) {
            $this->addError('managerPin', $e->getMessage());
        }
    }

    public function render()
    {
        $query = Order::with(['table', 'cashier', 'payments', 'items'])
            ->where('is_open_bill', true);

        if ($this->tab === 'unpaid') {
            $query->where('payment_status', PaymentStatus::Unpaid);
        } else {
            $query->where('payment_status', PaymentStatus::Paid);
        }

        if (trim($this->search)) {
            $query->where(function ($q) {
                $q->where('number', 'like', '%' . trim($this->search) . '%')
                  ->orWhere('customer_name', 'like', '%' . trim($this->search) . '%')
                  ->orWhere('open_bill_note', 'like', '%' . trim($this->search) . '%');
            });
        }

        $orders = $query->latest()->get();
        $totalUnpaidAmount = Order::where('is_open_bill', true)
            ->where('payment_status', PaymentStatus::Unpaid)
            ->sum('total');

        $selectedOrder = $this->selectedOrderId ? Order::with(['items', 'table'])->find($this->selectedOrderId) : null;
        $printOrder = $this->printBillOrderId ? Order::with(['items', 'table', 'cashier'])->find($this->printBillOrderId) : null;
        $voidOrder = $this->voidOrderId ? Order::find($this->voidOrderId) : null;

        $managers = User::permission('orders.void.approve')->where('is_active', true)->get();

        return view('livewire.pos.open-bills-index', [
            'orders' => $orders,
            'totalUnpaidAmount' => $totalUnpaidAmount,
            'selectedOrder' => $selectedOrder,
            'printOrder' => $printOrder,
            'voidOrder' => $voidOrder,
            'managers' => $managers,
        ])->title('Manajemen Open Bill & Piutang');
    }
}
