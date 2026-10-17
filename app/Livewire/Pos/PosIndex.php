<?php

namespace App\Livewire\Pos;

use App\Actions\CreateOrder;
use App\Actions\PayOrder;
use App\Enums\MenuType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Category;
use App\Models\Discount;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Table;
use App\Services\DiscountEngine;
use App\Services\StockService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class PosIndex extends Component
{
    // Search and filter
    public string $search = '';
    public ?int $selectedCategoryId = null;

    // Cart state
    public array $cart = []; // list of items
    public ?string $customerName = '';
    public ?int $selectedTableId = null;
    public ?int $selectedDiscountId = null;

    // Custom Item Modal
    public bool $showCustomModal = false;
    public string $customName = '';
    public int $customPrice = 0;
    public string $customType = 'drink';

    // Open Bill Modal
    public bool $showOpenBillModal = false;
    public string $openBillNote = '';

    // Payment Modal
    public bool $showPaymentModal = false;
    public string $paymentMethod = 'cash'; // 'cash' | 'qris'
    public int $cashReceived = 0;
    public string $qrisReference = '';

    // Receipt Modal
    public bool $showReceiptModal = false;
    public ?int $completedOrderId = null;

    // Today's Orders Drawer
    public bool $showOrdersDrawer = false;
    public string $ordersFilter = 'all';

    // Pending Self-Service Orders (KF-33)
    public bool $showPendingOrdersModal = false;
    public ?int $selectedPendingOrderId = null;
    public string $rejectReason = 'Dibatalkan oleh kasir';

    public function mount(): void
    {
        // Default table selection (Takeaway = null)
    }

    /**
     * Add menu item to cart or increment qty.
     */
    public function addToCart(int $menuItemId): void
    {
        $menuItem = MenuItem::find($menuItemId);
        if (! $menuItem || ! $menuItem->is_available) {
            return;
        }

        // Check if item already in cart without specific notes
        foreach ($this->cart as $key => $item) {
            if (($item['menu_item_id'] ?? null) === $menuItem->id && empty($item['note'])) {
                $this->cart[$key]['qty'] += 1;
                return;
            }
        }

        $key = 'item_' . $menuItem->id . '_' . uniqid();
        $this->cart[$key] = [
            'key' => $key,
            'menu_item_id' => $menuItem->id,
            'name' => $menuItem->name,
            'type' => $menuItem->type instanceof MenuType ? $menuItem->type->value : (string) $menuItem->type,
            'qty' => 1,
            'unit_price' => $menuItem->price,
            'note' => '',
            'is_custom' => false,
        ];
    }

    /**
     * Add custom product (KF-14).
     */
    public function addCustomItem(): void
    {
        $this->validate([
            'customName' => ['required', 'string', 'min:2', 'max:100'],
            'customPrice' => ['required', 'integer', 'min:1000'],
            'customType' => ['required', 'in:drink,food'],
        ], [
            'customName.required' => 'Nama pesanan custom wajib diisi.',
            'customPrice.min' => 'Harga minimal Rp1.000.',
        ]);

        $key = 'custom_' . uniqid();
        $this->cart[$key] = [
            'key' => $key,
            'menu_item_id' => null,
            'name' => $this->customName . ' (Custom)',
            'type' => $this->customType,
            'qty' => 1,
            'unit_price' => $this->customPrice,
            'note' => '',
            'is_custom' => true,
        ];

        $this->customName = '';
        $this->customPrice = 0;
        $this->customType = 'drink';
        $this->showCustomModal = false;
    }

    /**
     * Change quantity for cart item.
     */
    public function updateQty(string $key, int $delta): void
    {
        if (! isset($this->cart[$key])) {
            return;
        }

        $newQty = $this->cart[$key]['qty'] + $delta;
        if ($newQty <= 0) {
            unset($this->cart[$key]);
        } else {
            $this->cart[$key]['qty'] = $newQty;
        }
    }

    /**
     * Remove item from cart.
     */
    public function removeItem(string $key): void
    {
        unset($this->cart[$key]);
    }

    /**
     * Append quick note chip (KF-15).
     */
    public function appendNoteChip(string $key, string $chip): void
    {
        if (! isset($this->cart[$key])) {
            return;
        }

        $current = trim($this->cart[$key]['note'] ?? '');
        if (str_contains($current, $chip)) {
            return;
        }

        $this->cart[$key]['note'] = $current ? "{$current}, {$chip}" : $chip;
    }

    /**
     * Clear the whole cart.
     */
    public function clearCart(): void
    {
        $this->cart = [];
        $this->customerName = '';
        $this->selectedTableId = null;
        $this->selectedDiscountId = null;
        $this->openBillNote = '';
    }

    /**
     * Computed cart summary with DiscountEngine.
     */
    public function getSummaryProperty(): array
    {
        if (empty($this->cart)) {
            return [
                'items' => [],
                'subtotal' => 0,
                'discount_total' => 0,
                'order_discount' => 0,
                'total' => 0,
                'total_qty' => 0,
            ];
        }

        $engine = app(DiscountEngine::class);
        $result = $engine->calculate(collect(array_values($this->cart)), $this->selectedDiscountId);

        $subtotal = collect($this->cart)->sum(fn ($i) => $i['unit_price'] * $i['qty']);
        $total = max(0, $subtotal - $result['discount_total']);
        $totalQty = collect($this->cart)->sum('qty');

        return [
            'items' => $result['items'],
            'subtotal' => $subtotal,
            'discount_total' => $result['discount_total'],
            'order_discount' => $result['order_discount'] ?? 0,
            'total' => $total,
            'total_qty' => $totalQty,
        ];
    }

    /**
     * Open Payment modal and prepopulate cash.
     */
    public function openPaymentModal(): void
    {
        $summary = $this->summary;
        if ($summary['total'] <= 0 && empty($this->cart)) {
            return;
        }

        $this->cashReceived = $summary['total'];
        $this->paymentMethod = 'cash';
        $this->qrisReference = '';
        $this->showPaymentModal = true;
    }

    /**
     * Set quick cash button amounts.
     */
    public function setQuickCash(int|string $amount): void
    {
        if ($amount === 'exact') {
            $this->cashReceived = $this->summary['total'];
        } else {
            $this->cashReceived = (int) $amount;
        }
    }

    /**
     * Process full payment (Tunai or QRIS).
     */
    public function processPayment(CreateOrder $createOrder, PayOrder $payOrder): void
    {
        $summary = $this->summary;

        if (empty($this->cart)) {
            return;
        }

        if ($this->paymentMethod === 'cash' && $this->cashReceived < $summary['total']) {
            $this->addError('cashReceived', 'Nominal uang tunai kurang dari total tagihan.');
            return;
        }

        // 1. Create order
        $order = $createOrder->execute([
            'customer_name' => $this->customerName ?: 'Pelanggan Walk-in',
            'table_id' => $this->selectedTableId,
            'is_open_bill' => false,
            'discount_id' => $this->selectedDiscountId,
            'items' => array_values($this->cart),
        ]);

        // 2. Pay order
        $payOrder->execute(
            order: $order,
            method: $this->paymentMethod === 'cash' ? PaymentMethod::Cash : PaymentMethod::Qris,
            cashReceived: $this->paymentMethod === 'cash' ? $this->cashReceived : $order->total,
            reference: $this->paymentMethod === 'qris' ? $this->qrisReference : null,
        );

        $this->completedOrderId = $order->id;
        $this->showPaymentModal = false;
        $this->showReceiptModal = true;
        $this->clearCart();
    }

    /**
     * Save order as Open Bill (KF-20).
     */
    public function saveOpenBill(CreateOrder $createOrder): void
    {
        $this->validate([
            'customerName' => ['required', 'string', 'min:2'],
            'openBillNote' => ['required', 'string', 'min:3'],
        ], [
            'customerName.required' => 'Nama pemesan / instansi wajib diisi untuk Open Bill.',
            'openBillNote.required' => 'Catatan kegiatan / keperluan wajib diisi untuk Open Bill.',
        ]);

        if (empty($this->cart)) {
            return;
        }

        $order = $createOrder->execute([
            'customer_name' => $this->customerName,
            'table_id' => $this->selectedTableId,
            'is_open_bill' => true,
            'open_bill_note' => $this->openBillNote,
            'discount_id' => $this->selectedDiscountId,
            'items' => array_values($this->cart),
        ]);

        $this->showOpenBillModal = false;
        $this->completedOrderId = $order->id;
        $this->showReceiptModal = true;
        $this->clearCart();

        session()->flash('success', "Open Bill #{$order->number} berhasil disimpan.");
    }

    /**
     * View/Reprint receipt from orders list.
     */
    public function viewReceipt(int $orderId): void
    {
        $this->completedOrderId = $orderId;
        $this->showReceiptModal = true;
        $this->showOrdersDrawer = false;
    }

    /**
     * Close receipt modal and start fresh.
     */
    public function closeReceiptModal(): void
    {
        $this->showReceiptModal = false;
        $this->completedOrderId = null;
    }

    /**
     * Accept a pending self-service order (KF-33).
     */
    public function acceptPendingOrder(int $orderId, StockService $stockService): void
    {
        $order = Order::with(['items.menuItem.recipes.ingredient', 'table'])->findOrFail($orderId);

        if ($order->status !== OrderStatus::PendingConfirmation) {
            session()->flash('error', 'Pesanan ini sudah diproses sebelumnya.');
            return;
        }

        $order->status = OrderStatus::Confirmed;
        $order->cashier_id = Auth::id() ?? $order->cashier_id;
        $order->save();

        // Deduct stock per DESIGN.md Section 8.5
        $stockService->deductForOrder($order);

        // Broadcast to bar & kitchen screens (generates tickets + sounds on screens)
        if ($order->itemsForStation('bar')->isNotEmpty()) {
            event(new \App\Events\OrderConfirmed($order, 'bar'));
        }
        if ($order->itemsForStation('kitchen')->isNotEmpty()) {
            event(new \App\Events\OrderConfirmed($order, 'kitchen'));
        }

        // Broadcast status update for customer screen
        event(new \App\Events\OrderStatusUpdated($order, OrderStatus::Confirmed));

        app(\App\Services\AuditService::class)->log(
            action: 'orders.accept_self_service',
            subject: $order,
            oldValues: ['status' => OrderStatus::PendingConfirmation->value],
            newValues: ['status' => OrderStatus::Confirmed->value]
        );

        session()->flash('success', "Pesanan #{$order->number} diterima dan tiket otomatis dikirim ke Barista/Dapur.");
    }

    /**
     * Reject a pending self-service order.
     */
    public function rejectPendingOrder(int $orderId): void
    {
        $order = Order::findOrFail($orderId);

        if ($order->status !== OrderStatus::PendingConfirmation) {
            return;
        }

        $order->status = OrderStatus::Void;
        $order->payment_status = PaymentStatus::Void;
        $order->save();

        \App\Models\OrderVoid::create([
            'order_id' => $order->id,
            'reason' => $this->rejectReason ?: 'Dibatalkan oleh kasir',
            'requested_by' => Auth::id() ?? 1,
            'approved_by' => Auth::id() ?? 1,
            'approved_at' => now(),
        ]);

        event(new \App\Events\OrderStatusUpdated($order, OrderStatus::Void));

        session()->flash('warning', "Pesanan #{$order->number} telah ditolak.");
    }

    /**
     * Load pending order into active POS cart for modification before settlement.
     */
    public function loadPendingOrderIntoCart(int $orderId): void
    {
        $order = Order::with('items')->findOrFail($orderId);

        $this->cart = [];
        foreach ($order->items as $item) {
            $key = 'item_' . ($item->menu_item_id ?? uniqid()) . '_' . uniqid();
            $this->cart[$key] = [
                'key' => $key,
                'menu_item_id' => $item->menu_item_id,
                'name' => $item->name,
                'type' => $item->type instanceof MenuType ? $item->type->value : (string) $item->type,
                'qty' => $item->qty,
                'unit_price' => $item->unit_price,
                'note' => $item->note ?? '',
                'is_custom' => (bool) $item->is_custom,
            ];
        }

        $this->customerName = $order->customer_name;
        $this->selectedTableId = $order->table_id;
        $this->showPendingOrdersModal = false;

        // Delete the pending order so it gets recreated properly on checkout
        $order->items()->delete();
        $order->delete();

        session()->flash('info', "Item pesanan #{$order->number} telah dimuat ke kasir untuk disesuaikan.");
    }

    public function render()
    {
        $categories = Category::orderBy('sort_order')->get();

        $menuQuery = MenuItem::where('is_active', true)
            ->with('category');

        if ($this->selectedCategoryId) {
            $menuQuery->where('category_id', $this->selectedCategoryId);
        }

        if (trim($this->search)) {
            $menuQuery->where('name', 'like', '%' . trim($this->search) . '%');
        }

        $menuItems = $menuQuery->orderBy('name')->get();

        $tables = Table::where('is_active', true)->orderBy('label')->get();
        $discounts = app(DiscountEngine::class)->getAvailableDiscounts();

        $completedOrder = $this->completedOrderId
            ? Order::with(['items', 'payments', 'cashier', 'table'])->find($this->completedOrderId)
            : null;

        // Today's orders for quick access drawer
        $todayOrdersQuery = Order::with(['table', 'cashier', 'payments'])
            ->whereDate('created_at', today())
            ->latest();

        if ($this->ordersFilter === 'unpaid') {
            $todayOrdersQuery->where('payment_status', 'unpaid');
        } elseif ($this->ordersFilter === 'paid') {
            $todayOrdersQuery->where('payment_status', 'paid');
        }

        $todayOrders = $this->showOrdersDrawer ? $todayOrdersQuery->limit(20)->get() : collect();

        // Pending self-service orders (KF-33)
        $pendingOrders = Order::with(['items', 'table'])
            ->where('status', OrderStatus::PendingConfirmation)
            ->latest()
            ->get();

        return view('livewire.pos.pos-index', [
            'categories' => $categories,
            'menuItems' => $menuItems,
            'tables' => $tables,
            'discounts' => $discounts,
            'summary' => $this->summary,
            'completedOrder' => $completedOrder,
            'todayOrders' => $todayOrders,
            'pendingOrders' => $pendingOrders,
        ]);
    }
}
