<?php

namespace App\Livewire\Inventory;

use App\Actions\RecordOpname;
use App\Actions\RecordWaste;
use App\Enums\StockMovementType;
use App\Models\Ingredient;
use App\Models\StockMovement;
use App\Services\StockService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class InventoryIndex extends Component
{
    public string $mainTab = 'stock'; // stock | need_buy | opname | movements
    public string $search = '';
    public string $categoryFilter = 'all'; // all | bar | kitchen

    // Quick adjustment modal
    public bool $showAdjustModal = false;
    public ?int $selectedIngredientId = null;
    public float $adjustAmount = 0;
    public string $adjustReason = '';

    // Waste modal (KF-27)
    public bool $showWasteModal = false;
    public ?int $wasteIngredientId = null;
    public float $wasteQty = 0;
    public string $wasteReason = 'Basi / Kedaluwarsa';
    public string $wasteNote = '';

    // Stock Opname interactive counts (KF-25, KF-26)
    public array $countedQuantities = [];
    public string $opnameNote = '';
    public bool $showOpnameConfirmModal = false;

    public function mount(): void
    {
        $this->initOpnameCounts();
    }

    public function initOpnameCounts(): void
    {
        $ingredients = Ingredient::where('is_active', true)->get();
        foreach ($ingredients as $ing) {
            $this->countedQuantities[$ing->id] = (float) $ing->stock;
        }
    }

    public function openAdjustModal(int $ingredientId): void
    {
        $this->selectedIngredientId = $ingredientId;
        $this->adjustAmount = 0;
        $this->adjustReason = '';
        $this->showAdjustModal = true;
    }

    public function saveAdjustment(StockService $stockService): void
    {
        $this->validate([
            'selectedIngredientId' => ['required', 'exists:ingredients,id'],
            'adjustAmount' => ['required', 'numeric', 'not_in:0'],
            'adjustReason' => ['required', 'string', 'min:3'],
        ], [
            'adjustReason.required' => 'Alasan penyesuaian stok wajib diisi.',
            'adjustAmount.not_in' => 'Jumlah penyesuaian tidak boleh 0.',
        ]);

        $ingredient = Ingredient::findOrFail($this->selectedIngredientId);

        $stockService->adjustStock(
            ingredient: $ingredient,
            qtyChange: $this->adjustAmount,
            type: $this->adjustAmount > 0 ? StockMovementType::Purchase : StockMovementType::Manual,
            reference: null,
            note: $this->adjustReason,
        );

        $this->showAdjustModal = false;
        $this->initOpnameCounts();
        session()->flash('success', "Stok {$ingredient->name} berhasil disesuaikan.");
    }

    public function openWasteModal(?int $ingredientId = null): void
    {
        $this->wasteIngredientId = $ingredientId ?: Ingredient::where('is_active', true)->first()?->id;
        $this->wasteQty = 0;
        $this->wasteReason = 'Basi / Kedaluwarsa';
        $this->wasteNote = '';
        $this->resetErrorBag();
        $this->showWasteModal = true;
    }

    public function recordWaste(RecordWaste $recordWaste): void
    {
        $this->validate([
            'wasteIngredientId' => ['required', 'exists:ingredients,id'],
            'wasteQty' => ['required', 'numeric', 'min:0.01'],
            'wasteReason' => ['required', 'string'],
        ], [
            'wasteQty.min' => 'Jumlah bahan terbuang minimal 0.01.',
            'wasteReason.required' => 'Alasan bahan terbuang wajib dipilih.',
        ]);

        $ingredient = Ingredient::findOrFail($this->wasteIngredientId);

        $recordWaste->execute(
            ingredient: $ingredient,
            qty: $this->wasteQty,
            reason: $this->wasteReason,
            user: auth()->user(),
            note: $this->wasteNote,
        );

        $this->showWasteModal = false;
        $this->initOpnameCounts();
        session()->flash('success', "Pencatatan bahan terbuang ({$ingredient->name}) berhasil disimpan.");
    }

    public function saveOpname(RecordOpname $recordOpname): void
    {
        $items = [];
        foreach ($this->countedQuantities as $ingredientId => $counted) {
            $items[] = [
                'ingredient_id' => (int) $ingredientId,
                'counted_qty' => (float) $counted,
            ];
        }

        $opname = $recordOpname->execute(
            user: auth()->user(),
            items: $items,
            note: $this->opnameNote ?: 'Stok Opname Rutin UMCO House',
        );

        $this->showOpnameConfirmModal = false;
        $this->opnameNote = '';
        $this->mainTab = 'stock';
        session()->flash('success', "Stok Opname #{$opname->id} berhasil disimpan dan seluruh selisih fisik telah direkonsiliasi.");
    }

    public function render()
    {
        $query = Ingredient::where('is_active', true);

        if ($this->categoryFilter !== 'all') {
            $query->where('category', $this->categoryFilter);
        }

        if (trim($this->search)) {
            $query->where('name', 'like', '%' . trim($this->search) . '%');
        }

        $allIngredients = $query->orderBy('name')->get();

        // Need buy query: stock <= min_stock
        $needBuyItems = Ingredient::where('is_active', true)
            ->where(function ($q) {
                $q->whereColumn('stock', '<=', 'min_stock')
                  ->orWhere('stock', '<=', 0);
            })
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        // Recent stock movements
        $recentMovements = StockMovement::with(['ingredient', 'user'])
            ->latest()
            ->limit(30)
            ->get();

        $selectedIngredient = $this->selectedIngredientId ? Ingredient::find($this->selectedIngredientId) : null;
        $wasteIngredient = $this->wasteIngredientId ? Ingredient::find($this->wasteIngredientId) : null;

        return view('livewire.inventory.inventory-index', [
            'ingredients' => $allIngredients,
            'needBuyItems' => $needBuyItems,
            'recentMovements' => $recentMovements,
            'selectedIngredient' => $selectedIngredient,
            'wasteIngredient' => $wasteIngredient,
        ])->title('Stok Bahan Baku & Inventori');
    }
}
