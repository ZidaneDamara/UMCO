<?php

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Services\AuditService;
use App\Services\StockService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PayOrder
{
    public function __construct(
        protected AuditService $auditService,
        protected StockService $stockService,
    ) {}

    /**
     * Process payment for an order.
     */
    public function execute(
        Order $order,
        PaymentMethod|string $method,
        int $cashReceived = 0,
        ?string $reference = null,
    ): Payment {
        return DB::transaction(function () use ($order, $method, $cashReceived, $reference) {
            // Lock order for update
            $order = Order::lockForUpdate()->findOrFail($order->id);

            if ($order->isPaid()) {
                throw new InvalidArgumentException('Pesanan ini sudah dibayar.');
            }

            if ($order->isVoid()) {
                throw new InvalidArgumentException('Pesanan ini sudah dibatalkan (void).');
            }

            $paymentMethod = $method instanceof PaymentMethod ? $method : PaymentMethod::from($method);

            if ($paymentMethod === PaymentMethod::Cash) {
                if ($cashReceived < $order->total) {
                    throw new InvalidArgumentException("Uang tunai (Rp{$cashReceived}) kurang dari total pesanan (Rp{$order->total}).");
                }
                $change = $cashReceived - $order->total;
            } else {
                // QRIS or non-cash
                $cashReceived = $order->total;
                $change = 0;
            }

            $payment = Payment::create([
                'order_id' => $order->id,
                'method' => $paymentMethod,
                'amount' => $order->total,
                'cash_received' => $cashReceived,
                'change' => $change,
                'reference' => $reference,
                'user_id' => Auth::id(),
                'paid_at' => now(),
            ]);

            // If order was pending confirmation, confirm it now and deduct stock
            if ($order->status === OrderStatus::PendingConfirmation) {
                $order->status = OrderStatus::Confirmed;
                $this->stockService->deductForOrder($order);
            }

            $order->payment_status = PaymentStatus::Paid;
            $order->paid_at = now();
            $order->save();

            $this->auditService->log(
                action: 'orders.pay',
                subject: $order,
                oldValues: ['payment_status' => PaymentStatus::Unpaid->value],
                newValues: [
                    'payment_status' => PaymentStatus::Paid->value,
                    'method' => $paymentMethod->value,
                    'amount' => $payment->amount,
                    'change' => $payment->change,
                ]
            );

            event(new \App\Events\OrderPaid($order));

            return $payment;
        });
    }
}
