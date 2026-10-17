<?php

namespace App\Livewire\Customer;

use App\Actions\CreateOrder;
use App\Enums\MenuType;
use App\Enums\OrderSource;
use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Table;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.customer')]
class CustomerMenu extends Component
{
    public string $token = '';
    public ?Table $table = null;
    public bool $isTableInvalid = false;
    public bool $isDelivery = false;

    // Filters
    public string $search = '';
    public ?int $selectedCategoryId = null;

    // Cart: array of items
    // item: ['id' => uniqid, 'menu_item_id' => int, 'name' => string, 'type' => string, 'qty' => int, 'unit_price' => int, 'note' => string]
    public array $cart = [];

    // Item customization modal
    public bool $showItemModal = false;
    public ?MenuItem $activeItem = null;
    public int $itemQty = 1;
    public string $itemNote = '';
    public array $selectedChips = [];

    // Checkout / Order Form
    public bool $showCartDrawer = false;
    public string $customerName = '';
    public string $deliveryLocation = '';
    public string $honeypot = ''; // Spam protection

    public function mount(string $token): void
    {
        $this->token = trim($token);
        $this->table = Table::where('token', $this->token)
            ->where('is_active', true)
            ->first();

        if (! $this->table) {
            $this->isTableInvalid = true;
            return;
        }

        $this->isDelivery = ($this->table->token === 'antar' || str_contains(strtolower($this->table->label), 'antar'));
    }

    /**
     * Open item modal for customization.
     */
    public function selectItem(int $menuItemId): void
    {
        $item = MenuItem::where('id', $menuItemId)
            ->where('is_active', true)
            ->where('is_available', true)
            ->first();

        if (! $item) {
            return;
        }

        $this->activeItem = $item;
        $this->itemQty = 1;
        $this->itemNote = '';
        $this->selectedChips = [];
        $this->showItemModal = true;
    }

    /**
     * Toggle note chip (e.g. Less ice, Less sugar).
     */
    public function toggleChip(string $chip): void
    {
        if (in_array($chip, $this->selectedChips)) {
            $this->selectedChips = array_values(array_filter($this->selectedChips, fn ($c) => $c !== $chip));
        } else {
            $this->selectedChips[] = $chip;
        }
    }

    /**
     * Add customized item to cart.
     */
    public function addActiveItemToCart(): void
    {
        if (! $this->activeItem) {
            return;
        }

        $allNotes = $this->selectedChips;
        if (trim($this->itemNote)) {
            $allNotes[] = trim($this->itemNote);
        }
        $finalNote = implode(', ', $allNotes);

        $cartKey = 'cart_' . $this->activeItem->id . '_' . md5($finalNote);

        if (isset($this->cart[$cartKey])) {
            $this->cart[$cartKey]['qty'] += $this->itemQty;
        } else {
            // Check max item limits (anti-spam)
            if (count($this->cart) >= 20) {
                session()->flash('error', 'Batas maksimal 20 macam item per pesanan.');
                $this->showItemModal = false;
                return;
            }

            $typeVal = $this->activeItem->type instanceof MenuType
                ? $this->activeItem->type->value
                : (string) $this->activeItem->type;

            $this->cart[$cartKey] = [
                'cart_key' => $cartKey,
                'menu_item_id' => $this->activeItem->id,
                'name' => $this->activeItem->name,
                'type' => $typeVal,
                'qty' => $this->itemQty,
                'unit_price' => (int) $this->activeItem->price,
                'note' => $finalNote,
                'is_custom' => false,
            ];
        }

        $this->showItemModal = false;
        $this->activeItem = null;
    }

    /**
     * Increment cart item quantity.
     */
    public function incrementCart(string $cartKey): void
    {
        if (isset($this->cart[$cartKey])) {
            $totalQty = array_sum(array_column($this->cart, 'qty'));
            if ($totalQty >= 50) {
                session()->flash('error', 'Batas maksimal jumlah pesanan adalah 50 porsi.');
                return;
            }
            $this->cart[$cartKey]['qty'] += 1;
        }
    }

    /**
     * Decrement cart item quantity or remove if 0.
     */
    public function decrementCart(string $cartKey): void
    {
        if (isset($this->cart[$cartKey])) {
            if ($this->cart[$cartKey]['qty'] > 1) {
                $this->cart[$cartKey]['qty'] -= 1;
            } else {
                unset($this->cart[$cartKey]);
            }
        }
    }

    /**
     * Remove item completely.
     */
    public function removeFromCart(string $cartKey): void
    {
        unset($this->cart[$cartKey]);
    }

    /**
     * Clear all items in cart.
     */
    public function clearCart(): void
    {
        $this->cart = [];
        $this->showCartDrawer = false;
    }

    /**
     * Submit order to cashier.
     */
    public function submitOrder(CreateOrder $createOrder)
    {
        // 1. Honeypot check (anti-bot)
        if (! empty($this->honeypot)) {
            // Silently fake success or abort
            return redirect()->route('customer.menu', ['token' => $this->token]);
        }

        // 2. Validate cart
        if (empty($this->cart)) {
            session()->flash('error', 'Keranjang pesanan masih kosong.');
            return;
        }

        $totalQty = array_sum(array_column($this->cart, 'qty'));
        if ($totalQty > 50) {
            session()->flash('error', 'Jumlah pesanan melebihi batas 50 item.');
            return;
        }

        // 3. Validate delivery location if delivery mode
        if ($this->isDelivery) {
            $this->validate([
                'deliveryLocation' => ['required', 'string', 'min:3', 'max:200'],
                'customerName' => ['required', 'string', 'min:2', 'max:100'],
            ], [
                'deliveryLocation.required' => 'Lokasi/ruangan pengantaran wajib diisi.',
                'customerName.required' => 'Nama pemesan wajib diisi untuk pesanan antar.',
            ]);
        }

        $items = array_values($this->cart);

        $source = $this->isDelivery ? OrderSource::Delivery : OrderSource::SelfService;
        $customerName = trim($this->customerName) ?: ($this->table ? $this->table->label : 'Pelanggan QR');
        $deliveryNote = $this->isDelivery ? trim($this->deliveryLocation) : null;

        $order = $createOrder->execute([
            'source' => $source,
            'table_id' => $this->table->id,
            'customer_name' => $customerName,
            'open_bill_note' => $deliveryNote,
            'items' => $items,
        ]);

        $this->clearCart();

        return redirect()->route('customer.order.status', ['orderNumber' => $order->number]);
    }

    public function getSubtotalProperty(): int
    {
        return array_reduce($this->cart, function ($carry, $item) {
            return $carry + ($item['unit_price'] * $item['qty']);
        }, 0);
    }

    public function getTotalQtyProperty(): int
    {
        return array_sum(array_column($this->cart, 'qty'));
    }

    public function render()
    {
        if ($this->isTableInvalid) {
            return view('livewire.customer.table-invalid');
        }

        $categories = Category::orderBy('sort_order')->get();

        $menuQuery = MenuItem::where('is_active', true)
            ->with(['category', 'partner']);

        if ($this->selectedCategoryId) {
            $menuQuery->where('category_id', $this->selectedCategoryId);
        }

        if (trim($this->search)) {
            $menuQuery->where('name', 'like', '%' . trim($this->search) . '%');
        }

        $menuItems = $menuQuery->orderBy('is_available', 'desc')
            ->orderBy('name')
            ->get();

        return view('livewire.customer.customer-menu', [
            'categories' => $categories,
            'menuItems' => $menuItems,
        ]);
    }
}
