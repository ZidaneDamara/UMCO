<?php

namespace App\Actions;

use App\Enums\StockMovementType;
use App\Models\Ingredient;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\AuditService;
use App\Services\StockService;
use InvalidArgumentException;

class RecordWaste
{
    public function __construct(
        protected StockService $stockService,
        protected AuditService $auditService,
    ) {}

    /**
     * Record wasted or expired ingredient (KF-27).
     * Deducts stock and records a waste stock movement.
     */
    public function execute(
        Ingredient $ingredient,
        float $qty,
        string $reason,
        User $user,
        ?string $note = null,
    ): StockMovement {
        if ($qty <= 0) {
            throw new InvalidArgumentException('Jumlah bahan terbuang harus lebih besar dari 0.');
        }

        if (empty(trim($reason))) {
            throw new InvalidArgumentException('Alasan bahan terbuang/basi wajib diisi.');
        }

        $fullNote = "Terbuang ({$reason})";
        if ($note) {
            $fullNote .= ": {$note}";
        }

        $movement = $this->stockService->adjustStock(
            ingredient: $ingredient,
            qtyChange: -$qty,
            type: StockMovementType::Waste,
            reference: null,
            note: $fullNote,
        );

        $this->auditService->log(
            action: 'inventory.waste',
            subject: $ingredient,
            oldValues: ['stock' => $ingredient->stock + $qty],
            newValues: [
                'stock' => $ingredient->stock,
                'qty_wasted' => $qty,
                'reason' => $reason,
                'note' => $note,
                'user_id' => $user->id,
            ]
        );

        return $movement;
    }
}
