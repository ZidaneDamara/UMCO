<?php

namespace App\Actions;

use App\Enums\MenuType;
use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\StationType;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Shift;
use App\Services\AuditService;
use App\Services\DiscountEngine;
use App\Services\OrderNumberGenerator;
use App\Services\StockService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CreateOrder
{
    public function __construct(
        protected OrderNumberGenerator $numberGenerator,
        protected DiscountEngine $discountEngine,
        protected StockService $stockService,
        protected AuditService $auditService,
    ) {}

    /**
     * Create a new order with items, calculated discounts, and stock deduction.
     *
     * @param  array{
     *     customer_name?: ?string,
     *     table_id?: ?int,
     *     source?: OrderSource|string,
     *     is_open_bill?: bool,
     *     open_bill_note?: ?string,
     *     discount_id?: ?int,
     *     items: array<array{
     *         menu_item_id?: ?int,
     *         name: string,
     *         type: MenuType|string,
     *         qty: int,
     *         unit_price: int,
     *         is_custom?: bool,
     *         note?: ?string,
     *         station?: StationType|string|null,
     *     }>,
     * }  $data
     */
    public function execute(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            $user = Auth::user();
            $shift = $user?->activeShift();

            // Auto create shift if not exists for cashier (per DECISIONS.md D-006)
            if (! $shift && $user) {
                $shift = Shift::create([
                    'user_id' => $user->id,
                    'opened_at' => now(),
                    'opening_cash' => 0,
                ]);
            }

            $source = isset($data['source'])
                ? ($data['source'] instanceof OrderSource ? $data['source'] : OrderSource::from($data['source']))
                : OrderSource::Pos;

            $isOpenBill = (bool) ($data['is_open_bill'] ?? false);
            $discountId = isset($data['discount_id']) ? (int) $data['discount_id'] : null;

            // Prepare items collection for discount calculation
            $rawItems = collect($data['items'] ?? []);
            $calculation = $this->discountEngine->calculate($rawItems, $discountId);

            $processedItems = $calculation['items'];
            $discountTotal = $calculation['discount_total'];

            $subtotal = $rawItems->sum(fn ($i) => ((int) $i['unit_price']) * ((int) $i['qty']));
            $total = max(0, $subtotal - $discountTotal);

            $status = ($source === OrderSource::SelfService || $source === OrderSource::Delivery)
                ? OrderStatus::PendingConfirmation
                : OrderStatus::Confirmed;

            $orderNumber = $this->numberGenerator->generate();

            $order = Order::create([
                'number' => $orderNumber,
                'source' => $source,
                'table_id' => $data['table_id'] ?? null,
                'customer_name' => $data['customer_name'] ?? null,
                'status' => $status,
                'payment_status' => PaymentStatus::Unpaid,
                'is_open_bill' => $isOpenBill,
                'open_bill_note' => $data['open_bill_note'] ?? null,
                'subtotal' => $subtotal,
                'discount_total' => $discountTotal,
                'total' => $total,
                'shift_id' => $shift?->id,
                'cashier_id' => $user?->id,
            ]);

            foreach ($processedItems as $item) {
                $type = $item['type'] instanceof MenuType ? $item['type'] : MenuType::from($item['type']);
                $station = isset($item['station'])
                    ? ($item['station'] instanceof StationType ? $item['station'] : StationType::from($item['station']))
                    : ($type === MenuType::Drink ? StationType::Bar : StationType::Kitchen);

                OrderItem::create([
                    'order_id' => $order->id,
                    'menu_item_id' => $item['menu_item_id'] ?? null,
                    'name' => $item['name'],
                    'type' => $type,
                    'qty' => (int) $item['qty'],
                    'unit_price' => (int) $item['unit_price'],
                    'discount_amount' => (int) ($item['discount_amount'] ?? 0),
                    'discount_id' => $item['discount_id'] ?? null,
                    'note' => $item['note'] ?? null,
                    'is_custom' => (bool) ($item['is_custom'] ?? false),
                    'station' => $station,
                    'status' => 'pending',
                ]);
            }

            // Deduct stock and dispatch events if order is confirmed (per DESIGN.md Section 8.5 & 5.2)
            if ($order->status === OrderStatus::Confirmed) {
                $this->stockService->deductForOrder($order);

                $order->load(['items', 'table']);
                if ($order->itemsForStation('bar')->isNotEmpty()) {
                    event(new \App\Events\OrderConfirmed($order, 'bar'));
                }
                if ($order->itemsForStation('kitchen')->isNotEmpty()) {
                    event(new \App\Events\OrderConfirmed($order, 'kitchen'));
                }
            } elseif ($order->status === OrderStatus::PendingConfirmation) {
                $order->load(['items', 'table']);
                event(new \App\Events\OrderCreated($order));
            }

            $this->auditService->log(
                action: 'orders.create',
                subject: $order,
                oldValues: null,
                newValues: [
                    'number' => $order->number,
                    'total' => $order->total,
                    'items_count' => count($processedItems),
                    'is_open_bill' => $isOpenBill,
                ]
            );

            return $order;
        });
    }
}
