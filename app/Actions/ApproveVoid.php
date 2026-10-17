<?php

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderVoid;
use App\Models\User;
use App\Services\AuditService;
use App\Services\StockService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ApproveVoid
{
    public function __construct(
        protected StockService $stockService,
        protected AuditService $auditService,
    ) {}

    public function execute(
        Order $order,
        User $requester,
        User $approver,
        string $reason,
        ?string $reasonDetail = null,
    ): OrderVoid {
        return DB::transaction(function () use ($order, $requester, $approver, $reason, $reasonDetail) {
            $order = Order::lockForUpdate()->findOrFail($order->id);

            if ($order->isVoid()) {
                throw new InvalidArgumentException('Pesanan ini sudah dibatalkan sebelumnya.');
            }

            // Check approver authorization
            if (! $approver->hasPermissionTo('orders.void.approve')) {
                throw new InvalidArgumentException('Pengguna tidak memiliki izin untuk menyetujui pembatalan nota.');
            }

            // Record previous status for audit
            $previousStatus = $order->status->value;
            $previousPaymentStatus = $order->payment_status->value;

            // Update order status
            $order->status = OrderStatus::Void;
            $order->payment_status = PaymentStatus::Void;
            $order->save();

            // Create OrderVoid entry
            $orderVoid = OrderVoid::create([
                'order_id' => $order->id,
                'reason' => $reason,
                'reason_detail' => $reasonDetail,
                'requested_by' => $requester->id,
                'approved_by' => $approver->id,
                'approved_at' => now(),
            ]);

            // Return stock (void_return) per DESIGN.md Section 8.4 & 8.5
            $this->stockService->returnForOrder($order);

            $this->auditService->log(
                action: 'orders.void',
                subject: $order,
                oldValues: [
                    'status' => $previousStatus,
                    'payment_status' => $previousPaymentStatus,
                ],
                newValues: [
                    'status' => OrderStatus::Void->value,
                    'payment_status' => PaymentStatus::Void->value,
                    'void_reason' => $reason,
                    'void_detail' => $reasonDetail,
                    'approved_by' => $approver->id,
                ]
            );

            return $orderVoid;
        });
    }
}
