<div class="h-full flex flex-col p-4 bg-[var(--color-bg)] overflow-hidden">
    
    <!-- Top Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-[var(--color-line)] shrink-0">
        <div>
            <h2 class="text-base font-bold text-[var(--color-ink)]">Stok Bahan Baku & Inventori</h2>
            <span class="text-xs text-[var(--color-ink-muted)]">Stok otomatis, stok opname langsung tanpa Google Form, dan pencatatan waste (KF-25 s.d. KF-28)</span>
        </div>

        <div class="flex items-center gap-2">
            <!-- Record Waste Button (KF-27) -->
            <button type="button"
                    wire:click="openWasteModal"
                    class="px-3 py-1.5 rounded-lg border border-red-200 bg-white hover:bg-red-50 text-red-600 font-bold text-xs flex items-center gap-1.5 shadow-2xs transition-colors">
                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                <span>Catat Terbuang (Waste)</span>
            </button>

            <!-- Search box if on stock tab -->
            @if (in_array($mainTab, ['stock', 'opname']))
            <div class="relative w-full sm:w-56">
                <input type="text"
                       wire:model.live.debounce.250ms="search"
                       placeholder="Cari nama bahan..."
                       class="w-full h-8 pl-8 pr-3 text-xs rounded-lg border border-[var(--color-line)] bg-white outline-none focus:border-[var(--color-brand-600)]">
                <i data-lucide="search" class="w-3.5 h-3.5 text-[var(--color-ink-muted)] absolute left-2.5 top-2"></i>
            </div>
            @endif
        </div>
    </div>

    <!-- Main Navigation Tabs -->
    <div class="py-2.5 flex flex-wrap items-center justify-between gap-2 shrink-0 border-b border-[var(--color-line)]">
        <div class="flex items-center gap-1 bg-white p-1 rounded-xl border border-[var(--color-line)] text-xs">
            <button type="button"
                    wire:click="$set('mainTab', 'stock')"
                    class="px-3 py-1.5 rounded-lg font-semibold transition-all {{ $mainTab === 'stock' ? 'bg-[var(--color-brand-600)] text-white shadow-2xs' : 'text-[var(--color-ink-muted)] hover:text-[var(--color-ink)]' }}">
                Daftar Stok
            </button>

            <button type="button"
                    wire:click="$set('mainTab', 'need_buy')"
                    class="px-3 py-1.5 rounded-lg font-semibold transition-all flex items-center gap-1.5 {{ $mainTab === 'need_buy' ? 'bg-amber-600 text-white shadow-2xs' : 'text-[var(--color-ink-muted)] hover:text-[var(--color-ink)]' }}">
                <span>Perlu Dibeli</span>
                @if ($needBuyItems->count() > 0)
                <span class="px-1.5 py-0.2 rounded-full text-[10px] font-bold {{ $mainTab === 'need_buy' ? 'bg-white text-amber-700' : 'bg-amber-100 text-amber-800' }}">
                    {{ $needBuyItems->count() }}
                </span>
                @endif
            </button>

            <button type="button"
                    wire:click="$set('mainTab', 'opname')"
                    class="px-3 py-1.5 rounded-lg font-semibold transition-all flex items-center gap-1.5 {{ $mainTab === 'opname' ? 'bg-indigo-600 text-white shadow-2xs' : 'text-[var(--color-ink-muted)] hover:text-[var(--color-ink)]' }}">
                <i data-lucide="clipboard-check" class="w-3.5 h-3.5"></i>
                <span>Stok Opname</span>
            </button>

            <button type="button"
                    wire:click="$set('mainTab', 'movements')"
                    class="px-3 py-1.5 rounded-lg font-semibold transition-all {{ $mainTab === 'movements' ? 'bg-neutral-800 text-white shadow-2xs' : 'text-[var(--color-ink-muted)] hover:text-[var(--color-ink)]' }}">
                Riwayat Mutasi
            </button>
        </div>

        @if (in_array($mainTab, ['stock', 'opname']))
        <!-- Station Filter: All / Bar / Kitchen -->
        <div class="flex items-center gap-1 text-xs">
            <span class="text-[11px] font-semibold text-[var(--color-ink-muted)] mr-1">Filter Kategori:</span>
            <button type="button"
                    wire:click="$set('categoryFilter', 'all')"
                    class="px-2.5 py-1 rounded-lg font-medium border transition-colors {{ $categoryFilter === 'all' ? 'bg-neutral-800 text-white border-neutral-800' : 'bg-white text-[var(--color-ink-muted)] border-[var(--color-line)]' }}">
                Semua
            </button>
            <button type="button"
                    wire:click="$set('categoryFilter', 'bar')"
                    class="px-2.5 py-1 rounded-lg font-medium border transition-colors {{ $categoryFilter === 'bar' ? 'bg-amber-600 text-white border-amber-600' : 'bg-white text-[var(--color-ink-muted)] border-[var(--color-line)]' }}">
                Bar (Minuman)
            </button>
            <button type="button"
                    wire:click="$set('categoryFilter', 'kitchen')"
                    class="px-2.5 py-1 rounded-lg font-medium border transition-colors {{ $categoryFilter === 'kitchen' ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-white text-[var(--color-ink-muted)] border-[var(--color-line)]' }}">
                Dapur (Makanan)
            </button>
        </div>
        @endif
    </div>

    <!-- MAIN BODY AREA -->
    <div class="flex-1 overflow-y-auto pt-3 pb-6">

        <!-- TAB 1: DAFTAR STOK UTAMA -->
        @if ($mainTab === 'stock')
        <div class="bg-[var(--color-surface)] border border-[var(--color-line)] rounded-xl overflow-hidden shadow-2xs">
            <table class="w-full text-left text-xs">
                <thead class="bg-[var(--color-bg)]/80 text-[var(--color-ink-muted)] border-b border-[var(--color-line)]">
                    <tr>
                        <th class="py-3 px-4 font-bold">Nama Bahan</th>
                        <th class="py-3 px-4 font-bold">Kategori</th>
                        <th class="py-3 px-4 font-bold">Stok Saat Ini</th>
                        <th class="py-3 px-4 font-bold">Batas Minimum</th>
                        <th class="py-3 px-4 font-bold text-center">Status</th>
                        <th class="py-3 px-4 font-bold text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[var(--color-line)]">
                    @forelse ($ingredients as $ing)
                    @php
                        $isLow = $ing->stock <= $ing->min_stock && $ing->stock > 0;
                        $isOut = $ing->stock <= 0;
                    @endphp
                    <tr wire:key="ing-row-{{ $ing->id }}" class="hover:bg-[var(--color-brand-50)]/40 transition-colors">
                        <td class="py-3 px-4 font-bold text-[var(--color-ink)]">
                            {{ $ing->name }}
                        </td>
                        <td class="py-3 px-4">
                            <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full {{ $ing->category === 'bar' ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800' }}">
                                {{ $ing->category === 'bar' ? 'Bar' : 'Dapur' }}
                            </span>
                        </td>
                        <td class="py-3 px-4 font-extrabold tabular-nums {{ $isOut ? 'text-red-600' : ($isLow ? 'text-amber-600' : 'text-[var(--color-brand-700)]') }}">
                            {{ number_format($ing->stock, 0, ',', '.') }} {{ $ing->unit }}
                        </td>
                        <td class="py-3 px-4 text-[var(--color-ink-muted)] tabular-nums">
                            {{ number_format($ing->min_stock, 0, ',', '.') }} {{ $ing->unit }}
                        </td>
                        <td class="py-3 px-4 text-center">
                            @if ($isOut)
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-800">
                                Habis (0)
                            </span>
                            @elseif ($isLow)
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">
                                Menipis
                            </span>
                            @else
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                Aman
                            </span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-right">
                            <div class="flex items-center justify-end gap-1.5">
                                <button type="button"
                                        wire:click="openWasteModal({{ $ing->id }})"
                                        class="px-2 py-1 rounded-lg border border-red-200 bg-white hover:bg-red-50 text-[11px] font-semibold text-red-600 transition-colors"
                                        title="Catat bahan terbuang/tumpah">
                                    Waste
                                </button>
                                <button type="button"
                                        wire:click="openAdjustModal({{ $ing->id }})"
                                        class="px-2.5 py-1 rounded-lg border border-[var(--color-line)] bg-white hover:bg-[var(--color-brand-50)] text-[11px] font-semibold text-[var(--color-brand-700)] transition-colors">
                                    Sesuaikan
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-[var(--color-ink-muted)] text-xs">
                            Tidak ada bahan baku ditemukan.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @endif

        <!-- TAB 2: DAFTAR PERLU DIBELI (SHOPPING LIST) (KF-28) -->
        @if ($mainTab === 'need_buy')
        <div class="space-y-4">
            <div class="p-4 rounded-xl bg-amber-50 border border-amber-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <span class="w-10 h-10 rounded-xl bg-amber-200 text-amber-800 flex items-center justify-center shrink-0">
                        <i data-lucide="shopping-cart" class="w-5 h-5"></i>
                    </span>
                    <div>
                        <h3 class="text-sm font-bold text-amber-900">Daftar Bahan Perlu Dibeli (Helper & Barista)</h3>
                        <p class="text-xs text-amber-800 mt-0.5">Bahan baku yang stoknya di bawah batas minimum atau habis. Siap disalin atau dicetak.</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button type="button"
                            onclick="navigator.clipboard.writeText(document.getElementById('shopping-list-text').innerText); alert('Daftar belanja berhasil disalin ke clipboard!');"
                            class="px-3.5 py-2 rounded-lg bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs flex items-center gap-1.5 shadow-2xs">
                        <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                        <span>Salin Daftar Belanja</span>
                    </button>
                    <button type="button"
                            onclick="window.print()"
                            class="px-3 py-2 rounded-lg border border-amber-300 bg-white hover:bg-amber-100 text-amber-900 font-bold text-xs flex items-center gap-1.5">
                        <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                        <span>Cetak</span>
                    </button>
                </div>
            </div>

            <div id="shopping-list-text" class="bg-[var(--color-surface)] border border-[var(--color-line)] rounded-xl overflow-hidden shadow-2xs">
                <table class="w-full text-left text-xs">
                    <thead class="bg-[var(--color-bg)]/80 text-[var(--color-ink-muted)] border-b border-[var(--color-line)]">
                        <tr>
                            <th class="py-3 px-4 font-bold">Nama Bahan</th>
                            <th class="py-3 px-4 font-bold">Kategori</th>
                            <th class="py-3 px-4 font-bold text-right">Sisa Stok</th>
                            <th class="py-3 px-4 font-bold text-right">Batas Min</th>
                            <th class="py-3 px-4 font-bold text-right text-amber-700">Rekomendasi Beli</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--color-line)]">
                        @forelse ($needBuyItems as $item)
                        @php
                            $recBuy = max(1, ($item->min_stock * 2) - max(0, $item->stock));
                        @endphp
                        <tr class="hover:bg-[var(--color-brand-50)]/40">
                            <td class="py-3 px-4 font-bold text-[var(--color-ink)]">
                                {{ $item->name }}
                            </td>
                            <td class="py-3 px-4">
                                <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full {{ $item->category === 'bar' ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800' }}">
                                    {{ $item->category === 'bar' ? 'Bar' : 'Dapur' }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-right font-extrabold tabular-nums {{ $item->stock <= 0 ? 'text-red-600' : 'text-amber-600' }}">
                                {{ number_format($item->stock, 0, ',', '.') }} {{ $item->unit }}
                            </td>
                            <td class="py-3 px-4 text-right tabular-nums text-[var(--color-ink-muted)]">
                                {{ number_format($item->min_stock, 0, ',', '.') }} {{ $item->unit }}
                            </td>
                            <td class="py-3 px-4 text-right font-extrabold text-amber-800 tabular-nums">
                                +{{ number_format($recBuy, 0, ',', '.') }} {{ $item->unit }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-emerald-700 font-semibold text-xs">
                                ✓ Semua stok bahan dalam kondisi aman di atas batas minimum.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <!-- TAB 3: STOK OPNAME MASSAL (KF-25, KF-26) -->
        @if ($mainTab === 'opname')
        <div class="space-y-4">
            <div class="p-3.5 rounded-xl bg-indigo-50 border border-indigo-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <span class="w-9 h-9 rounded-xl bg-indigo-200 text-indigo-800 flex items-center justify-center shrink-0">
                        <i data-lucide="clipboard-check" class="w-5 h-5"></i>
                    </span>
                    <div>
                        <h3 class="text-sm font-bold text-indigo-900">Stok Opname Langsung di Sistem (Tanpa Google Form)</h3>
                        <p class="text-xs text-indigo-800 mt-0.5">Isi jumlah fisik di lapangan. Selisih dihitung seketika dan langsung direkonsiliasi.</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button type="button"
                            wire:click="$set('showOpnameConfirmModal', true)"
                            class="px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs flex items-center gap-1.5 shadow-xs">
                        <i data-lucide="save" class="w-3.5 h-3.5"></i>
                        <span>Simpan Hasil Opname</span>
                    </button>
                </div>
            </div>

            <div class="bg-[var(--color-surface)] border border-[var(--color-line)] rounded-xl overflow-hidden shadow-2xs">
                <table class="w-full text-left text-xs">
                    <thead class="bg-[var(--color-bg)]/80 text-[var(--color-ink-muted)] border-b border-[var(--color-line)]">
                        <tr>
                            <th class="py-3 px-4 font-bold">Nama Bahan</th>
                            <th class="py-3 px-4 font-bold">Kategori</th>
                            <th class="py-3 px-4 font-bold text-right">Stok Sistem</th>
                            <th class="py-3 px-4 font-bold text-center w-36">Hitungan Fisik</th>
                            <th class="py-3 px-4 font-bold text-right">Selisih Fisik</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--color-line)]">
                        @forelse ($ingredients as $ing)
                        @php
                            $counted = (float) ($countedQuantities[$ing->id] ?? $ing->stock);
                            $diff = $counted - (float) $ing->stock;
                        @endphp
                        <tr wire:key="opname-row-{{ $ing->id }}" class="hover:bg-indigo-50/30 transition-colors">
                            <td class="py-2.5 px-4 font-bold text-[var(--color-ink)]">
                                {{ $ing->name }}
                            </td>
                            <td class="py-2.5 px-4">
                                <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full {{ $ing->category === 'bar' ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800' }}">
                                    {{ $ing->category === 'bar' ? 'Bar' : 'Dapur' }}
                                </span>
                            </td>
                            <td class="py-2.5 px-4 text-right font-medium text-[var(--color-ink-muted)] tabular-nums">
                                {{ number_format($ing->stock, 0, ',', '.') }} {{ $ing->unit }}
                            </td>
                            <td class="py-2.5 px-4 text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <input type="number"
                                           step="any"
                                           wire:model.live.debounce.150ms="countedQuantities.{{ $ing->id }}"
                                           class="w-24 h-8 px-2 text-right font-bold text-xs rounded-md border border-[var(--color-line)] bg-white outline-none focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 tabular-nums">
                                    <span class="text-[10px] text-[var(--color-ink-muted)] font-semibold">{{ $ing->unit }}</span>
                                </div>
                            </td>
                            <td class="py-2.5 px-4 text-right font-extrabold tabular-nums">
                                @if (abs($diff) < 0.001)
                                <span class="text-emerald-600">0 (Cocok)</span>
                                @elseif ($diff > 0)
                                <span class="text-blue-600">+{{ number_format($diff, 0, ',', '.') }} {{ $ing->unit }} (Lebih)</span>
                                @else
                                <span class="text-red-600">{{ number_format($diff, 0, ',', '.') }} {{ $ing->unit }} (Kurang)</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-[var(--color-ink-muted)] text-xs">
                                Tidak ada data bahan.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <!-- TAB 4: RIWAYAT MUTASI (KF-35, KF-37) -->
        @if ($mainTab === 'movements')
        <div class="bg-[var(--color-surface)] border border-[var(--color-line)] rounded-xl overflow-hidden shadow-2xs">
            <div class="p-3 border-b border-[var(--color-line)] bg-[var(--color-bg)]/80 flex items-center justify-between">
                <span class="text-xs font-bold text-[var(--color-ink)]">Riwayat Transaksi Mutasi Bahan Baku Terakhir</span>
                <span class="text-xs text-[var(--color-ink-muted)]">{{ $recentMovements->count() }} mutasi tercatat</span>
            </div>

            <table class="w-full text-left text-xs">
                <thead class="bg-[var(--color-bg)]/80 text-[var(--color-ink-muted)] border-b border-[var(--color-line)]">
                    <tr>
                        <th class="py-3 px-4 font-bold">Waktu</th>
                        <th class="py-3 px-4 font-bold">Nama Bahan</th>
                        <th class="py-3 px-4 font-bold text-center">Tipe Mutasi</th>
                        <th class="py-3 px-4 font-bold text-right">Perubahan</th>
                        <th class="py-3 px-4 font-bold text-right">Stok Akhir</th>
                        <th class="py-3 px-4 font-bold">Keterangan</th>
                        <th class="py-3 px-4 font-bold">Petugas</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[var(--color-line)]">
                    @forelse ($recentMovements as $mov)
                    <tr class="hover:bg-[var(--color-brand-50)]/40 transition-colors">
                        <td class="py-2.5 px-4 text-[var(--color-ink-muted)]">
                            {{ $mov->created_at->format('d/m/Y H:i') }}
                        </td>
                        <td class="py-2.5 px-4 font-bold text-[var(--color-ink)]">
                            {{ $mov->ingredient?->name ?? '-' }}
                        </td>
                        <td class="py-2.5 px-4 text-center">
                            @php
                                $badgeStyle = match ($mov->type) {
                                    \App\Enums\StockMovementType::Sale => 'bg-neutral-100 text-neutral-800',
                                    \App\Enums\StockMovementType::Purchase => 'bg-emerald-100 text-emerald-800',
                                    \App\Enums\StockMovementType::Waste => 'bg-red-100 text-red-800',
                                    \App\Enums\StockMovementType::Opname => 'bg-indigo-100 text-indigo-800',
                                    \App\Enums\StockMovementType::VoidReturn => 'bg-blue-100 text-blue-800',
                                    default => 'bg-neutral-100 text-neutral-800',
                                };
                            @endphp
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ $badgeStyle }}">
                                {{ $mov->type->label() }}
                            </span>
                        </td>
                        <td class="py-2.5 px-4 text-right font-extrabold tabular-nums {{ $mov->qty_change >= 0 ? 'text-emerald-700' : 'text-red-600' }}">
                            {{ $mov->qty_change > 0 ? '+' : '' }}{{ number_format($mov->qty_change, 0, ',', '.') }} {{ $mov->ingredient?->unit }}
                        </td>
                        <td class="py-2.5 px-4 text-right font-bold text-[var(--color-ink)] tabular-nums">
                            {{ number_format($mov->qty_after, 0, ',', '.') }}
                        </td>
                        <td class="py-2.5 px-4 text-[var(--color-ink-muted)] max-w-xs truncate">
                            {{ $mov->note ?: '-' }}
                        </td>
                        <td class="py-2.5 px-4 font-medium text-[var(--color-ink)]">
                            {{ $mov->user?->name ?? 'Sistem' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center text-[var(--color-ink-muted)] text-xs">
                            Belum ada riwayat pergerakan stok.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @endif

    </div>

    <!-- MODAL 1: WASTE RECORDING MODAL (KF-27) -->
    @if ($showWasteModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs">
        <div class="bg-[var(--color-surface)] w-full max-w-sm rounded-xl border border-[var(--color-line)] shadow-xl p-5 space-y-4">
            <div class="flex items-center justify-between pb-2 border-b border-[var(--color-line)]">
                <div>
                    <h3 class="text-sm font-bold text-red-700 flex items-center gap-1.5">
                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                        <span>Catat Bahan Terbuang / Basi</span>
                    </h3>
                    <span class="text-xs text-[var(--color-ink-muted)]">Pengurangan stok akibat kerusakan/tumpah</span>
                </div>
                <button type="button" wire:click="$set('showWasteModal', false)" class="text-[var(--color-ink-muted)] hover:text-[var(--color-ink)]">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <div class="space-y-3 text-xs">
                <div>
                    <label class="block font-semibold mb-1">Pilih Bahan Baku:</label>
                    <select wire:model="wasteIngredientId" class="w-full h-10 px-3 rounded-lg border border-[var(--color-line)] bg-white text-xs outline-none focus:border-red-600">
                        @foreach (\App\Models\Ingredient::where('is_active', true)->orderBy('name')->get() as $item)
                        <option value="{{ $item->id }}">{{ $item->name }} (Sisa: {{ $item->stock }} {{ $item->unit }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block font-semibold mb-1">Jumlah Terbuang:</label>
                    <input type="number"
                           step="any"
                           wire:model="wasteQty"
                           placeholder="Contoh: 250"
                           class="w-full h-10 px-3 rounded-lg border border-[var(--color-line)] bg-white text-xs outline-none focus:border-red-600 font-bold tabular-nums">
                    @error('wasteQty') <span class="text-red-600 text-[11px] mt-0.5 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block font-semibold mb-1">Alasan Terbuang (Wajib):</label>
                    <select wire:model="wasteReason" class="w-full h-10 px-3 rounded-lg border border-[var(--color-line)] bg-white text-xs outline-none focus:border-red-600">
                        <option value="Basi / Kedaluwarsa">Basi / Kedaluwarsa</option>
                        <option value="Tumpah / Terjatuh">Tumpah / Terjatuh</option>
                        <option value="Kemasan Bocor / Rusak">Kemasan Bocor / Rusak</option>
                        <option value="Salah Racik / Gagal Buat">Salah Racik / Gagal Buat</option>
                        <option value="Lainnya">Lainnya</option>
                    </select>
                </div>

                <div>
                    <label class="block font-semibold mb-1">Catatan Tambahan (Opsional):</label>
                    <input type="text"
                           wire:model="wasteNote"
                           placeholder="Keterangan singkat kejadian..."
                           class="w-full h-10 px-3 rounded-lg border border-[var(--color-line)] bg-white text-xs outline-none focus:border-red-600">
                </div>
            </div>

            <div class="pt-2 flex items-center justify-end gap-2">
                <button type="button" wire:click="$set('showWasteModal', false)" class="px-3 py-2 text-xs font-semibold text-[var(--color-ink-muted)]">
                    Batal
                </button>
                <button type="button" wire:click="recordWaste" class="px-4 py-2 text-xs font-bold rounded-lg bg-red-600 hover:bg-red-700 text-white shadow-xs">
                    Simpan Waste
                </button>
            </div>
        </div>
    </div>
    @endif

    <!-- MODAL 2: CONFIRM OPNAME SAVE -->
    @if ($showOpnameConfirmModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs">
        <div class="bg-[var(--color-surface)] w-full max-w-sm rounded-xl border border-[var(--color-line)] shadow-xl p-5 space-y-4">
            <div class="flex items-center justify-between pb-2 border-b border-[var(--color-line)]">
                <div>
                    <h3 class="text-sm font-bold text-indigo-900">Konfirmasi Stok Opname</h3>
                    <span class="text-xs text-[var(--color-ink-muted)]">Rekonsiliasi seluruh stok fisik</span>
                </div>
                <button type="button" wire:click="$set('showOpnameConfirmModal', false)" class="text-[var(--color-ink-muted)] hover:text-[var(--color-ink)]">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <div class="space-y-3 text-xs">
                <p class="text-[var(--color-ink-muted)]">
                    Sistem akan menyesuaikan saldo stok bahan baku berdasarkan hitungan fisik yang Anda masukkan, dan mencatat selisih ke riwayat mutasi stok opname.
                </p>

                <div>
                    <label class="block font-semibold mb-1">Catatan Sesi Opname:</label>
                    <input type="text"
                           wire:model="opnameNote"
                           placeholder="Contoh: Stok opname mingguan shift pagi"
                           class="w-full h-10 px-3 rounded-lg border border-[var(--color-line)] bg-white text-xs outline-none focus:border-indigo-600">
                </div>
            </div>

            <div class="pt-2 flex items-center justify-end gap-2">
                <button type="button" wire:click="$set('showOpnameConfirmModal', false)" class="px-3 py-2 text-xs font-semibold text-[var(--color-ink-muted)]">
                    Periksa Kembali
                </button>
                <button type="button" wire:click="saveOpname" class="px-4 py-2 text-xs font-bold rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white shadow-xs">
                    Ya, Simpan & Terapkan
                </button>
            </div>
        </div>
    </div>
    @endif

    <!-- MODAL 3: QUICK ADJUSTMENT MODAL -->
    @if ($showAdjustModal && $selectedIngredient)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs">
        <div class="bg-[var(--color-surface)] w-full max-w-sm rounded-xl border border-[var(--color-line)] shadow-xl p-5 space-y-4">
            <div class="flex items-center justify-between pb-2 border-b border-[var(--color-line)]">
                <div>
                    <h3 class="text-sm font-bold text-[var(--color-ink)]">Sesuaikan Stok Bahan</h3>
                    <span class="text-xs text-[var(--color-ink-muted)]">{{ $selectedIngredient->name }}</span>
                </div>
                <button type="button" wire:click="$set('showAdjustModal', false)" class="text-[var(--color-ink-muted)] hover:text-[var(--color-ink)]">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <div class="space-y-3 text-xs">
                <div class="p-2.5 rounded-lg bg-[var(--color-bg)] flex justify-between items-center">
                    <span class="text-[var(--color-ink-muted)]">Stok saat ini:</span>
                    <span class="font-bold text-[var(--color-ink)]">{{ number_format($selectedIngredient->stock, 0) }} {{ $selectedIngredient->unit }}</span>
                </div>

                <div>
                    <label class="block font-semibold mb-1">
                        Perubahan Jumlah (+/- dalam {{ $selectedIngredient->unit }}):
                    </label>
                    <input type="number"
                           step="any"
                           wire:model="adjustAmount"
                           placeholder="Contoh: 1000 untuk tambah, -200 untuk kurangi"
                           class="w-full h-10 px-3 rounded-lg border border-[var(--color-line)] bg-white text-xs outline-none focus:border-[var(--color-brand-600)]">
                    @error('adjustAmount') <span class="text-red-600 text-[11px] mt-0.5 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block font-semibold mb-1">Alasan Penyesuaian (Wajib):</label>
                    <input type="text"
                           wire:model="adjustReason"
                           placeholder="Contoh: Pembelian baru / Koreksi fisik"
                           class="w-full h-10 px-3 rounded-lg border border-[var(--color-line)] bg-white text-xs outline-none focus:border-[var(--color-brand-600)]">
                    @error('adjustReason') <span class="text-red-600 text-[11px] mt-0.5 block">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="pt-2 flex items-center justify-end gap-2">
                <button type="button" wire:click="$set('showAdjustModal', false)" class="px-3 py-2 text-xs font-semibold text-[var(--color-ink-muted)]">
                    Batal
                </button>
                <button type="button" wire:click="saveAdjustment" class="px-4 py-2 text-xs font-bold rounded-lg bg-[var(--color-brand-600)] hover:bg-[var(--color-brand-700)] text-white">
                    Simpan Perubahan
                </button>
            </div>
        </div>
    </div>
    @endif

</div>
