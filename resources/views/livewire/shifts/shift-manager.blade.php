<div class="h-full flex flex-col p-4 bg-[var(--color-bg)] overflow-hidden">
    
    <!-- Top Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-[var(--color-line)] shrink-0">
        <div>
            <h2 class="text-base font-bold text-[var(--color-ink)]">Manajemen Shift & Saldo Kas</h2>
            <span class="text-xs text-[var(--color-ink-muted)]">Pencatatan modal kas awal, pengeluaran operasional, dan rekonsiliasi kas fisik (KF-23, KF-24)</span>
        </div>

        @if ($activeShift)
        <div class="flex items-center gap-2">
            <span class="text-xs px-3 py-1 rounded-full bg-emerald-100 text-emerald-800 font-bold flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span>Shift Aktif (Buka {{ $activeShift->opened_at->format('H:i') }})</span>
            </span>
        </div>
        @endif
    </div>

    <div class="flex-1 overflow-y-auto pt-3 pb-6 space-y-4">
        
        <!-- IF NO ACTIVE SHIFT: OPEN SHIFT PROMPT -->
        @if (! $activeShift)
        <div class="max-w-md mx-auto my-8 bg-white border border-[var(--color-line)] rounded-2xl p-6 shadow-sm text-center space-y-4">
            <div class="w-12 h-12 rounded-2xl bg-[var(--color-brand-50)] text-[var(--color-brand-600)] flex items-center justify-center mx-auto">
                <i data-lucide="clock" class="w-6 h-6"></i>
            </div>
            <div>
                <h3 class="text-base font-bold text-[var(--color-ink)]">Tidak Ada Shift Aktif</h3>
                <p class="text-xs text-[var(--color-ink-muted)] mt-1">Buka shift kasir terlebih dahulu dengan mengisi saldo modal awal di laci kasir.</p>
            </div>

            <div class="space-y-3 text-left text-xs pt-2">
                <div>
                    <label class="block font-semibold mb-1">Modal Awal Kas (Rupiah):</label>
                    <input type="number"
                           wire:model="openingCash"
                           placeholder="200000"
                           class="w-full h-11 px-3 text-base font-bold rounded-lg border border-[var(--color-line)] bg-white outline-none focus:border-[var(--color-brand-600)] tabular-nums">
                    @error('openingCash') <span class="text-red-600 text-[11px] mt-0.5 block">{{ $message }}</span> @enderror
                </div>
                <div class="flex items-center gap-2 pt-1">
                    @foreach ([100000, 200000, 300000, 500000] as $preset)
                    <button type="button"
                            wire:click="$set('openingCash', {{ $preset }})"
                            class="flex-1 py-1.5 rounded-md border border-[var(--color-line)] bg-white hover:bg-[var(--color-brand-50)] text-xs font-semibold tabular-nums text-[var(--color-ink)]">
                        {{ number_format($preset / 1000, 0) }}k
                    </button>
                    @endforeach
                </div>
            </div>

            <button type="button"
                    wire:click="openNewShift"
                    class="w-full h-11 rounded-lg bg-[var(--color-brand-600)] hover:bg-[var(--color-brand-700)] text-white text-xs font-bold shadow-sm flex items-center justify-center gap-2">
                <i data-lucide="play" class="w-4 h-4"></i>
                <span>Buka Shift Sekarang</span>
            </button>
        </div>
        @else

        <!-- ACTIVE SHIFT METRICS DASHBOARD -->
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-3">
            <div class="p-3.5 rounded-xl border border-[var(--color-line)] bg-white shadow-2xs">
                <span class="text-[10px] font-bold text-[var(--color-ink-muted)] uppercase tracking-wider block">Modal Awal Kas</span>
                <div class="text-lg font-black text-[var(--color-ink)] tabular-nums mt-1">
                    Rp{{ number_format($activeShift->opening_cash, 0, ',', '.') }}
                </div>
            </div>

            <div class="p-3.5 rounded-xl border border-[var(--color-line)] bg-white shadow-2xs">
                <span class="text-[10px] font-bold text-emerald-800 uppercase tracking-wider block">Penjualan Tunai</span>
                <div class="text-lg font-black text-emerald-700 tabular-nums mt-1">
                    Rp{{ number_format($metrics['cash_sales'], 0, ',', '.') }}
                </div>
            </div>

            <div class="p-3.5 rounded-xl border border-[var(--color-line)] bg-white shadow-2xs">
                <span class="text-[10px] font-bold text-blue-800 uppercase tracking-wider block">Penjualan QRIS</span>
                <div class="text-lg font-black text-blue-700 tabular-nums mt-1">
                    Rp{{ number_format($metrics['qris_sales'], 0, ',', '.') }}
                </div>
            </div>

            <div class="p-3.5 rounded-xl border border-[var(--color-line)] bg-white shadow-2xs">
                <span class="text-[10px] font-bold text-red-800 uppercase tracking-wider block">Pengeluaran Kas</span>
                <div class="text-lg font-black text-red-600 tabular-nums mt-1">
                    Rp{{ number_format($metrics['total_expenses'], 0, ',', '.') }}
                </div>
            </div>

            <div class="col-span-2 lg:col-span-1 p-3.5 rounded-xl border border-[var(--color-brand-100)] bg-[var(--color-brand-50)] shadow-2xs">
                <span class="text-[10px] font-bold text-[var(--color-brand-900)] uppercase tracking-wider block">Kas Diharapkan di Laci</span>
                <div class="text-lg font-black text-[var(--color-brand-700)] tabular-nums mt-1">
                    Rp{{ number_format($metrics['expected_cash'], 0, ',', '.') }}
                </div>
            </div>
        </div>

        <!-- Shift Actions -->
        <div class="flex items-center justify-between p-3.5 bg-white border border-[var(--color-line)] rounded-xl shadow-2xs">
            <div class="flex items-center gap-2">
                <button type="button"
                        wire:click="$set('showExpenseModal', true)"
                        class="h-10 px-3.5 rounded-lg border border-[var(--color-line)] bg-white hover:bg-[var(--color-brand-50)] text-xs font-bold text-[var(--color-ink)] flex items-center gap-1.5 transition-colors">
                    <i data-lucide="minus-circle" class="w-4 h-4 text-red-600"></i>
                    <span>Catat Pengeluaran Operasional</span>
                </button>
            </div>

            <button type="button"
                    wire:click="openCloseShiftModal"
                    class="h-10 px-4 rounded-lg bg-neutral-900 hover:bg-black text-white text-xs font-bold flex items-center gap-1.5 shadow-xs transition-colors">
                <i data-lucide="lock" class="w-4 h-4"></i>
                <span>Tutup Shift & Rekap Kas</span>
            </button>
        </div>

        <!-- Expenses List in this Shift -->
        <div class="bg-white border border-[var(--color-line)] rounded-xl overflow-hidden shadow-2xs">
            <div class="p-3 border-b border-[var(--color-line)] bg-[var(--color-bg)]/80 flex items-center justify-between">
                <span class="text-xs font-bold text-[var(--color-ink)]">Rincian Pengeluaran Operasional Shift Ini</span>
                <span class="text-xs text-[var(--color-ink-muted)]">{{ $activeExpenses->count() }} pengeluaran dicatat</span>
            </div>

            <table class="w-full text-left text-xs">
                <thead class="text-[var(--color-ink-muted)] border-b border-[var(--color-line)]">
                    <tr>
                        <th class="py-2.5 px-4 font-bold">Waktu</th>
                        <th class="py-2.5 px-4 font-bold">Kategori</th>
                        <th class="py-2.5 px-4 font-bold">Keperluan / Catatan</th>
                        <th class="py-2.5 px-4 font-bold text-right">Nominal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[var(--color-line)]">
                    @forelse ($activeExpenses as $exp)
                    <tr>
                        <td class="py-2.5 px-4 text-[var(--color-ink-muted)]">{{ $exp->created_at->format('H:i') }} WIB</td>
                        <td class="py-2.5 px-4 font-bold capitalize">{{ str_replace('_', ' ', $exp->category) }}</td>
                        <td class="py-2.5 px-4 text-neutral-700">{{ $exp->note ?: '-' }}</td>
                        <td class="py-2.5 px-4 text-right font-bold text-red-600 tabular-nums">
                            {{ $exp->formattedAmount() }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="py-6 text-center text-[var(--color-ink-muted)] text-xs">
                            Belum ada pengeluaran operasional pada shift ini.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @endif

        <!-- Recent Shifts History Table -->
        <div class="bg-white border border-[var(--color-line)] rounded-xl overflow-hidden shadow-2xs">
            <div class="p-3 border-b border-[var(--color-line)] bg-[var(--color-bg)]/80 flex items-center justify-between">
                <span class="text-xs font-bold text-[var(--color-ink)]">Riwayat Rekonsiliasi Shift Terakhir</span>
                <span class="text-xs text-[var(--color-ink-muted)]">Histori selisih kas fisik</span>
            </div>

            <table class="w-full text-left text-xs">
                <thead class="text-[var(--color-ink-muted)] border-b border-[var(--color-line)]">
                    <tr>
                        <th class="py-2.5 px-4 font-bold">Petugas</th>
                        <th class="py-2.5 px-4 font-bold">Waktu Buka / Tutup</th>
                        <th class="py-2.5 px-4 font-bold text-right">Modal Awal</th>
                        <th class="py-2.5 px-4 font-bold text-right">Kas Fisik</th>
                        <th class="py-2.5 px-4 font-bold text-center">Selisih Kas</th>
                        <th class="py-2.5 px-4 font-bold text-right">Kas Esok Hari</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[var(--color-line)]">
                    @forelse ($recentShifts as $sh)
                    <tr class="hover:bg-[var(--color-brand-50)]/40 transition-colors">
                        <td class="py-2.5 px-4 font-bold">{{ $sh->user?->name ?? 'Kasir' }}</td>
                        <td class="py-2.5 px-4 text-[var(--color-ink-muted)]">
                            <div>{{ $sh->opened_at->format('d/m/Y H:i') }}</div>
                            <div class="text-[10px]">{{ $sh->closed_at ? 's/d ' . $sh->closed_at->format('H:i') : '(Sedang Berjalan)' }}</div>
                        </td>
                        <td class="py-2.5 px-4 text-right tabular-nums">Rp{{ number_format($sh->opening_cash, 0, ',', '.') }}</td>
                        <td class="py-2.5 px-4 text-right font-bold tabular-nums">
                            {{ $sh->counted_cash !== null ? 'Rp' . number_format($sh->counted_cash, 0, ',', '.') : '-' }}
                        </td>
                        <td class="py-2.5 px-4 text-center">
                            @if ($sh->isOpen())
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-800">Aktif</span>
                            @elseif ($sh->difference === 0)
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">Kas Pas (0)</span>
                            @elseif ($sh->difference > 0)
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800">+Rp{{ number_format($sh->difference, 0, ',', '.') }}</span>
                            @else
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-800">Kurang Rp{{ number_format(abs($sh->difference), 0, ',', '.') }}</span>
                            @endif
                        </td>
                        <td class="py-2.5 px-4 text-right tabular-nums text-[var(--color-ink-muted)]">
                            {{ $sh->next_day_cash ? 'Rp' . number_format($sh->next_day_cash, 0, ',', '.') : '-' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-6 text-center text-[var(--color-ink-muted)] text-xs">Belum ada riwayat shift.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL 1: RECORD EXPENSE MODAL -->
    @if ($showExpenseModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs">
        <div class="bg-[var(--color-surface)] w-full max-w-sm rounded-2xl border border-[var(--color-line)] shadow-2xl p-5 space-y-4">
            <div class="flex items-center justify-between pb-2 border-b border-[var(--color-line)]">
                <h3 class="text-sm font-bold text-[var(--color-ink)]">Catat Pengeluaran Operasional</h3>
                <button type="button" wire:click="$set('showExpenseModal', false)" class="text-[var(--color-ink-muted)] hover:text-[var(--color-ink)]">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <div class="space-y-3 text-xs">
                <div>
                    <label class="block font-semibold mb-1">Kategori Pengeluaran:</label>
                    <select wire:model="expenseCategory" class="w-full h-9 px-2 text-xs rounded-lg border border-[var(--color-line)] bg-white outline-none focus:border-[var(--color-brand-600)]">
                        <option value="bahan_darurat">Pembelian Bahan Darurat (Es Batu / Susu dll)</option>
                        <option value="gas_air">Gas LPG & Galon Air Minum</option>
                        <option value="operasional">Operasional Kebersihan / Toko</option>
                        <option value="lain_lain">Lain-lain</option>
                    </select>
                </div>

                <div>
                    <label class="block font-semibold mb-1">Nominal (Rupiah):</label>
                    <input type="number"
                           wire:model="expenseAmount"
                           placeholder="25000"
                           class="w-full h-10 px-3 text-sm font-bold rounded-lg border border-[var(--color-line)] bg-white outline-none focus:border-[var(--color-brand-600)] tabular-nums">
                    @error('expenseAmount') <span class="text-red-600 text-[11px] mt-0.5 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block font-semibold mb-1">Catatan Keperluan (Wajib):</label>
                    <input type="text"
                           wire:model="expenseNote"
                           placeholder="Contoh: Beli 2 bungkus es batu kristal di warung"
                           class="w-full h-10 px-3 text-xs rounded-lg border border-[var(--color-line)] bg-white outline-none focus:border-[var(--color-brand-600)]">
                    @error('expenseNote') <span class="text-red-600 text-[11px] mt-0.5 block">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="pt-2 flex items-center justify-end gap-2">
                <button type="button" wire:click="$set('showExpenseModal', false)" class="px-3 py-2 text-xs font-semibold text-[var(--color-ink-muted)]">
                    Batal
                </button>
                <button type="button" wire:click="recordExpense" class="px-4 py-2 text-xs font-bold rounded-lg bg-red-600 hover:bg-red-700 text-white">
                    Simpan Pengeluaran
                </button>
            </div>
        </div>
    </div>
    @endif

    <!-- MODAL 2: CLOSE SHIFT RECONCILIATION MODAL -->
    @if ($showCloseModal && $activeShift)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs">
        <div class="bg-[var(--color-surface)] w-full max-w-md rounded-2xl border border-[var(--color-line)] shadow-2xl p-6 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-[var(--color-line)]">
                <div>
                    <h3 class="text-base font-bold text-[var(--color-ink)]">Tutup Shift & Rekonsiliasi Kas</h3>
                    <span class="text-xs text-[var(--color-ink-muted)]">Petugas: {{ auth()->user()->name }}</span>
                </div>
                <button type="button" wire:click="$set('showCloseModal', false)" class="text-[var(--color-ink-muted)] hover:text-[var(--color-ink)]">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Expected Cash Display -->
            <div class="p-3.5 rounded-xl bg-[var(--color-brand-50)] border border-[var(--color-brand-100)] flex items-center justify-between">
                <div>
                    <span class="text-xs font-semibold text-[var(--color-brand-900)]">Kas Diharapkan di Laci:</span>
                    <div class="text-[10px] text-[var(--color-brand-900)]/70">Modal + Penjualan Tunai − Pengeluaran</div>
                </div>
                <span class="text-xl font-black text-[var(--color-brand-700)] tabular-nums">
                    Rp{{ number_format($metrics['expected_cash'], 0, ',', '.') }}
                </span>
            </div>

            <div class="space-y-3 text-xs">
                <div>
                    <label class="block font-semibold mb-1">Kas Fisik Hasil Hitung Manual di Laci (Wajib):</label>
                    <input type="number"
                           wire:model.live.debounce.150ms="countedCash"
                           class="w-full h-11 px-3 text-lg font-bold text-right rounded-lg border border-[var(--color-line)] bg-white outline-none focus:border-[var(--color-brand-600)] tabular-nums">
                    @error('countedCash') <span class="text-red-600 text-[11px] mt-0.5 block">{{ $message }}</span> @enderror
                </div>

                <!-- Difference badge calculation -->
                @php
                    $diff = $countedCash - $metrics['expected_cash'];
                @endphp
                <div class="p-3 rounded-xl border flex items-center justify-between {{ $diff === 0 ? 'bg-emerald-50 border-emerald-200 text-emerald-900' : ($diff > 0 ? 'bg-blue-50 border-blue-200 text-blue-900' : 'bg-red-50 border-red-200 text-red-900') }}">
                    <span class="font-bold">Selisih Kas:</span>
                    <span class="font-extrabold text-sm tabular-nums">
                        @if ($diff === 0)
                        Rp0 (Kas Pas Cocok)
                        @elseif ($diff > 0)
                        +Rp{{ number_format($diff, 0, ',', '.') }} (Kas Lebih)
                        @else
                        -Rp{{ number_format(abs($diff), 0, ',', '.') }} (Kas Kurang)
                        @endif
                    </span>
                </div>

                <div>
                    <label class="block font-semibold mb-1">Kas yang Ditinggalkan untuk Shift Esok Hari (Next Day):</label>
                    <input type="number"
                           wire:model="nextDayCash"
                           class="w-full h-10 px-3 text-sm font-bold text-right rounded-lg border border-[var(--color-line)] bg-white outline-none focus:border-[var(--color-brand-600)] tabular-nums">
                    @error('nextDayCash') <span class="text-red-600 text-[11px] mt-0.5 block">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="pt-3 border-t border-[var(--color-line)] flex items-center justify-end gap-2">
                <button type="button" wire:click="$set('showCloseModal', false)" class="px-3 py-2 text-xs font-semibold text-[var(--color-ink-muted)]">
                    Batal
                </button>
                <button type="button" wire:click="closeCurrentShift" class="px-4 py-2 text-xs font-bold rounded-lg bg-neutral-900 hover:bg-black text-white shadow-xs">
                    Konfirmasi Tutup Shift
                </button>
            </div>
        </div>
    </div>
    @endif

</div>
