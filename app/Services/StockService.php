<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Models\Ingredient;
use App\Models\Order;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockService
{
    /**
     * Deduct stock based on recipes when order is confirmed.
     * Stock may go negative (doesn't block sale) per DESIGN.md Section 8.5.
     */
    public function deductForOrder(Order $order): void
    {
        $order->load('items.menuItem.recipes.ingredient');

        foreach ($order->items as $item) {
            // Skip custom items — they have no recipe
            if ($item->is_custom || ! $item->menuItem) {
                continue;
            }

            foreach ($item->menuItem->recipes as $recipe) {
                $totalQty = $recipe->qty_per_item * $item->qty;
                $this->adjustStock(
                    ingredient: $recipe->ingredient,
                    qtyChange: -$totalQty,
                    type: StockMovementType::Sale,
                    reference: $order,
                    note: "Pesanan #{$order->number}: {$item->name} x{$item->qty}",
                );
            }
        }
    }

    /**
     * Return stock when an order is voided.
     */
    public function returnForOrder(Order $order): void
    {
        $order->load('items.menuItem.recipes.ingredient');

        foreach ($order->items as $item) {
            if ($item->is_custom || ! $item->menuItem) {
                continue;
            }

            foreach ($item->menuItem->recipes as $recipe) {
                $totalQty = $recipe->qty_per_item * $item->qty;
                $this->adjustStock(
                    ingredient: $recipe->ingredient,
                    qtyChange: $totalQty,
                    type: StockMovementType::VoidReturn,
                    reference: $order,
                    note: "Void pesanan #{$order->number}: {$item->name} x{$item->qty}",
                );
            }
        }
    }

    /**
     * Adjust stock for a single ingredient and record the movement.
     */
    public function adjustStock(
        Ingredient $ingredient,
        float $qtyChange,
        StockMovementType $type,
        mixed $reference = null,
        ?string $note = null,
    ): StockMovement {
        return DB::transaction(function () use ($ingredient, $qtyChange, $type, $reference, $note) {
            // Lock the ingredient row for update
            $ingredient = Ingredient::lockForUpdate()->find($ingredient->id);

            $newStock = $ingredient->stock + $qtyChange;
            $ingredient->update(['stock' => $newStock]);

            return StockMovement::create([
                'ingredient_id' => $ingredient->id,
                'type' => $type,
                'qty_change' => $qtyChange,
                'qty_after' => $newStock,
                'reference_type' => $reference ? get_class($reference) : null,
                'reference_id' => $reference?->id,
                'note' => $note,
                'user_id' => Auth::id(),
            ]);
        });
    }
}
