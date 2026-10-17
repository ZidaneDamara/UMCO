<div class="h-full flex flex-col p-4 bg-[var(--color-bg)] overflow-hidden">
    <!-- Header & Search -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-[var(--color-line)] shrink-0">
        <div>
            <h2 class="text-base font-bold text-[var(--color-ink)]">Daftar Menu & Ketersediaan</h2>
            <span class="text-xs text-[var(--color-ink-muted)]">Atur ketersediaan menu secara cepat (KF-04, KF-16)</span>
        </div>

        <div class="flex items-center gap-2">
            <div class="relative w-full sm:w-64">
                <input type="text"
                       wire:model.live.debounce.250ms="search"
                       placeholder="Cari nama menu..."
                       class="w-full h-9 pl-8 pr-3 text-xs rounded-lg border border-[var(--color-line)] bg-white outline-none focus:border-[var(--color-brand-600)]">
                <i data-lucide="search" class="w-3.5 h-3.5 text-[var(--color-ink-muted)] absolute left-2.5 top-2.5"></i>
            </div>
        </div>
    </div>

    <!-- Category Filter Tabs -->
    <div class="py-2.5 flex items-center gap-1.5 overflow-x-auto no-scrollbar shrink-0 text-xs">
        <button type="button"
                wire:click="$set('selectedCategoryId', null)"
                class="px-3 py-1.5 rounded-lg font-semibold shrink-0 transition-all {{ is_null($selectedCategoryId) ? 'bg-[var(--color-brand-600)] text-white' : 'bg-white border border-[var(--color-line)] text-[var(--color-ink)]' }}">
            Semua
        </button>
        @foreach ($categories as $cat)
        <button type="button"
                wire:click="$set('selectedCategoryId', {{ $cat->id }})"
                class="px-3 py-1.5 rounded-lg font-semibold shrink-0 transition-all {{ $selectedCategoryId === $cat->id ? 'bg-[var(--color-brand-600)] text-white' : 'bg-white border border-[var(--color-line)] text-[var(--color-ink)]' }}">
            {{ $cat->name }}
        </button>
        @endforeach
    </div>

    <!-- Items Table / Grid -->
    <div class="flex-1 overflow-y-auto pt-1 pb-4">
        <div class="bg-[var(--color-surface)] border border-[var(--color-line)] rounded-xl overflow-hidden shadow-2xs">
            <table class="w-full text-left text-xs">
                <thead class="bg-[var(--color-bg)]/80 text-[var(--color-ink-muted)] border-b border-[var(--color-line)]">
                    <tr>
                        <th class="py-3 px-4 font-bold">Nama Menu</th>
                        <th class="py-3 px-4 font-bold">Kategori</th>
                        <th class="py-3 px-4 font-bold">Stasiun</th>
                        <th class="py-3 px-4 font-bold">Harga</th>
                        <th class="py-3 px-4 font-bold text-center">Status</th>
                        <th class="py-3 px-4 font-bold text-right">Aksi Sakelar</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[var(--color-line)]">
                    @forelse ($items as $it)
                    <tr wire:key="menu-row-{{ $it->id }}" class="hover:bg-[var(--color-brand-50)]/40 transition-colors">
                        <td class="py-3 px-4 font-bold text-[var(--color-ink)]">
                            {{ $it->name }}
                        </td>
                        <td class="py-3 px-4 text-[var(--color-ink-muted)]">
                            {{ $it->category?->name ?? '-' }}
                        </td>
                        <td class="py-3 px-4">
                            <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full {{ $it->type->value === 'drink' ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800' }}">
                                {{ $it->type->value === 'drink' ? 'Bar (Minuman)' : 'Dapur (Makanan)' }}
                            </span>
                        </td>
                        <td class="py-3 px-4 font-extrabold text-[var(--color-brand-700)] tabular-nums">
                            {{ $it->formattedPrice() }}
                        </td>
                        <td class="py-3 px-4 text-center">
                            @if ($it->is_available)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                Tersedia
                            </span>
                            @else
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-800">
                                <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                Habis
                            </span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-right">
                            <button type="button"
                                    wire:click="toggleAvailability({{ $it->id }})"
                                    class="px-3 py-1.5 rounded-lg border text-xs font-bold transition-all {{ $it->is_available ? 'border-red-200 text-red-700 hover:bg-red-50' : 'border-emerald-200 text-emerald-700 hover:bg-emerald-50' }}">
                                {{ $it->is_available ? 'Tandai Habis' : 'Tandai Tersedia' }}
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-[var(--color-ink-muted)] text-xs">
                            Tidak ada menu ditemukan.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
