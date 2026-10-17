<div wire:poll.5s class="h-full flex flex-col md:flex-row overflow-hidden relative">

    <!-- ZONE 1: VERTICAL CATEGORY BAR (Tablet Landscape & Desktop) -->
    <div class="hidden lg:flex flex-col w-44 shrink-0 bg-[var(--color-surface)] border-r border-[var(--color-line)] p-3 overflow-y-auto space-y-1.5">
        <span class="text-[11px] font-bold text-[var(--color-ink-muted)] uppercase tracking-wider px-2 mb-1">
            Kategori
        </span>
        <button type="button"
                wire:click="$set('selectedCategoryId', null)"
                class="w-full text-left px-3 py-2.5 rounded-lg text-xs font-semibold transition-all flex items-center justify-between {{ is_null($selectedCategoryId) ? 'bg-[var(--color-brand-600)] text-white shadow-xs' : 'text-[var(--color-ink)] hover:bg-[var(--color-brand-50)]' }}">
            <span>Semua Menu</span>
            <i data-lucide="chevron-right" class="w-3.5 h-3.5 opacity-70"></i>
        </button>

        @foreach ($categories as $cat)
        <button type="button"
                wire:click="$set('selectedCategoryId', {{ $cat->id }})"
                class="w-full text-left px-3 py-2.5 rounded-lg text-xs font-semibold transition-all flex items-center justify-between {{ $selectedCategoryId === $cat->id ? 'bg-[var(--color-brand-600)] text-white shadow-xs' : 'text-[var(--color-ink)] hover:bg-[var(--color-brand-50)]' }}">
            <span class="truncate">{{ $cat->name }}</span>
            <span class="text-[10px] {{ $selectedCategoryId === $cat->id ? 'text-white/80' : 'text-[var(--color-ink-muted)]' }}">
                {{ $cat->type === 'drink' ? 'Bar' : 'Dapur' }}
            </span>
        </button>
        @endforeach
    </div>

    <!-- ZONE 2: MENU GRID & SEARCH -->
    <div class="flex-1 flex flex-col min-w-0 bg-[var(--color-bg)] overflow-hidden">
        <!-- Top Toolbar: Search, Mobile Category Pills & Actions -->
        <div class="p-3 bg-[var(--color-surface)] border-b border-[var(--color-line)] shrink-0 space-y-2">
            <div class="flex items-center gap-2">
                <!-- Search Menu -->
                <div class="relative flex-1">
                    <input type="text"
                           wire:model.live.debounce.200ms="search"
                           placeholder="Cari menu (cth: Kopi Susu, Nasi Goreng)..."
                           class="w-full h-10 pl-9 pr-8 text-xs sm:text-sm rounded-lg border border-[var(--color-line)] bg-white focus:border-[var(--color-brand-600)] focus:ring-1 focus:ring-[var(--color-brand-600)] outline-none">
                    <i data-lucide="search" class="w-4 h-4 text-[var(--color-ink-muted)] absolute left-3 top-3"></i>
                    @if ($search)
                    <button type="button" wire:click="$set('search', '')" class="absolute right-2.5 top-2.5 text-[var(--color-ink-muted)] hover:text-[var(--color-ink)]">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                    @endif
                </div>

                <!-- Custom Item Button (KF-14) -->
                <button type="button"
                        wire:click="$set('showCustomModal', true)"
                        class="h-10 px-3 rounded-lg border border-[var(--color-line)] bg-white hover:bg-[var(--color-brand-50)] text-[var(--color-ink)] text-xs font-semibold flex items-center gap-1.5 transition-colors shrink-0">
                    <i data-lucide="plus-circle" class="w-4 h-4 text-[var(--color-brand-600)]"></i>
                    <span class="hidden sm:inline">Item Custom</span>
                </button>

                <!-- Today's Orders Drawer Toggle -->
                <button type="button"
                        wire:click="$toggle('showOrdersDrawer')"
                        class="h-10 px-3 rounded-lg border border-[var(--color-line)] bg-white hover:bg-[var(--color-brand-50)] text-[var(--color-ink)] text-xs font-semibold flex items-center gap-1.5 transition-colors shrink-0">
                    <i data-lucide="clock" class="w-4 h-4 text-[var(--color-ink-muted)]"></i>
                    <span class="hidden sm:inline">Pesanan Hari Ini</span>
                </button>

                <!-- Pending Self-Service Orders Toggle (KF-33) -->
                @php $pendingCount = $pendingOrders->count(); @endphp
                <button type="button"
                        wire:click="$set('showPendingOrdersModal', true)"
                        class="h-10 px-3 rounded-lg border text-xs font-bold flex items-center gap-1.5 transition-all shrink-0 {{ $pendingCount > 0 ? 'bg-amber-500 hover:bg-amber-600 text-white border-amber-600 shadow-sm animate-pulse' : 'border-[var(--color-line)] bg-white text-[var(--color-ink)] hover:bg-[var(--color-brand-50)]' }}">
                    <i data-lucide="bell-ring" class="w-4 h-4 {{ $pendingCount > 0 ? 'text-white' : 'text-amber-500' }}"></i>
                    <span>Pesanan Masuk</span>
                    @if($pendingCount > 0)
                    <span class="px-1.5 py-0.5 rounded-full bg-white text-amber-800 font-extrabold text-[11px] tabular-nums">
                        {{ $pendingCount }}
                    </span>
                    @endif
                </button>
            </div>

            @if($pendingOrders->count() > 0)
            <div class="p-2.5 rounded-xl bg-amber-50 border border-amber-300 text-amber-900 text-xs flex items-center justify-between shadow-xs">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-500 animate-ping shrink-0"></span>
                    <span class="font-bold">Ada {{ $pendingOrders->count() }} pesanan self-service baru yang menunggu konfirmasi!</span>
                </div>
                <button type="button" wire:click="$set('showPendingOrdersModal', true)" class="px-3 py-1 rounded-lg bg-amber-500 text-white font-bold hover:bg-amber-600 transition text-[11px] shrink-0">
                    Konfirmasi Sekarang
                </button>
            </div>
            @endif

            <!-- Mobile & Tablet Category Horizontal Scroll -->
            <div class="lg:hidden flex items-center gap-1.5 overflow-x-auto pb-1 no-scrollbar text-xs">
                <button type="button"
                        wire:click="$set('selectedCategoryId', null)"
                        class="px-3 py-1.5 rounded-full font-semibold shrink-0 transition-all {{ is_null($selectedCategoryId) ? 'bg-[var(--color-brand-600)] text-white' : 'bg-white border border-[var(--color-line)] text-[var(--color-ink)]' }}">
                    Semua
                </button>
                @foreach ($categories as $cat)
                <button type="button"
                        wire:click="$set('selectedCategoryId', {{ $cat->id }})"
                        class="px-3 py-1.5 rounded-full font-semibold shrink-0 transition-all {{ $selectedCategoryId === $cat->id ? 'bg-[var(--color-brand-600)] text-white' : 'bg-white border border-[var(--color-line)] text-[var(--color-ink)]' }}">
                    {{ $cat->name }}
                </button>
                @endforeach
            </div>
        </div>

        <!-- Menu Grid Items -->
        <div class="flex-1 p-3 sm:p-4 overflow-y-auto">
            @if ($menuItems->isEmpty())
            <div class="h-64 flex flex-col items-center justify-center text-center p-6 text-[var(--color-ink-muted)]">
                <i data-lucide="utensils-crossed" class="w-10 h-10 mb-2 opacity-40"></i>
                <p class="text-sm font-semibold">Menu tidak ditemukan</p>
                <p class="text-xs mt-1">Coba kata kunci pencarian lain atau pilih kategori berbeda.</p>
            </div>
            @else
            <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-2.5 sm:gap-3">
                @foreach ($menuItems as $item)
                <div wire:key="menu-item-{{ $item->id }}"
                     @if ($item->is_available)
                     wire:click="addToCart({{ $item->id }})"
                     @endif
                     class="group relative flex flex-col justify-between p-3 rounded-xl border border-[var(--color-line)] bg-[var(--color-surface)] text-left select-none transition-all duration-150 {{ $item->is_available ? 'hover:border-[var(--color-brand-600)] hover:shadow-sm cursor-pointer active:scale-[0.98]' : 'opacity-50 cursor-not-allowed bg-neutral-50' }}">

                    <div>
                        <div class="flex items-start justify-between gap-1 mb-1.5">
                            <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded bg-[var(--color-brand-50)] text-[var(--color-brand-900)]">
                                {{ $item->category?->name ?? 'Menu' }}
                            </span>
                            @if (! $item->is_available)
                            <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-red-100 text-red-700">
                                Habis
                            </span>
                            @endif
                        </div>
                        <h4 class="text-xs sm:text-sm font-bold text-[var(--color-ink)] leading-snug line-clamp-2">
                            {{ $item->name }}
                        </h4>
                    </div>

                    <div class="mt-3 pt-2 border-t border-[var(--color-line)]/60 flex items-center justify-between">
                        <span class="text-xs sm:text-sm font-extrabold text-[var(--color-brand-700)] tabular-nums">
                            {{ $item->formattedPrice() }}
                        </span>
                        @if ($item->is_available)
                        <span class="w-6 h-6 rounded-full bg-[var(--color-brand-50)] text-[var(--color-brand-600)] flex items-center justify-center group-hover:bg-[var(--color-brand-600)] group-hover:text-white transition-colors">
                            <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                        </span>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>

        <!-- Mobile Cart Trigger Bar (Phones) -->
        <div class="md:hidden p-3 bg-[var(--color-surface)] border-t border-[var(--color-line)] shrink-0 flex items-center justify-between">
            <div>
                <span class="text-xs text-[var(--color-ink-muted)]">Pesanan ({{ $summary['total_qty'] }})</span>
                <div class="text-base font-extrabold text-[var(--color-ink)] tabular-nums">
                    Rp{{ number_format($summary['total'], 0, ',', '.') }}
                </div>
            </div>
            <button type="button"
                    onclick="document.getElementById('pos-cart-panel').classList.toggle('hidden')"
                    class="px-4 py-2 rounded-lg bg-[var(--color-brand-600)] text-white text-xs font-bold shadow-sm flex items-center gap-1.5">
                <i data-lucide="shopping-cart" class="w-4 h-4"></i>
                <span>Lihat Keranjang</span>
            </button>
        </div>
    </div>

    <!-- ZONE 3: ACTIVE CART PANEL (Pesanan Saat Ini) -->
    <div id="pos-cart-panel" class="hidden md:flex flex-col w-full md:w-96 lg:w-[410px] shrink-0 bg-[var(--color-surface)] border-l border-[var(--color-line)] h-full z-30 fixed inset-0 md:relative">
        
        <!-- Cart Header -->
        <div class="p-3.5 border-b border-[var(--color-line)] shrink-0 flex items-center justify-between bg-white">
            <div class="flex items-center gap-2">
                <i data-lucide="shopping-bag" class="w-5 h-5 text-[var(--color-brand-600)]"></i>
                <h3 class="text-sm font-bold text-[var(--color-ink)]">Pesanan Baru</h3>
            </div>
            <div class="flex items-center gap-1">
                @if (! empty($cart))
                <button type="button" 
                        wire:click="clearCart"
                        title="Kosongkan Keranjang"
                        class="p-1.5 text-xs text-[var(--color-ink-muted)] hover:text-red-600 hover:bg-red-50 rounded-md transition-colors">
                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                </button>
                @endif
                <button type="button" 
                        onclick="document.getElementById('pos-cart-panel').classList.add('hidden')"
                        class="md:hidden p-1.5 text-xs text-[var(--color-ink-muted)] hover:text-[var(--color-ink)] rounded-md">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
        </div>

        <!-- Customer & Table Bar -->
        <div class="p-3 border-b border-[var(--color-line)] bg-[var(--color-bg)]/50 shrink-0 grid grid-cols-2 gap-2 text-xs">
            <div>
                <label class="block text-[10px] font-semibold text-[var(--color-ink-muted)] mb-1 uppercase tracking-wider">
                    Pelanggan
                </label>
                <input type="text"
                       wire:model="customerName"
                       placeholder="Nama Pelanggan / Dosen"
                       class="w-full h-8 px-2 text-xs rounded-md border border-[var(--color-line)] bg-white focus:border-[var(--color-brand-600)] outline-none">
            </div>
            <div>
                <label class="block text-[10px] font-semibold text-[var(--color-ink-muted)] mb-1 uppercase tracking-wider">
                    Meja / Tipe
                </label>
                <select wire:model="selectedTableId"
                        class="w-full h-8 px-2 text-xs rounded-md border border-[var(--color-line)] bg-white focus:border-[var(--color-brand-600)] outline-none">
                    <option value="">Bawa Pulang (Takeaway)</option>
                    @foreach ($tables as $t)
                    <option value="{{ $t->id }}">{{ $t->label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Cart Items List -->
        <div class="flex-1 overflow-y-auto p-3 space-y-2.5 divide-y divide-[var(--color-line)]/50">
            @if (empty($cart))
            <div class="h-48 flex flex-col items-center justify-center text-center p-6 text-[var(--color-ink-muted)]">
                <i data-lucide="shopping-cart" class="w-8 h-8 mb-2 opacity-30"></i>
                <p class="text-xs font-semibold">Keranjang masih kosong</p>
                <p class="text-[11px] mt-0.5">Pilih menu di sebelah kiri untuk menambahkan pesanan.</p>
            </div>
            @else
            @foreach ($cart as $key => $item)
            <div wire:key="cart-{{ $key }}" class="pt-2 first:pt-0 space-y-1.5">
                <!-- Line Info & Stepper -->
                <div class="flex items-start justify-between gap-2">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-1.5">
                            <span class="text-xs font-bold text-[var(--color-ink)] leading-snug">
                                {{ $item['name'] }}
                            </span>
                            @if ($item['is_custom'])
                            <span class="text-[9px] font-semibold px-1 py-0.2 rounded bg-amber-100 text-amber-800">
                                Custom
                            </span>
                            @endif
                        </div>
                        <div class="text-[11px] text-[var(--color-ink-muted)] tabular-nums mt-0.5">
                            Rp{{ number_format($item['unit_price'], 0, ',', '.') }} × {{ $item['qty'] }}
                        </div>
                    </div>

                    <!-- Quantity Controls -->
                    <div class="flex items-center gap-1 shrink-0">
                        <button type="button"
                                wire:click="updateQty('{{ $key }}', -1)"
                                class="w-7 h-7 rounded-md border border-[var(--color-line)] bg-white hover:bg-neutral-100 text-[var(--color-ink)] flex items-center justify-center text-xs font-bold">
                            -
                        </button>
                        <span class="w-6 text-center text-xs font-bold text-[var(--color-ink)] tabular-nums">
                            {{ $item['qty'] }}
                        </span>
                        <button type="button"
                                wire:click="updateQty('{{ $key }}', 1)"
                                class="w-7 h-7 rounded-md border border-[var(--color-line)] bg-white hover:bg-neutral-100 text-[var(--color-ink)] flex items-center justify-center text-xs font-bold">
                            +
                        </button>
                    </div>

                    <div class="text-right shrink-0 min-w-[70px]">
                        <div class="text-xs font-extrabold text-[var(--color-ink)] tabular-nums">
                            Rp{{ number_format($item['unit_price'] * $item['qty'], 0, ',', '.') }}
                        </div>
                    </div>
                </div>

                <!-- Special Note & Quick Chips (KF-15) -->
                <div class="space-y-1 bg-[var(--color-bg)]/80 p-1.5 rounded-lg border border-[var(--color-line)]/60 text-xs">
                    <div class="flex items-center gap-1">
                        <input type="text"
                               wire:model.live.debounce.300ms="cart.{{ $key }}.note"
                               placeholder="Catatan khusus (cth: gula pisah, panas)..."
                               class="w-full text-[11px] px-2 py-1 rounded border border-[var(--color-line)] bg-white outline-none focus:border-[var(--color-brand-600)]">
                    </div>

                    <!-- Fast Note Chips -->
                    <div class="flex flex-wrap gap-1 pt-0.5">
                        @foreach (['Normal ice', 'Less ice', 'No ice', 'Less sugar', 'No sugar', 'Extra topping'] as $chip)
                        <button type="button"
                                wire:click="appendNoteChip('{{ $key }}', '{{ $chip }}')"
                                class="text-[9px] px-1.5 py-0.5 rounded border border-[var(--color-line)] bg-white hover:bg-[var(--color-brand-50)] text-[var(--color-ink-muted)] hover:text-[var(--color-brand-700)] transition-colors">
                            + {{ $chip }}
                        </button>
                        @endforeach
                    </div>
                </div>
            </div>
            @endforeach
            @endif
        </div>

        <!-- Discounts Section (KF-17, KF-18) -->
        <div class="p-3 border-t border-[var(--color-line)] bg-white shrink-0 space-y-2">
            <div class="flex items-center justify-between text-xs">
                <span class="font-bold text-[var(--color-ink)] flex items-center gap-1">
                    <i data-lucide="tag" class="w-3.5 h-3.5 text-[var(--color-brand-600)]"></i>
                    Diskon Identitas
                </span>
                <span class="text-[10px] text-[var(--color-ink-muted)]">Pilih 1 diskon</span>
            </div>

            <div class="grid grid-cols-3 gap-1 text-[11px]">
                <button type="button"
                        wire:click="$set('selectedDiscountId', null)"
                        class="py-1.5 px-2 rounded-md border text-center font-medium transition-all {{ is_null($selectedDiscountId) ? 'border-[var(--color-brand-600)] bg-[var(--color-brand-50)] text-[var(--color-brand-700)] font-bold' : 'border-[var(--color-line)] bg-white text-[var(--color-ink)]' }}">
                    Normal
                </button>

                @foreach ($discounts as $disc)
                <button type="button"
                        wire:click="$set('selectedDiscountId', {{ $disc->id }})"
                        class="py-1.5 px-1.5 rounded-md border text-center transition-all truncate {{ $selectedDiscountId === $disc->id ? 'border-[var(--color-brand-600)] bg-[var(--color-brand-50)] text-[var(--color-brand-700)] font-bold' : 'border-[var(--color-line)] bg-white text-[var(--color-ink)]' }}">
                    {{ $disc->name }}
                </button>
                @endforeach
            </div>

            @if ($summary['order_discount'] > 0)
            <div class="p-1.5 rounded-md bg-emerald-50 border border-emerald-200 text-emerald-800 text-[11px] flex items-center justify-between">
                <span>Diskon Minimal 3 Produk Aktif:</span>
                <span class="font-bold">-Rp{{ number_format($summary['order_discount'], 0, ',', '.') }}</span>
            </div>
            @endif
        </div>

        <!-- Calculation & Action Bottom Footer -->
        <div class="p-4 border-t border-[var(--color-line)] bg-[var(--color-bg)]/80 shrink-0 space-y-3">
            <div class="space-y-1 text-xs">
                <div class="flex items-center justify-between text-[var(--color-ink-muted)]">
                    <span>Subtotal</span>
                    <span class="font-medium tabular-nums">Rp{{ number_format($summary['subtotal'], 0, ',', '.') }}</span>
                </div>
                @if ($summary['discount_total'] > 0)
                <div class="flex items-center justify-between text-red-600 font-semibold">
                    <span>Total Diskon</span>
                    <span class="tabular-nums">-Rp{{ number_format($summary['discount_total'], 0, ',', '.') }}</span>
                </div>
                @endif
                <div class="pt-1 border-t border-[var(--color-line)] flex items-center justify-between">
                    <span class="text-sm font-bold text-[var(--color-ink)]">Total Bayar</span>
                    <span class="text-2xl font-black text-[var(--color-brand-700)] tabular-nums">
                        Rp{{ number_format($summary['total'], 0, ',', '.') }}
                    </span>
                </div>
            </div>

            <!-- Buttons: Open Bill & Pay -->
            <div class="grid grid-cols-2 gap-2 pt-1">
                <button type="button"
                        wire:click="$set('showOpenBillModal', true)"
                        @disabled(empty($cart))
                        class="h-11 rounded-lg border border-[var(--color-line)] bg-white hover:bg-[var(--color-brand-50)] text-[var(--color-ink)] font-bold text-xs shadow-xs transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-1.5">
                    <i data-lucide="file-text" class="w-4 h-4 text-amber-600"></i>
                    <span>Simpan Open Bill</span>
                </button>

                <button type="button"
                        wire:click="openPaymentModal"
                        @disabled(empty($cart))
                        class="h-11 rounded-lg bg-[var(--color-brand-600)] hover:bg-[var(--color-brand-700)] text-white font-bold text-sm shadow-sm transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                    <span>Bayar</span>
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL 1: CUSTOM ITEM MODAL (KF-14) -->
    @if ($showCustomModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs">
        <div class="bg-[var(--color-surface)] w-full max-w-sm rounded-xl border border-[var(--color-line)] shadow-xl p-5 space-y-4">
            <div class="flex items-center justify-between pb-2 border-b border-[var(--color-line)]">
                <h3 class="text-sm font-bold text-[var(--color-ink)]">Tambah Pesanan Custom</h3>
                <button type="button" wire:click="$set('showCustomModal', false)" class="text-[var(--color-ink-muted)] hover:text-[var(--color-ink)]">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <div class="space-y-3 text-xs">
                <div>
                    <label class="block font-semibold mb-1">Nama Pesanan / Tambahan:</label>
                    <input type="text"
                           wire:model="customName"
                           placeholder="Contoh: Tambah Telur Dadar / Es Batu Jumbo"
                           class="w-full h-10 px-3 rounded-lg border border-[var(--color-line)] bg-white text-xs outline-none focus:border-[var(--color-brand-600)]">
                    @error('customName') <span class="text-red-600 text-[11px] mt-0.5 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block font-semibold mb-1">Harga (Rupiah):</label>
                    <input type="number"
                           wire:model="customPrice"
                           placeholder="5000"
                           class="w-full h-10 px-3 rounded-lg border border-[var(--color-line)] bg-white text-xs outline-none focus:border-[var(--color-brand-600)]">
                    @error('customPrice') <span class="text-red-600 text-[11px] mt-0.5 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block font-semibold mb-1">Stasiun Produksi:</label>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="flex items-center gap-2 p-2 border rounded-lg cursor-pointer {{ $customType === 'drink' ? 'border-[var(--color-brand-600)] bg-[var(--color-brand-50)] font-bold' : 'border-[var(--color-line)]' }}">
                            <input type="radio" wire:model="customType" value="drink" class="hidden">
                            <span>Bar (Minuman)</span>
                        </label>
                        <label class="flex items-center gap-2 p-2 border rounded-lg cursor-pointer {{ $customType === 'food' ? 'border-[var(--color-brand-600)] bg-[var(--color-brand-50)] font-bold' : 'border-[var(--color-line)]' }}">
                            <input type="radio" wire:model="customType" value="food" class="hidden">
                            <span>Dapur (Makanan)</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="pt-2 flex items-center justify-end gap-2">
                <button type="button" wire:click="$set('showCustomModal', false)" class="px-3 py-2 text-xs font-semibold text-[var(--color-ink-muted)]">
                    Batal
                </button>
                <button type="button" wire:click="addCustomItem" class="px-4 py-2 text-xs font-bold rounded-lg bg-[var(--color-brand-600)] text-white hover:bg-[var(--color-brand-700)]">
                    Tambahkan ke Keranjang
                </button>
            </div>
        </div>
    </div>
    @endif

    <!-- MODAL 2: OPEN BILL MODAL (KF-20) -->
    @if ($showOpenBillModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs">
        <div class="bg-[var(--color-surface)] w-full max-w-sm rounded-xl border border-[var(--color-line)] shadow-xl p-5 space-y-4">
            <div class="flex items-center justify-between pb-2 border-b border-[var(--color-line)]">
                <div class="flex items-center gap-2">
                    <i data-lucide="file-text" class="w-4 h-4 text-amber-600"></i>
                    <h3 class="text-sm font-bold text-[var(--color-ink)]">Simpan Sebagai Open Bill</h3>
                </div>
                <button type="button" wire:click="$set('showOpenBillModal', false)" class="text-[var(--color-ink-muted)] hover:text-[var(--color-ink)]">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <div class="space-y-3 text-xs">
                <div>
                    <label class="block font-semibold mb-1">Nama Pemesan / Instansi (Wajib):</label>
                    <input type="text"
                           wire:model="customerName"
                           placeholder="Contoh: Dosen Fak. Teknik / Rapat BUMK"
                           class="w-full h-10 px-3 rounded-lg border border-[var(--color-line)] bg-white text-xs outline-none focus:border-[var(--color-brand-600)]">
                    @error('customerName') <span class="text-red-600 text-[11px] mt-0.5 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block font-semibold mb-1">Catatan Keperluan / Kegiatan (Wajib):</label>
                    <textarea wire:model="openBillNote"
                              rows="3"
                              placeholder="Contoh: Pesanan konsumsi tamu dekanat, bayar via memo BUMK"
                              class="w-full p-2.5 rounded-lg border border-[var(--color-line)] bg-white text-xs outline-none focus:border-[var(--color-brand-600)]"></textarea>
                    @error('openBillNote') <span class="text-red-600 text-[11px] mt-0.5 block">{{ $message }}</span> @enderror
                </div>

                <div class="p-2.5 rounded-lg bg-amber-50 border border-amber-200 text-amber-800 text-[11px]">
                    Open bill akan dicatat sebagai piutang (belum dibayar) dan pesanan langsung diteruskan ke stasiun produksi.
                </div>
            </div>

            <div class="pt-2 flex items-center justify-end gap-2">
                <button type="button" wire:click="$set('showOpenBillModal', false)" class="px-3 py-2 text-xs font-semibold text-[var(--color-ink-muted)]">
                    Batal
                </button>
                <button type="button" wire:click="saveOpenBill" class="px-4 py-2 text-xs font-bold rounded-lg bg-amber-600 hover:bg-amber-700 text-white">
                    Simpan Open Bill
                </button>
            </div>
        </div>
    </div>
    @endif

    <!-- MODAL 3: PAYMENT MODAL (TUNAI & QRIS) -->
    @if ($showPaymentModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs">
        <div class="bg-[var(--color-surface)] w-full max-w-md rounded-2xl border border-[var(--color-line)] shadow-2xl p-6 space-y-4">
            
            <div class="flex items-center justify-between pb-3 border-b border-[var(--color-line)]">
                <div>
                    <h3 class="text-base font-bold text-[var(--color-ink)]">Pembayaran Pesanan</h3>
                    <span class="text-xs text-[var(--color-ink-muted)]">Pilih metode pembayaran kasir</span>
                </div>
                <button type="button" wire:click="$set('showPaymentModal', false)" class="text-[var(--color-ink-muted)] hover:text-[var(--color-ink)]">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Total to Pay Banner -->
            <div class="p-4 rounded-xl bg-[var(--color-brand-50)] border border-[var(--color-brand-100)] flex items-center justify-between">
                <span class="text-xs font-semibold text-[var(--color-brand-900)]">Total Tagihan:</span>
                <span class="text-2xl font-black text-[var(--color-brand-700)] tabular-nums">
                    Rp{{ number_format($summary['total'], 0, ',', '.') }}
                </span>
            </div>

            <!-- Payment Method Tabs -->
            <div class="grid grid-cols-2 gap-2">
                <button type="button"
                        wire:click="$set('paymentMethod', 'cash')"
                        class="p-3 rounded-xl border text-center transition-all flex flex-col items-center gap-1.5 {{ $paymentMethod === 'cash' ? 'border-[var(--color-brand-600)] bg-[var(--color-brand-50)] text-[var(--color-brand-700)] font-bold ring-1 ring-[var(--color-brand-600)]' : 'border-[var(--color-line)] bg-white text-[var(--color-ink)]' }}">
                    <i data-lucide="banknote" class="w-5 h-5"></i>
                    <span class="text-xs">Uang Tunai (Cash)</span>
                </button>

                <button type="button"
                        wire:click="$set('paymentMethod', 'qris')"
                        class="p-3 rounded-xl border text-center transition-all flex flex-col items-center gap-1.5 {{ $paymentMethod === 'qris' ? 'border-[var(--color-brand-600)] bg-[var(--color-brand-50)] text-[var(--color-brand-700)] font-bold ring-1 ring-[var(--color-brand-600)]' : 'border-[var(--color-line)] bg-white text-[var(--color-ink)]' }}">
                    <i data-lucide="qr-code" class="w-5 h-5"></i>
                    <span class="text-xs">QRIS UMCO</span>
                </button>
            </div>

            <!-- CASH TAB CONTENT -->
            @if ($paymentMethod === 'cash')
            <div class="space-y-3 text-xs pt-1">
                <div>
                    <label class="block font-semibold mb-1 text-[var(--color-ink)]">Nominal Uang Diterima:</label>
                    <input type="number"
                           wire:model.live.debounce.150ms="cashReceived"
                           class="w-full h-12 px-3 text-lg font-bold text-right rounded-lg border border-[var(--color-line)] bg-white outline-none focus:border-[var(--color-brand-600)] focus:ring-1 focus:ring-[var(--color-brand-600)] tabular-nums">
                    @error('cashReceived') <span class="text-red-600 text-[11px] mt-0.5 block font-semibold">{{ $message }}</span> @enderror
                </div>

                <!-- Quick Cash Presets -->
                <div class="grid grid-cols-3 gap-1.5">
                    <button type="button"
                            wire:click="setQuickCash('exact')"
                            class="py-2 rounded-lg border border-[var(--color-line)] bg-white hover:bg-[var(--color-brand-50)] text-xs font-bold text-[var(--color-brand-700)]">
                        Uang Pas
                    </button>
                    @foreach ([20000, 50000, 100000, 150000, 200000] as $preset)
                    @if ($preset >= $summary['total'])
                    <button type="button"
                            wire:click="setQuickCash({{ $preset }})"
                            class="py-2 rounded-lg border border-[var(--color-line)] bg-white hover:bg-[var(--color-brand-50)] text-xs font-bold tabular-nums text-[var(--color-ink)]">
                        {{ number_format($preset / 1000, 0) }}k
                    </button>
                    @endif
                    @endforeach
                </div>

                <!-- Calculated Change Display -->
                <div class="p-3.5 rounded-xl border border-[var(--color-line)] bg-[var(--color-bg)] flex items-center justify-between">
                    <span class="text-xs font-semibold text-[var(--color-ink-muted)]">Kembalian:</span>
                    <span class="text-xl font-extrabold tabular-nums {{ $cashReceived >= $summary['total'] ? 'text-emerald-700' : 'text-red-600' }}">
                        Rp{{ number_format(max(0, $cashReceived - $summary['total']), 0, ',', '.') }}
                    </span>
                </div>
            </div>
            @endif

            <!-- QRIS TAB CONTENT -->
            @if ($paymentMethod === 'qris')
            <div class="space-y-3 text-center text-xs pt-1">
                <div class="p-4 bg-white border border-[var(--color-line)] rounded-xl inline-block mx-auto shadow-xs">
                    <!-- Static QR Code Canvas / Visual representation for UMCO QRIS -->
                    <div class="w-44 h-44 mx-auto bg-neutral-900 rounded-lg p-2 flex flex-col items-center justify-between text-white">
                        <div class="text-[10px] font-bold tracking-widest text-emerald-400">QRIS UMCO HOUSE</div>
                        <i data-lucide="qr-code" class="w-24 h-24 text-white"></i>
                        <div class="text-[9px] text-neutral-300">NMID: ID102026UMRI01</div>
                    </div>
                </div>
                <p class="text-xs font-semibold text-[var(--color-ink)]">
                    Scan QRIS Statis UMCO House di atas melalui m-Banking / E-Wallet.
                </p>
                <div>
                    <input type="text"
                           wire:model="qrisReference"
                           placeholder="No. Referensi / RRN (Opsional)"
                           class="w-full h-9 px-3 text-xs text-center rounded-lg border border-[var(--color-line)] bg-white outline-none focus:border-[var(--color-brand-600)]">
                </div>
            </div>
            @endif

            <!-- Modal Action Buttons -->
            <div class="pt-3 border-t border-[var(--color-line)] flex items-center justify-end gap-2">
                <button type="button" wire:click="$set('showPaymentModal', false)" class="px-4 py-2.5 text-xs font-semibold text-[var(--color-ink-muted)]">
                    Batal
                </button>
                <button type="button" 
                        wire:click="processPayment"
                        class="px-5 py-2.5 rounded-lg bg-[var(--color-brand-600)] hover:bg-[var(--color-brand-700)] text-white text-xs font-bold shadow-sm flex items-center gap-1.5">
                    <i data-lucide="check" class="w-4 h-4"></i>
                    <span>Konfirmasi Lunas & Cetak</span>
                </button>
            </div>
        </div>
    </div>
    @endif

    <!-- MODAL 4: RECEIPT MODAL & BROWSER PRINT (58mm / 80mm) -->
    @if ($showReceiptModal && $completedOrder)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs">
        <div class="bg-[var(--color-surface)] w-full max-w-sm rounded-2xl border border-[var(--color-line)] shadow-2xl p-5 space-y-4 max-h-[90vh] flex flex-col">
            
            <div class="flex items-center justify-between pb-2 border-b border-[var(--color-line)] shrink-0">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                    <h3 class="text-sm font-bold text-[var(--color-ink)]">Struk Transaksi</h3>
                </div>
                <button type="button" wire:click="closeReceiptModal" class="text-[var(--color-ink-muted)] hover:text-[var(--color-ink)]">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <!-- Receipt Content (Thermal Receipt 58/80 mm) -->
            <div class="flex-1 overflow-y-auto p-4 bg-white border border-[var(--color-line)] rounded-xl font-mono text-[11px] leading-tight text-neutral-800 space-y-2 select-text" id="printable-receipt">
                <div class="text-center space-y-0.5 border-b border-dashed border-neutral-300 pb-2">
                    <div class="font-extrabold text-sm uppercase tracking-wider text-black">UMCO HOUSE</div>
                    <div class="text-[10px]">Unit Bisnis BUMK Univ. Muhammadiyah Riau</div>
                    <div class="text-[9px] text-neutral-500">Jl. Tuanku Tambusai, Pekanbaru</div>
                </div>

                <div class="space-y-0.5 text-[10px] border-b border-dashed border-neutral-300 pb-2">
                    <div class="flex justify-between">
                        <span>No. Order:</span>
                        <span class="font-bold text-black">{{ $completedOrder->number }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Waktu:</span>
                        <span>{{ $completedOrder->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Kasir:</span>
                        <span>{{ $completedOrder->cashier?->name ?? 'Kasir' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Pelanggan:</span>
                        <span>{{ $completedOrder->customer_name ?: 'Walk-in' }} ({{ $completedOrder->table_label }})</span>
                    </div>
                    @if ($completedOrder->is_open_bill)
                    <div class="mt-1 p-1 bg-amber-50 text-amber-800 text-[10px] font-bold text-center border border-amber-200">
                        OPEN BILL (BELUM LUNAS)
                    </div>
                    @endif
                </div>

                <!-- Items -->
                <div class="border-b border-dashed border-neutral-300 py-1 space-y-1">
                    @foreach ($completedOrder->items as $it)
                    <div>
                        <div class="flex justify-between font-bold text-black">
                            <span>{{ $it->name }}</span>
                            <span class="tabular-nums">Rp{{ number_format($it->lineSubtotal(), 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between text-[10px] text-neutral-600">
                            <span>{{ $it->qty }} × Rp{{ number_format($it->unit_price, 0, ',', '.') }}</span>
                            @if ($it->discount_amount > 0)
                            <span class="text-red-600">-Rp{{ number_format($it->discount_amount * $it->qty, 0, ',', '.') }}</span>
                            @endif
                        </div>
                        @if ($it->note)
                        <div class="text-[9px] italic text-neutral-500 pl-1">* {{ $it->note }}</div>
                        @endif
                    </div>
                    @endforeach
                </div>

                <!-- Totals -->
                <div class="space-y-0.5 text-[10px] border-b border-dashed border-neutral-300 pb-2">
                    <div class="flex justify-between">
                        <span>Subtotal:</span>
                        <span class="tabular-nums">Rp{{ number_format($completedOrder->subtotal, 0, ',', '.') }}</span>
                    </div>
                    @if ($completedOrder->discount_total > 0)
                    <div class="flex justify-between text-red-600 font-semibold">
                        <span>Diskon:</span>
                        <span class="tabular-nums">-Rp{{ number_format($completedOrder->discount_total, 0, ',', '.') }}</span>
                    </div>
                    @endif
                    <div class="flex justify-between text-xs font-extrabold text-black pt-1">
                        <span>TOTAL:</span>
                        <span class="tabular-nums text-sm">Rp{{ number_format($completedOrder->total, 0, ',', '.') }}</span>
                    </div>
                </div>

                <!-- Payments info -->
                @if ($completedOrder->isPaid() && $completedOrder->payments->isNotEmpty())
                @php $pay = $completedOrder->payments->first(); @endphp
                <div class="space-y-0.5 text-[10px] border-b border-dashed border-neutral-300 pb-2">
                    <div class="flex justify-between">
                        <span>Metode:</span>
                        <span class="font-bold uppercase">{{ $pay->method->value }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Bayar:</span>
                        <span class="tabular-nums">Rp{{ number_format($pay->cash_received, 0, ',', '.') }}</span>
                    </div>
                    @if ($pay->change > 0)
                    <div class="flex justify-between font-bold">
                        <span>Kembalian:</span>
                        <span class="tabular-nums">Rp{{ number_format($pay->change, 0, ',', '.') }}</span>
                    </div>
                    @endif
                    <div class="mt-1 text-center font-bold text-xs text-emerald-800 bg-emerald-50 py-1 rounded">
                        *** LUNAS ***
                    </div>
                </div>
                @endif

                <div class="text-center text-[10px] text-neutral-500 pt-1">
                    Terima kasih atas kunjungan Anda di UMCO House!<br>
                    WiFi: UMCO_HOUSE | Password: muhammadiyah
                </div>
            </div>

            <!-- Receipt Actions -->
            <div class="pt-2 flex items-center justify-between gap-2 shrink-0">
                <button type="button"
                        onclick="window.print()"
                        class="flex-1 h-10 rounded-lg bg-[var(--color-brand-600)] hover:bg-[var(--color-brand-700)] text-white text-xs font-bold flex items-center justify-center gap-1.5 shadow-xs">
                    <i data-lucide="printer" class="w-4 h-4"></i>
                    <span>Cetak Struk</span>
                </button>
                <button type="button"
                        wire:click="closeReceiptModal"
                        class="h-10 px-4 rounded-lg border border-[var(--color-line)] bg-white hover:bg-[var(--color-brand-50)] text-xs font-bold text-[var(--color-ink)]">
                    Selesai
                </button>
            </div>
        </div>
    </div>
    @endif

    <!-- DRAWER: TODAY'S ORDERS DRAWER -->
    @if ($showOrdersDrawer)
    <div class="fixed inset-0 z-40 flex justify-end bg-black/30 backdrop-blur-2xs">
        <div class="bg-[var(--color-surface)] w-full max-w-md h-full border-l border-[var(--color-line)] shadow-2xl flex flex-col">
            <div class="p-4 border-b border-[var(--color-line)] flex items-center justify-between shrink-0">
                <div class="flex items-center gap-2">
                    <i data-lucide="history" class="w-5 h-5 text-[var(--color-brand-600)]"></i>
                    <h3 class="text-sm font-bold text-[var(--color-ink)]">Riwayat Pesanan Hari Ini</h3>
                </div>
                <button type="button" wire:click="$set('showOrdersDrawer', false)" class="text-[var(--color-ink-muted)] hover:text-[var(--color-ink)]">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <div class="px-4 py-2 bg-[var(--color-bg)] border-b border-[var(--color-line)] flex gap-1 text-xs shrink-0">
                <button type="button"
                        wire:click="$set('ordersFilter', 'all')"
                        class="px-2.5 py-1 rounded-md font-semibold {{ $ordersFilter === 'all' ? 'bg-white shadow-2xs font-bold text-[var(--color-brand-700)]' : 'text-[var(--color-ink-muted)]' }}">
                    Semua
                </button>
                <button type="button"
                        wire:click="$set('ordersFilter', 'paid')"
                        class="px-2.5 py-1 rounded-md font-semibold {{ $ordersFilter === 'paid' ? 'bg-white shadow-2xs font-bold text-emerald-700' : 'text-[var(--color-ink-muted)]' }}">
                    Lunas
                </button>
                <button type="button"
                        wire:click="$set('ordersFilter', 'unpaid')"
                        class="px-2.5 py-1 rounded-md font-semibold {{ $ordersFilter === 'unpaid' ? 'bg-white shadow-2xs font-bold text-amber-700' : 'text-[var(--color-ink-muted)]' }}">
                    Open Bill
                </button>
            </div>

            <div class="flex-1 overflow-y-auto p-4 space-y-2">
                @if ($todayOrders->isEmpty())
                <div class="h-48 flex flex-col items-center justify-center text-center text-[var(--color-ink-muted)] text-xs">
                    <p>Belum ada transaksi hari ini.</p>
                </div>
                @else
                @foreach ($todayOrders as $ord)
                <div class="p-3 rounded-xl border border-[var(--color-line)] bg-white space-y-2 text-xs">
                    <div class="flex items-center justify-between">
                        <span class="font-extrabold text-[var(--color-ink)]">{{ $ord->number }}</span>
                        @if ($ord->isPaid())
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-emerald-100 text-emerald-800">Lunas</span>
                        @else
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-amber-100 text-amber-800">Open Bill</span>
                        @endif
                    </div>
                    <div class="flex items-center justify-between text-[var(--color-ink-muted)] text-[11px]">
                        <span>{{ $ord->customer_name ?: 'Walk-in' }} ({{ $ord->table_label }})</span>
                        <span class="font-bold text-[var(--color-ink)] tabular-nums">
                            {{ $ord->formattedTotal() }}
                        </span>
                    </div>
                    <div class="pt-2 border-t border-[var(--color-line)]/50 flex items-center justify-between">
                        <span class="text-[10px] text-[var(--color-ink-muted)]">{{ $ord->created_at->format('H:i') }} WIB</span>
                        <button type="button"
                                wire:click="viewReceipt({{ $ord->id }})"
                                class="text-xs font-semibold text-[var(--color-brand-600)] hover:underline flex items-center gap-1">
                            <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                            <span>Cetak Ulang</span>
                        </button>
                    </div>
                </div>
                @endforeach
                @endif
            </div>
        </div>
    </div>
    @endif

    <!-- PENDING SELF-SERVICE ORDERS MODAL (KF-33) -->
    @if ($showPendingOrdersModal)
    <div class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-[var(--color-surface)] w-full max-w-2xl rounded-2xl shadow-2xl flex flex-col max-h-[90vh] overflow-hidden border border-[var(--color-line)]">
            <!-- Modal Header -->
            <div class="p-4 border-b border-[var(--color-line)] flex items-center justify-between bg-amber-500/10">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-amber-500 text-white flex items-center justify-center font-bold shadow-xs">
                        <i data-lucide="bell-ring" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-[var(--color-ink)] leading-none">
                            Pesanan Masuk (Self-Service)
                        </h3>
                        <span class="text-[11px] text-[var(--color-ink-muted)]">
                            Konfirmasi pesanan agar tiket otomatis dicetak ke Barista & Dapur
                        </span>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <span class="px-2.5 py-1 rounded-full bg-amber-500 text-white text-xs font-bold tabular-nums">
                        {{ $pendingOrders->count() }} Menunggu
                    </span>
                    <button type="button"
                            wire:click="$set('showPendingOrdersModal', false)"
                            class="p-1 rounded-lg text-[var(--color-ink-muted)] hover:text-[var(--color-ink)] hover:bg-[var(--color-line)]/50">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
            </div>

            <!-- Modal Body: Orders List -->
            <div class="flex-1 overflow-y-auto p-4 space-y-4">
                @if ($pendingOrders->isEmpty())
                <div class="py-12 flex flex-col items-center justify-center text-center text-[var(--color-ink-muted)]">
                    <div class="w-12 h-12 rounded-full bg-neutral-100 flex items-center justify-center text-neutral-400 mb-2">
                        <i data-lucide="inbox" class="w-6 h-6"></i>
                    </div>
                    <p class="text-xs font-semibold">Tidak ada pesanan masuk saat ini.</p>
                    <p class="text-[11px] text-neutral-400 mt-0.5">Pesanan dari scan QR meja akan muncul di sini secara langsung.</p>
                </div>
                @else
                @foreach ($pendingOrders as $pOrd)
                <div class="p-4 rounded-xl border-2 border-amber-300 bg-amber-50/30 space-y-3">
                    <!-- Order Header -->
                    <div class="flex items-center justify-between pb-2.5 border-b border-amber-200/80">
                        <div class="flex items-center gap-2">
                            <span class="px-2.5 py-1 rounded-lg {{ $pOrd->source->value === 'delivery' ? 'bg-amber-500 text-white' : 'bg-[var(--color-brand-600)] text-white' }} text-xs font-bold shadow-xs">
                                {{ $pOrd->table_label }}
                            </span>
                            <span class="font-extrabold text-sm text-[var(--color-ink)]">
                                #{{ $pOrd->number }}
                            </span>
                        </div>
                        <div class="text-right">
                            <span class="text-[11px] text-neutral-500 font-medium">
                                {{ $pOrd->created_at->diffForHumans() }}
                            </span>
                        </div>
                    </div>

                    <!-- Customer & Note info -->
                    <div class="text-xs text-[var(--color-ink-muted)] space-y-0.5">
                        <div>Pemesan: <strong class="text-[var(--color-ink)]">{{ $pOrd->customer_name ?: 'Pelanggan Meja' }}</strong></div>
                        @if($pOrd->open_bill_note)
                        <div class="text-amber-800 font-semibold bg-amber-100/80 px-2 py-1 rounded-md text-[11px]">
                            📍 Lokasi Antar: {{ $pOrd->open_bill_note }}
                        </div>
                        @endif
                    </div>

                    <!-- Items Breakdown -->
                    <div class="bg-white rounded-lg p-3 border border-neutral-200 space-y-1.5">
                        @foreach ($pOrd->items as $pItem)
                        <div class="flex items-start justify-between text-xs">
                            <div class="flex-1 min-w-0 pr-2">
                                <span class="font-bold text-[var(--color-ink)]">{{ $pItem->qty }}x</span>
                                <span class="text-[var(--color-ink)] font-semibold">{{ $pItem->name }}</span>
                                @if($pItem->note)
                                <span class="text-[11px] text-amber-700 italic block">Catatan: {{ $pItem->note }}</span>
                                @endif
                            </div>
                            <span class="tabular-nums font-semibold text-neutral-600 shrink-0">
                                Rp{{ number_format($pItem->unit_price * $pItem->qty, 0, ',', '.') }}
                            </span>
                        </div>
                        @endforeach

                        <div class="pt-2 border-t border-neutral-100 flex justify-between items-center text-xs font-black">
                            <span>Total Pesanan</span>
                            <span class="text-sm text-[var(--color-brand-700)] tabular-nums">
                                Rp{{ number_format($pOrd->total, 0, ',', '.') }}
                            </span>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center justify-end gap-2 pt-1">
                        <button type="button"
                                wire:click="rejectPendingOrder({{ $pOrd->id }})"
                                wire:confirm="Yakin ingin menolak pesanan ini?"
                                class="px-3 py-2 rounded-xl border border-red-200 text-red-600 hover:bg-red-50 text-xs font-bold transition flex items-center gap-1.5">
                            <i data-lucide="x" class="w-3.5 h-3.5"></i>
                            <span>Tolak</span>
                        </button>

                        <button type="button"
                                wire:click="loadPendingOrderIntoCart({{ $pOrd->id }})"
                                class="px-3 py-2 rounded-xl border border-blue-200 text-blue-700 hover:bg-blue-50 text-xs font-bold transition flex items-center gap-1.5">
                            <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                            <span>Muat ke Kasir</span>
                        </button>

                        <button type="button"
                                wire:click="acceptPendingOrder({{ $pOrd->id }})"
                                wire:loading.attr="disabled"
                                class="px-4 py-2 rounded-xl bg-[var(--color-brand-600)] hover:bg-[var(--color-brand-700)] text-white text-xs font-bold shadow transition flex items-center gap-1.5">
                            <i data-lucide="check" class="w-4 h-4"></i>
                            <span>Terima Pesanan</span>
                        </button>
                    </div>
                </div>
                @endforeach
                @endif
            </div>
        </div>
    </div>
    @endif

</div>
