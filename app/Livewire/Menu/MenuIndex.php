<?php

namespace App\Livewire\Menu;

use App\Models\Category;
use App\Models\MenuItem;
use App\Services\AuditService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class MenuIndex extends Component
{
    public string $search = '';
    public ?int $selectedCategoryId = null;

    public function toggleAvailability(int $menuItemId): void
    {
        $item = MenuItem::findOrFail($menuItemId);
        $old = $item->is_available;
        $item->is_available = ! $old;
        $item->save();

        app(AuditService::class)->log(
            action: 'menu.toggle_availability',
            subject: $item,
            oldValues: ['is_available' => $old],
            newValues: ['is_available' => $item->is_available]
        );
    }

    public function render()
    {
        $categories = Category::orderBy('sort_order')->get();

        $query = MenuItem::with(['category', 'recipes.ingredient']);

        if ($this->selectedCategoryId) {
            $query->where('category_id', $this->selectedCategoryId);
        }

        if (trim($this->search)) {
            $query->where('name', 'like', '%' . trim($this->search) . '%');
        }

        $items = $query->orderBy('category_id')->orderBy('name')->get();

        return view('livewire.menu.menu-index', [
            'categories' => $categories,
            'items' => $items,
        ])->title('Daftar Menu & Ketersediaan');
    }
}
