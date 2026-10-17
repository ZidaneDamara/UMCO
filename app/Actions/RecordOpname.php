<?php

namespace App\Actions;

use App\Enums\StockMovementType;
use App\Models\Ingredient;
use App\Models\StockMovement;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RecordOpname
{
    public function __construct(
        protected AuditService $auditService,
    ) {}

    /**
     * Process stock opname counts in batch (KF-25, KF-26).
     *
     * @param array<int, array{ingredient_id: int, counted_qty: float}> $items
     */
    public function execute(
        User $user,
        array $items,
        ?string $note = null,
    ): StockOpname {
        if (empty($items)) {
            throw new InvalidArgumentException('Daftar bahan untuk stok opname tidak boleh kosong.');
        }

        return DB::transaction(function () use ($user, $items, $note) {
            $opname = StockOpname::create([
                'user_id' => $user->id,
                'started_at' => now(),
                'finished_at' => now(),
                'note' => $note,
            ]);

            $totalDiffCount = 0;

            foreach ($items as $item) {
                $ingredient = Ingredient::lockForUpdate()->findOrFail($item['ingredient_id']);
                $systemQty = (float) $ingredient->stock;
                $countedQty = (float) $item['counted_qty'];
                $diff = $countedQty - $systemQty;

                StockOpnameItem::create([
                    'opname_id' => $opname->id,
                    'ingredient_id' => $ingredient->id,
                    'system_qty' => $systemQty,
                    'counted_qty' => $countedQty,
                    'diff' => $diff,
                ]);

                if (abs($diff) > 0.0001) {
                    $totalDiffCount++;
                    $ingredient->update(['stock' => $countedQty]);

                    StockMovement::create([
                        'ingredient_id' => $ingredient->id,
                        'type' => StockMovementType::Opname,
                        'qty_change' => $diff,
                        'qty_after' => $countedQty,
                        'reference_type' => StockOpname::class,
                        'reference_id' => $opname->id,
                        'note' => "Stok Opname #{$opname->id} (Sistem: {$systemQty}, Fisik: {$countedQty}, Selisih: {$diff})",
                        'user_id' => $user->id,
                    ]);
                }
            }

            $this->auditService->log(
                action: 'inventory.opname',
                subject: $opname,
                oldValues: null,
                newValues: [
                    'opname_id' => $opname->id,
                    'user_id' => $user->id,
                    'total_items' => count($items),
                    'items_with_diff' => $totalDiffCount,
                    'note' => $note,
                ]
            );

            return $opname;
        });
    }
}
