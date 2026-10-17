<?php

namespace App\Services;

use App\Enums\DiscountKind;
use App\Enums\MenuType;
use App\Models\Discount;
use Illuminate\Support\Collection;

class DiscountEngine
{
    /**
     * Calculate discount for cart items.
     *
     * @param  Collection  $cartItems  Collection of arrays with keys: id, name, type, qty, unit_price, menu_item_id
     * @param  int|null  $discountId  Selected identity-based discount ID
     * @return array{items: array, discount_total: int}
     */
    public function calculate(Collection $cartItems, ?int $discountId = null): array
    {
        $discount = $discountId ? Discount::find($discountId) : null;
        $discountTotal = 0;
        $processedItems = [];

        foreach ($cartItems as $item) {
            $itemDiscount = 0;
            $appliedDiscountId = null;

            if ($discount && $discount->isCurrentlyValid()) {
                $itemDiscount = $this->calculateItemDiscount($discount, $item);
                if ($itemDiscount > 0) {
                    $appliedDiscountId = $discount->id;
                }
            }

            // Ensure discount doesn't make price negative
            $maxDiscount = $item['unit_price'];
            $itemDiscount = min($itemDiscount, $maxDiscount);

            $processedItems[] = array_merge($item, [
                'discount_amount' => $itemDiscount,
                'discount_id' => $appliedDiscountId,
            ]);

            $discountTotal += $itemDiscount * $item['qty'];
        }

        // Check for "minimal 3 produk" discount (fixed_per_order)
        $totalQty = $cartItems->sum('qty');
        $orderDiscount = $this->calculateOrderLevelDiscount($totalQty, $discountId);

        if ($orderDiscount > 0) {
            $discountTotal += $orderDiscount;
        }

        return [
            'items' => $processedItems,
            'discount_total' => $discountTotal,
            'order_discount' => $orderDiscount,
        ];
    }

    /**
     * Calculate per-item discount based on discount type.
     */
    private function calculateItemDiscount(Discount $discount, array $item): int
    {
        // Check if discount applies to this item type
        if ($discount->applies_to !== 'all') {
            $itemType = $item['type'] instanceof MenuType ? $item['type']->value : $item['type'];
            if ($discount->applies_to !== $itemType) {
                return 0;
            }
        }

        // Check item menu ID restrictions if defined in conditions
        if (! empty($discount->conditions['menu_item_ids'])) {
            $menuId = $item['menu_item_id'] ?? null;
            if (! in_array($menuId, $discount->conditions['menu_item_ids'])) {
                return 0;
            }
        }

        return match ($discount->kind) {
            DiscountKind::FixedPerItem, DiscountKind::Bundle => $discount->value,
            DiscountKind::PercentPerItem => (int) floor($item['unit_price'] * $discount->value / 100),
            DiscountKind::Promo => (($discount->conditions['type'] ?? 'fixed') === 'percent')
                ? (int) floor($item['unit_price'] * $discount->value / 100)
                : $discount->value,
            default => 0,
        };
    }

    /**
     * Calculate order-level discount (e.g., min 3 products).
     * This is separate from identity-based discounts.
     */
    private function calculateOrderLevelDiscount(int $totalQty, ?int $excludeDiscountId = null): int
    {
        $orderDiscounts = Discount::where('kind', DiscountKind::FixedPerOrder)
            ->where('is_active', true)
            ->get();

        $total = 0;

        foreach ($orderDiscounts as $discount) {
            if (! $discount->isCurrentlyValid()) {
                continue;
            }

            // Check conditions (e.g., min_qty)
            $conditions = $discount->conditions ?? [];
            $minQty = $conditions['min_qty'] ?? 0;

            if ($totalQty >= $minQty) {
                $total += $discount->value;
            }
        }

        return $total;
    }

    /**
     * Get available identity-based discounts for display in POS.
     */
    public function getAvailableDiscounts(): Collection
    {
        return Discount::where('is_active', true)
            ->whereIn('kind', [
                DiscountKind::FixedPerItem,
                DiscountKind::PercentPerItem,
                DiscountKind::Promo,
                DiscountKind::Bundle,
            ])
            ->get();
    }
}
