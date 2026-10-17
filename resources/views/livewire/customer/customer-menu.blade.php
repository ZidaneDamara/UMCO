<div class="flex-1 flex flex-col pb-24 relative select-none">
    <!-- Header Banner -->
    <header class="bg-gradient-to-r from-[var(--color-brand-700)] to-[var(--color-brand-600)] text-white px-5 pt-6 pb-5 rounded-b-3xl shadow-md sticky top-0 z-20">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-white/15 backdrop-blur-sm flex items-center justify-center font-bold text-lg text-white border border-white/20 shadow-sm">
                    UH
                </div>
                <div>
                    <h1 class="text-base font-bold leading-tight tracking-tight">UMCO House</h1>
                    <p class="text-xs text-white/80 font-medium">BUMK Universitas Muhammadiyah Riau</p>
                </div>
            </div>

            <!-- Table / Delivery Badge -->
            <div class="px-3 py-1.5 rounded-full {{ $isDelivery ? 'bg-amber-400 text-amber-950 font-bold' : 'bg-white/20 backdrop-blur-sm text-white font-semibold' }} text-xs flex items-center gap-1.5 border border-white/20 shadow-sm">
                <i data-lucide="{{ $isDelivery ? 'truck' : 'map-pin' }}" class="w-3.5 h-3.5"></i>
                <span>{{ $table->label }}</span>
            </div>
        </div>

        <!-- Search input -->
        <div class="relative">
            <i data-lucide="search" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-white/60"></i>
            <input type="text" 
                   wire:model.live.debounce.250ms="search" 
                   placeholder="Cari kopi, mocktail, makanan..." 
                   class="w-full bg-white/15 backdrop-blur-md text-white placeholder-white/60 text-xs rounded-xl pl-10 pr-9 py-2.5 border border-white/20 focus:outline-none focus:ring-2 focus:ring-white/40 transition">
            @if($search)
            <button wire:click="$set('search', '')" class="absolute right-3 top-1/2 -translate-y-1/2 text-white/70 hover:text-white">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
            @endif
        </div>
    </header>

    <!-- Category Pills -->
    <div class="px-4 py-3 bg-[var(--color-surface)] border-b border-[var(--color-line)] sticky top-[138px] z-10 overflow-x-auto no-scrollbar flex items-center gap-2">
        <button wire:click="$set('selectedCategoryId', null)" 
                class="px-3.5 py-1.5 rounded-full text-xs font-semibold whitespace-nowrap transition-all shadow-xs {{ is_null($selectedCategoryId) ? 'bg-[var(--color-brand-600)] text-white' : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200' }}">
            Semua Menu
        </button>
        @foreach($categories as $cat)
        <button wire:click="$set('selectedCategoryId', {{ $cat->id }})" 
                class="px-3.5 py-1.5 rounded-full text-xs font-semibold whitespace-nowrap transition-all shadow-xs {{ $selectedCategoryId === $cat->id ? 'bg-[var(--color-brand-600)] text-white' : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200' }}">
            {{ $cat->name }}
        </button>
        @endforeach
    </div>

    <!-- Alert / Flash message -->
    @if(session()->has('error'))
    <div class="mx-4 mt-3 p-3 bg-red-50 border border-red-200 rounded-xl text-red-700 text-xs flex items-center gap-2">
        <i data-lucide="alert-circle" class="w-4 h-4 shrink-0"></i>
        <span>{{ session('error') }}</span>
    </div>
    @endif

    <!-- Menu List -->
    <div class="p-4 space-y-3 flex-1">
        @forelse($menuItems as $item)
        @php
            $inCartQty = 0;
            foreach ($cart as $c) {
                if ($c['menu_item_id'] === $item->id) {
                    $inCartQty += $c['qty'];
                }
            }
        @endphp
        <div class="p-3.5 rounded-2xl border transition-all duration-150 flex items-center justify-between gap-3 bg-white {{ $item->is_available ? 'border-neutral-200 hover:border-neutral-300 shadow-xs' : 'border-neutral-100 bg-neutral-50/60 opacity-60' }}">
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-1.5 mb-1">
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide {{ $item->type->value === 'drink' ? 'bg-blue-50 text-blue-700' : 'bg-orange-50 text-orange-700' }}">
                        {{ $item->type->value === 'drink' ? 'Minuman' : 'Makanan' }}
                    </span>
                    @if($item->partner)
                    <span class="px-2 py-0.5 rounded text-[10px] font-medium bg-neutral-100 text-neutral-600">
                        {{ $item->partner->name }}
                    </span>
                    @endif
                </div>

                <h3 class="text-sm font-bold text-neutral-900 leading-snug truncate">
                    {{ $item->name }}
                </h3>
                <div class="text-xs font-extrabold text-[var(--color-brand-700)] tabular-nums mt-0.5">
                    Rp{{ number_format($item->price, 0, ',', '.') }}
                </div>
            </div>

            <!-- Action Button -->
            <div class="shrink-0 flex items-center gap-2">
                @if(! $item->is_available)
                <span class="px-3 py-1.5 rounded-xl bg-neutral-200 text-neutral-500 text-xs font-semibold">
                    Habis
                </span>
                @else
                <button wire:click="selectItem({{ $item->id }})" 
                        class="relative px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all shadow-xs flex items-center gap-1.5 {{ $inCartQty > 0 ? 'bg-[var(--color-brand-50)] text-[var(--color-brand-700)] border border-[var(--color-brand-600)]' : 'bg-[var(--color-brand-600)] text-white hover:bg-[var(--color-brand-700)]' }}">
                    @if($inCartQty > 0)
                    <span class="w-5 h-5 rounded-full bg-[var(--color-brand-600)] text-white text-[11px] font-black flex items-center justify-center">
                        {{ $inCartQty }}
                    </span>
                    <span>Tambah</span>
                    @else
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                    <span>Pesan</span>
                    @endif
                </button>
                @endif
            </div>
        </div>
        @empty
        <div class="py-16 text-center text-neutral-400">
            <i data-lucide="utensils" class="w-10 h-10 mx-auto mb-2 opacity-50"></i>
            <p class="text-sm font-semibold">Tidak ada menu yang cocok</p>
            <p class="text-xs text-neutral-400 mt-1">Coba kata kunci pencarian lain</p>
        </div>
        @endforelse
    </div>

    <!-- Floating Bottom Cart Bar -->
    @if(count($cart) > 0)
    <div class="fixed bottom-0 left-1/2 -translate-x-1/2 w-full max-w-lg p-4 bg-gradient-to-t from-[var(--color-surface)] via-[var(--color-surface)] to-transparent z-30">
        <div class="bg-[var(--color-brand-700)] text-white p-3.5 rounded-2xl shadow-xl flex items-center justify-between border border-white/20">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center font-black text-sm">
                    {{ $this->totalQty }}
                </div>
                <div>
                    <div class="text-[11px] text-white/80 font-medium leading-none mb-1">Total Pesanan</div>
                    <div class="text-base font-extrabold tabular-nums leading-none">
                        Rp{{ number_format($this->subtotal, 0, ',', '.') }}
                    </div>
                </div>
            </div>

            <button wire:click="$set('showCartDrawer', true)" 
                    class="px-4 py-2.5 rounded-xl bg-white text-[var(--color-brand-700)] text-xs font-bold shadow hover:bg-neutral-100 transition flex items-center gap-1.5">
                <span>Lihat Keranjang</span>
                <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
            </button>
        </div>
    </div>
    @endif

    <!-- Item Customization Modal -->
    @if($showItemModal && $activeItem)
    <div class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-end sm:items-center justify-center p-0 sm:p-4 animate-in fade-in duration-150">
        <div class="bg-white w-full max-w-lg rounded-t-3xl sm:rounded-3xl p-5 shadow-2xl flex flex-col max-h-[85vh] overflow-y-auto">
            <div class="flex items-center justify-between pb-3 border-b border-neutral-100">
                <div>
                    <h3 class="text-base font-bold text-neutral-900">{{ $activeItem->name }}</h3>
                    <div class="text-xs font-extrabold text-[var(--color-brand-600)] tabular-nums mt-0.5">
                        Rp{{ number_format($activeItem->price, 0, ',', '.') }}
                    </div>
                </div>
                <button wire:click="$set('showItemModal', false)" class="p-1 rounded-full text-neutral-400 hover:text-neutral-700 hover:bg-neutral-100">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Quantity Selector -->
            <div class="py-4 border-b border-neutral-100 flex items-center justify-between">
                <span class="text-xs font-semibold text-neutral-600">Jumlah Porsi</span>
                <div class="flex items-center gap-3">
                    <button type="button" 
                            wire:click="$set('itemQty', {{ max(1, $itemQty - 1) }})" 
                            class="w-8 h-8 rounded-full border border-neutral-300 flex items-center justify-center text-neutral-600 hover:bg-neutral-100 text-sm font-bold">
                        -
                    </button>
                    <span class="w-8 text-center text-sm font-extrabold tabular-nums">{{ $itemQty }}</span>
                    <button type="button" 
                            wire:click="$set('itemQty', {{ min(20, $itemQty + 1) }})" 
                            class="w-8 h-8 rounded-full bg-[var(--color-brand-600)] text-white flex items-center justify-center text-sm font-bold shadow-xs">
                        +
                    </button>
                </div>
            </div>

            <!-- Notes / Preferences Chips -->
            <div class="py-4 border-b border-neutral-100 space-y-2">
                <label class="text-xs font-semibold text-neutral-700 block">Pilihan / Catatan Varian</label>
                <div class="flex flex-wrap gap-1.5">
                    @if($activeItem->type->value === 'drink')
                        @foreach(['Less Ice', 'No Ice', 'Normal Ice', 'Less Sugar', 'Normal Sugar', 'Hot / Hangat', 'Takeaway Cup'] as $chip)
                        <button type="button" 
                                wire:click="toggleChip('{{ $chip }}')" 
                                class="px-3 py-1 rounded-full text-xs font-semibold transition border {{ in_array($chip, $selectedChips) ? 'bg-[var(--color-brand-600)] text-white border-[var(--color-brand-600)]' : 'bg-neutral-50 text-neutral-600 border-neutral-200 hover:bg-neutral-100' }}">
                            {{ $chip }}
                        </button>
                        @endforeach
                    @else
                        @foreach(['Pedas', 'Sedang', 'Tidak Pedas', 'Pisah Sambal', 'Bungkus / Takeaway'] as $chip)
                        <button type="button" 
                                wire:click="toggleChip('{{ $chip }}')" 
                                class="px-3 py-1 rounded-full text-xs font-semibold transition border {{ in_array($chip, $selectedChips) ? 'bg-[var(--color-brand-600)] text-white border-[var(--color-brand-600)]' : 'bg-neutral-50 text-neutral-600 border-neutral-200 hover:bg-neutral-100' }}">
                            {{ $chip }}
                        </button>
                        @endforeach
                    @endif
                </div>

                <!-- Custom note text -->
                <div class="pt-2">
                    <input type="text" 
                           wire:model="itemNote" 
                           placeholder="Catatan tambahan (misal: gula aren terpisah)..." 
                           class="w-full text-xs px-3 py-2 rounded-xl border border-neutral-200 focus:outline-none focus:ring-2 focus:ring-[var(--color-brand-600)]">
                </div>
            </div>

            <!-- Add to Cart CTA -->
            <div class="pt-4">
                <button type="button" 
                        wire:click="addActiveItemToCart" 
                        class="w-full py-3 rounded-2xl bg-[var(--color-brand-600)] text-white text-xs font-bold shadow-md hover:bg-[var(--color-brand-700)] transition flex items-center justify-center gap-2">
                    <i data-lucide="shopping-bag" class="w-4 h-4"></i>
                    <span>Tambahkan ke Pesanan — Rp{{ number_format($activeItem->price * $itemQty, 0, ',', '.') }}</span>
                </button>
            </div>
        </div>
    </div>
    @endif

    <!-- Cart Drawer / Bottom Sheet -->
    @if($showCartDrawer)
    <div class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-end sm:items-center justify-center p-0 sm:p-4">
        <div class="bg-white w-full max-w-lg rounded-t-3xl sm:rounded-3xl p-5 shadow-2xl flex flex-col max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between pb-3 border-b border-neutral-100">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-xl bg-[var(--color-brand-50)] text-[var(--color-brand-600)] flex items-center justify-center font-bold">
                        <i data-lucide="shopping-cart" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-neutral-900 leading-none">Keranjang Pesanan</h3>
                        <span class="text-[11px] font-medium text-neutral-500">{{ $table->label }}</span>
                    </div>
                </div>
                <button wire:click="$set('showCartDrawer', false)" class="p-1 rounded-full text-neutral-400 hover:text-neutral-700">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Cart Items List -->
            <div class="py-3 divide-y divide-neutral-100 max-h-56 overflow-y-auto">
                @foreach($cart as $cKey => $item)
                <div class="py-2.5 flex items-center justify-between gap-3">
                    <div class="flex-1 min-w-0">
                        <div class="text-xs font-bold text-neutral-900 leading-snug truncate">{{ $item['name'] }}</div>
                        @if($item['note'])
                        <div class="text-[10px] text-amber-700 italic truncate mt-0.5">Note: {{ $item['note'] }}</div>
                        @endif
                        <div class="text-[11px] text-neutral-500 tabular-nums">
                            Rp{{ number_format($item['unit_price'], 0, ',', '.') }} x {{ $item['qty'] }} = 
                            <span class="font-bold text-neutral-800">Rp{{ number_format($item['unit_price'] * $item['qty'], 0, ',', '.') }}</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        <button type="button" 
                                wire:click="decrementCart('{{ $cKey }}')" 
                                class="w-6 h-6 rounded-md bg-neutral-100 hover:bg-neutral-200 text-neutral-700 text-xs font-bold flex items-center justify-center">
                            -
                        </button>
                        <span class="text-xs font-extrabold tabular-nums w-4 text-center">{{ $item['qty'] }}</span>
                        <button type="button" 
                                wire:click="incrementCart('{{ $cKey }}')" 
                                class="w-6 h-6 rounded-md bg-[var(--color-brand-600)] hover:bg-[var(--color-brand-700)] text-white text-xs font-bold flex items-center justify-center">
                            +
                        </button>
                        <button type="button" 
                                wire:click="removeFromCart('{{ $cKey }}')" 
                                class="text-red-400 hover:text-red-600 ml-1">
                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                        </button>
                    </div>
                </div>
                @endforeach
            </div>

            <!-- Order Details Form -->
            <div class="pt-3 border-t border-neutral-100 space-y-3">
                <!-- Customer Name -->
                <div>
                    <label class="text-[11px] font-bold text-neutral-700 block mb-1">
                        Nama Pemesan {{ $isDelivery ? '<span class="text-red-500">*</span>' : '(Opsional)' }}
                    </label>
                    <input type="text" 
                           wire:model="customerName" 
                           placeholder="Contoh: Bpk. Ahmad / Zahra" 
                           class="w-full text-xs px-3 py-2 rounded-xl border border-neutral-200 focus:outline-none focus:ring-2 focus:ring-[var(--color-brand-600)]">
                    @error('customerName') <span class="text-[10px] text-red-500">{{ $message }}</span> @enderror
                </div>

                <!-- Delivery Destination (If Pesan Antar) -->
                @if($isDelivery)
                <div>
                    <label class="text-[11px] font-bold text-neutral-700 block mb-1">
                        Lokasi / Ruangan Pengantaran <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           wire:model="deliveryLocation" 
                           placeholder="Contoh: Gedung Rektorat Lt. 2, Ruang Wakil Rektor I" 
                           class="w-full text-xs px-3 py-2 rounded-xl border border-neutral-200 focus:outline-none focus:ring-2 focus:ring-[var(--color-brand-600)]">
                    @error('deliveryLocation') <span class="text-[10px] text-red-500">{{ $message }}</span> @enderror
                </div>
                @endif

                <!-- Honeypot spam trap -->
                <input type="text" wire:model="honeypot" class="hidden" tabindex="-1" autocomplete="off">

                <!-- Summary Breakdown -->
                <div class="bg-neutral-50 p-3 rounded-xl space-y-1 text-xs">
                    <div class="flex justify-between text-neutral-500">
                        <span>Total Item</span>
                        <span class="font-bold tabular-nums text-neutral-800">{{ $this->totalQty }} porsi</span>
                    </div>
                    <div class="flex justify-between text-neutral-900 font-extrabold text-sm pt-1 border-t border-neutral-200">
                        <span>Estimasi Total</span>
                        <span class="text-[var(--color-brand-700)] tabular-nums">Rp{{ number_format($this->subtotal, 0, ',', '.') }}</span>
                    </div>
                </div>

                <!-- Info Notice -->
                <div class="p-2.5 rounded-xl bg-amber-50 border border-amber-200/60 text-[11px] text-amber-900 flex items-start gap-2">
                    <i data-lucide="info" class="w-4 h-4 shrink-0 text-amber-600 mt-0.5"></i>
                    <span>
                        Pesanan akan diverifikasi oleh kasir. Pembayaran dapat dilakukan langsung di kasir UMCO House (Tunai / QRIS).
                    </span>
                </div>

                <!-- Submit Button -->
                <div class="pt-2">
                    <button type="button" 
                            wire:click="submitOrder" 
                            wire:loading.attr="disabled"
                            class="w-full py-3.5 rounded-2xl bg-[var(--color-brand-600)] text-white text-xs font-bold shadow-lg hover:bg-[var(--color-brand-700)] transition flex items-center justify-center gap-2 disabled:opacity-50">
                        <span wire:loading.remove>Kirim Pesanan ke Kasir</span>
                        <span wire:loading>Memproses Pesanan...</span>
                        <i data-lucide="send" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
