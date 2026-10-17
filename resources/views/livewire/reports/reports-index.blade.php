<div class="h-full flex flex-col p-4 bg-[var(--color-bg)] overflow-hidden">
    
    <!-- Top Header & Period Filter -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-[var(--color-line)] shrink-0">
        <div>
            <h2 class="text-base font-bold text-[var(--color-ink)]">Laporan Penjualan & Keuangan</h2>
            <span class="text-xs text-[var(--color-ink-muted)]">Periode: {{ $periodLabel }} (KF-29 s.d. KF-31; KF-10)</span>
        </div>

        <!-- Action Exports & Period Selectors -->
        <div class="flex flex-wrap items-center gap-2">
            <!-- Export PDF -->
            <button type="button"
                    wire:click="exportPdf"
                    class="px-3 py-1.5 rounded-lg border border-red-200 bg-white hover:bg-red-50 text-red-600 font-bold text-xs flex items-center gap-1.5 shadow-2xs transition-colors">
                <i data-lucide="file-text" class="w-3.5 h-3.5"></i>
                <span>Unduh PDF</span>
            </button>

            <!-- Export CSV / Excel -->
            <button type="button"
                    wire:click="exportCsv"
                    class="px-3 py-1.5 rounded-lg border border-emerald-300 bg-white hover:bg-emerald-50 text-emerald-700 font-bold text-xs flex items-center gap-1.5 shadow-2xs transition-colors">
                <i data-lucide="table" class="w-3.5 h-3.5"></i>
                <span>Ekspor Excel/CSV</span>
            </button>

            <!-- Period Pills -->
            <div class="flex items-center gap-1 bg-white p-1 rounded-xl border border-[var(--color-line)] text-xs">
                @foreach (['today' => 'Hari Ini', 'yesterday' => 'Kemarin', 'week' => '7 Hari', 'month' => 'Bulan Ini'] as $val => $label)
                <button type="button"
                        wire:click="$set('period', '{{ $val }}')"
                        class="px-2.5 py-1 rounded-lg font-semibold transition-all {{ $period === $val ? 'bg-[var(--color-brand-600)] text-white shadow-2xs' : 'text-[var(--color-ink-muted)] hover:text-[var(--color-ink)]' }}">
                    {{ $label }}
                </button>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Sub-tab Navigation: Sales, Top Menu, Discounts, Partners -->
    <div class="py-2.5 flex items-center gap-1 shrink-0 text-xs border-b border-[var(--color-line)]">
        <button type="button"
                wire:click="$set('reportTab', 'sales')"
                class="px-3.5 py-1.5 rounded-lg font-bold transition-all {{ $reportTab === 'sales' ? 'bg-neutral-800 text-white' : 'text-[var(--color-ink-muted)] hover:text-[var(--color-ink)]' }}">
            Ringkasan Penjualan & Transaksi
        </button>

        <button type="button"
                wire:click="$set('reportTab', 'top_menu')"
                class="px-3.5 py-1.5 rounded-lg font-bold transition-all {{ $reportTab === 'top_menu' ? 'bg-neutral-800 text-white' : 'text-[var(--color-ink-muted)] hover:text-[var(--color-ink)]' }}">
            Menu Terlaris
        </button>

        <button type="button"
                wire:click="$set('reportTab', 'discounts')"
                class="px-3.5 py-1.5 rounded-lg font-bold transition-all {{ $reportTab === 'discounts' ? 'bg-neutral-800 text-white' : 'text-[var(--color-ink-muted)] hover:text-[var(--color-ink)]' }}">
            Rekapitulasi Diskon
        </button>

        <button type="button"
                wire:click="$set('reportTab', 'partners')"
                class="px-3.5 py-1.5 rounded-lg font-bold transition-all {{ $reportTab === 'partners' ? 'bg-neutral-800 text-white' : 'text-[var(--color-ink-muted)] hover:text-[var(--color-ink)]' }}">
            Bagi Hasil Mitra Titipan
        </button>
    </div>

    <!-- MAIN BODY AREA -->
    <div class="flex-1 overflow-y-auto pt-3 pb-6 space-y-4">

        <!-- TAB 1: SALES & TRANSACTIONS -->
        @if ($reportTab === 'sales')
        <!-- Metric Cards Grid -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 shrink-0">
            <!-- Penjualan Neto -->
            <div class="p-4 rounded-xl border border-[var(--color-brand-100)] bg-[var(--color-brand-50)]/70 shadow-2xs">
                <span class="text-[10px] font-bold text-[var(--color-brand-900)] uppercase tracking-wider block">Penjualan Neto (Bersih)</span>
                <div class="text-xl font-black text-[var(--color-brand-700)] tabular-nums mt-1">
                    Rp{{ number_format($metrics['net_sales'], 0, ',', '.') }}
                </div>
                <div class="text-[11px] text-[var(--color-ink-muted)] mt-0.5">
                    Dari {{ number_format($metrics['total_transactions']) }} transaksi lunas
                </div>
            </div>

            <!-- Penjualan Bruto & Diskon -->
            <div class="p-4 rounded-xl border border-[var(--color-line)] bg-white shadow-2xs">
                <span class="text-[10px] font-bold text-neutral-500 uppercase tracking-wider block">Penjualan Bruto & Diskon</span>
                <div class="text-lg font-extrabold text-[var(--color-ink)] tabular-nums mt-1">
                    Rp{{ number_format($metrics['gross_sales'], 0, ',', '.') }}
                </div>
                <div class="text-[11px] text-fuchsia-700 font-semibold mt-0.5">
                    Diskon: -Rp{{ number_format($metrics['discount_total'], 0, ',', '.') }}
                </div>
            </div>

            <!-- Kas Tunai vs QRIS -->
            <div class="p-4 rounded-xl border border-[var(--color-line)] bg-white shadow-2xs">
                <span class="text-[10px] font-bold text-neutral-500 uppercase tracking-wider block">Metode Pembayaran</span>
                <div class="flex items-center justify-between mt-1 text-xs font-bold">
                    <span class="text-emerald-700">Tunai:</span>
                    <span class="tabular-nums">Rp{{ number_format($metrics['cash_sales'], 0, ',', '.') }}</span>
                </div>
                <div class="flex items-center justify-between mt-0.5 text-xs font-bold">
                    <span class="text-blue-700">QRIS:</span>
                    <span class="tabular-nums">Rp{{ number_format($metrics['qris_sales'], 0, ',', '.') }}</span>
                </div>
            </div>

            <!-- Piutang (Open Bill) -->
            <div class="p-4 rounded-xl border border-amber-200 bg-amber-50 shadow-2xs">
                <span class="text-[10px] font-bold text-amber-900 uppercase tracking-wider block">Piutang Belum Lunas (Open Bill)</span>
                <div class="text-lg font-black text-amber-700 tabular-nums mt-1">
                    Rp{{ number_format($metrics['open_bill_total'], 0, ',', '.') }}
                </div>
                <div class="text-[11px] text-amber-800 mt-0.5">
                    Tagihan dosen & prodi aktif
                </div>
            </div>
        </div>

        <!-- Orders Table -->
        <div class="bg-[var(--color-surface)] border border-[var(--color-line)] rounded-xl overflow-hidden shadow-2xs">
            <div class="p-3 border-b border-[var(--color-line)] bg-[var(--color-bg)]/80 flex items-center justify-between">
                <span class="text-xs font-bold text-[var(--color-ink)]">Daftar Transaksi Pesanan</span>
                <span class="text-xs text-[var(--color-ink-muted)]">{{ $orders->count() }} nota terdata</span>
            </div>

            <table class="w-full text-left text-xs">
                <thead class="bg-[var(--color-bg)]/80 text-[var(--color-ink-muted)] border-b border-[var(--color-line)]">
                    <tr>
                        <th class="py-2.5 px-4 font-bold">No. Order</th>
                        <th class="py-2.5 px-4 font-bold">Waktu</th>
                        <th class="py-2.5 px-4 font-bold">Pelanggan / Meja</th>
                        <th class="py-2.5 px-4 font-bold text-center">Status</th>
                        <th class="py-2.5 px-4 font-bold text-center">Metode</th>
                        <th class="py-2.5 px-4 font-bold text-right">Subtotal</th>
                        <th class="py-2.5 px-4 font-bold text-right">Diskon</th>
                        <th class="py-2.5 px-4 font-bold text-right">Total Akhir</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[var(--color-line)]">
                    @forelse ($orders as $ord)
                    <tr class="hover:bg-[var(--color-brand-50)]/40 transition-colors">
                        <td class="py-2.5 px-4 font-bold text-[var(--color-ink)]">
                            {{ $ord->number }}
                        </td>
                        <td class="py-2.5 px-4 text-[var(--color-ink-muted)]">
                            {{ $ord->created_at->format('d/m/Y H:i') }}
                        </td>
                        <td class="py-2.5 px-4 font-medium text-[var(--color-ink)]">
                            {{ $ord->customer_name ?: 'Pelanggan Umum' }}
                            @if ($ord->table)
                            <span class="text-[10px] text-neutral-400">({{ $ord->table->label }})</span>
                            @endif
                        </td>
                        <td class="py-2.5 px-4 text-center">
                            @if ($ord->isVoid())
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-neutral-200 text-neutral-700">VOID</span>
                            @elseif ($ord->isPaid())
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">Lunas</span>
                            @else
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">Open Bill</span>
                            @endif
                        </td>
                        <td class="py-2.5 px-4 text-center font-bold uppercase text-[10px] text-[var(--color-ink-muted)]">
                            {{ $ord->payments->first()?->method->value ?? ($ord->isPaid() ? 'paid' : '-') }}
                        </td>
                        <td class="py-2.5 px-4 text-right tabular-nums text-[var(--color-ink-muted)]">
                            {{ $ord->formattedSubtotal() }}
                        </td>
                        <td class="py-2.5 px-4 text-right tabular-nums text-fuchsia-700 font-semibold">
                            -{{ $ord->formattedDiscountTotal() }}
                        </td>
                        <td class="py-2.5 px-4 text-right font-extrabold tabular-nums text-[var(--color-ink)]">
                            {{ $ord->formattedTotal() }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="py-8 text-center text-[var(--color-ink-muted)] text-xs">
                            Tidak ada transaksi pada periode ini.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @endif

        <!-- TAB 2: MENU TERLARIS (TOP SELLING) -->
        @if ($reportTab === 'top_menu')
        <div class="bg-[var(--color-surface)] border border-[var(--color-line)] rounded-xl overflow-hidden shadow-2xs">
            <div class="p-3 border-b border-[var(--color-line)] bg-[var(--color-bg)]/80 flex items-center justify-between">
                <span class="text-xs font-bold text-[var(--color-ink)]">Daftar Menu Paling Laku & Total Penjualan</span>
                <span class="text-xs text-[var(--color-ink-muted)]">Top 10 Menu</span>
            </div>

            <table class="w-full text-left text-xs">
                <thead class="bg-[var(--color-bg)]/80 text-[var(--color-ink-muted)] border-b border-[var(--color-line)]">
                    <tr>
                        <th class="py-2.5 px-4 font-bold text-center w-12">#</th>
                        <th class="py-2.5 px-4 font-bold">Nama Menu</th>
                        <th class="py-2.5 px-4 font-bold text-right">Jumlah Terjual</th>
                        <th class="py-2.5 px-4 font-bold text-right">Total Nilai Penjualan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[var(--color-line)]">
                    @forelse ($topItems as $index => $item)
                    <tr class="hover:bg-[var(--color-brand-50)]/40 transition-colors">
                        <td class="py-2.5 px-4 text-center font-bold text-[var(--color-ink-muted)]">
                            {{ $index + 1 }}
                        </td>
                        <td class="py-2.5 px-4 font-bold text-[var(--color-ink)]">
                            {{ $item->name }}
                        </td>
                        <td class="py-2.5 px-4 text-right font-black tabular-nums text-[var(--color-brand-700)]">
                            {{ number_format($item->total_qty) }} cup/porsi
                        </td>
                        <td class="py-2.5 px-4 text-right font-bold tabular-nums text-[var(--color-ink)]">
                            Rp{{ number_format($item->total_revenue, 0, ',', '.') }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="py-8 text-center text-[var(--color-ink-muted)] text-xs">
                            Belum ada data penjualan menu pada periode ini.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @endif

        <!-- TAB 3: DISCOUNTS BREAKDOWN -->
        @if ($reportTab === 'discounts')
        <div class="bg-[var(--color-surface)] border border-[var(--color-line)] rounded-xl overflow-hidden shadow-2xs">
            <div class="p-3 border-b border-[var(--color-line)] bg-[var(--color-bg)]/80 flex items-center justify-between">
                <span class="text-xs font-bold text-[var(--color-ink)]">Rekapitulasi Penggunaan Diskon & Promo</span>
                <span class="text-xs text-[var(--color-ink-muted)]">Analisis potongan promosi</span>
            </div>

            <table class="w-full text-left text-xs">
                <thead class="bg-[var(--color-bg)]/80 text-[var(--color-ink-muted)] border-b border-[var(--color-line)]">
                    <tr>
                        <th class="py-2.5 px-4 font-bold">Nama Diskon / Promo</th>
                        <th class="py-2.5 px-4 font-bold text-center">Tipe Diskon</th>
                        <th class="py-2.5 px-4 font-bold text-center">Frekuensi Digunakan</th>
                        <th class="py-2.5 px-4 font-bold text-right">Total Nilai Potongan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[var(--color-line)]">
                    @forelse ($discountsBreakdown as $d)
                    <tr class="hover:bg-[var(--color-brand-50)]/40 transition-colors">
                        <td class="py-2.5 px-4 font-bold text-[var(--color-ink)]">
                            {{ $d->discount?->name ?? 'Diskon Transaksi' }}
                        </td>
                        <td class="py-2.5 px-4 text-center text-[10px]">
                            <span class="px-2 py-0.5 rounded-full bg-fuchsia-100 text-fuchsia-800 font-bold">
                                {{ $d->discount?->kind->label() ?? 'Order' }}
                            </span>
                        </td>
                        <td class="py-2.5 px-4 text-center font-bold tabular-nums">
                            {{ number_format($d->total_applied) }} kali
                        </td>
                        <td class="py-2.5 px-4 text-right font-black tabular-nums text-fuchsia-700">
                            -Rp{{ number_format($d->total_cut, 0, ',', '.') }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="py-8 text-center text-[var(--color-ink-muted)] text-xs">
                            Tidak ada diskon diterapkan pada periode ini.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @endif

        <!-- TAB 4: BAGI HASIL MITRA TITIPAN (KF-10) -->
        @if ($reportTab === 'partners')
        <div class="bg-[var(--color-surface)] border border-[var(--color-line)] rounded-xl overflow-hidden shadow-2xs">
            <div class="p-3 border-b border-[var(--color-line)] bg-[var(--color-bg)]/80 flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold text-[var(--color-ink)]">Rekapitulasi Bagi Hasil Produk Titipan Mitra (KF-10)</span>
                    <p class="text-[11px] text-[var(--color-ink-muted)]">Pembagian omzet produk konsinyasi antara pemilik produk dan UMCO House</p>
                </div>
            </div>

            <table class="w-full text-left text-xs">
                <thead class="bg-[var(--color-bg)]/80 text-[var(--color-ink-muted)] border-b border-[var(--color-line)]">
                    <tr>
                        <th class="py-2.5 px-4 font-bold">Nama Mitra</th>
                        <th class="py-2.5 px-4 font-bold text-center">Porsi Mitra (%)</th>
                        <th class="py-2.5 px-4 font-bold text-right">Item Terjual</th>
                        <th class="py-2.5 px-4 font-bold text-right">Total Omzet Bruto</th>
                        <th class="py-2.5 px-4 font-bold text-right text-blue-700">Hak Mitra</th>
                        <th class="py-2.5 px-4 font-bold text-right text-emerald-700">Hak UMCO House</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[var(--color-line)]">
                    @forelse ($partnerSales as $p)
                    <tr class="hover:bg-[var(--color-brand-50)]/40 transition-colors">
                        <td class="py-2.5 px-4 font-bold text-[var(--color-ink)]">
                            {{ $p['partner_name'] }}
                        </td>
                        <td class="py-2.5 px-4 text-center font-bold">
                            {{ $p['share_percent'] }}%
                        </td>
                        <td class="py-2.5 px-4 text-right font-medium tabular-nums">
                            {{ number_format($p['total_sold']) }} porsi
                        </td>
                        <td class="py-2.5 px-4 text-right font-bold tabular-nums text-[var(--color-ink)]">
                            Rp{{ number_format($p['total_revenue'], 0, ',', '.') }}
                        </td>
                        <td class="py-2.5 px-4 text-right font-black tabular-nums text-blue-700">
                            Rp{{ number_format($p['partner_share'], 0, ',', '.') }}
                        </td>
                        <td class="py-2.5 px-4 text-right font-black tabular-nums text-emerald-700">
                            Rp{{ number_format($p['cafe_share'], 0, ',', '.') }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-[var(--color-ink-muted)] text-xs">
                            Belum ada penjualan produk titipan mitra pada periode ini.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @endif

    </div>

</div>
