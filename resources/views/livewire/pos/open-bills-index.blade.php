<div class="h-full flex flex-col p-4 bg-[var(--color-bg)] overflow-hidden">
    
    <!-- Top Header & Outstanding Piutang Banner -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-[var(--color-line)] shrink-0">
        <div>
            <h2 class="text-base font-bold text-[var(--color-ink)]">Manajemen Open Bill & Piutang</h2>
            <span class="text-xs text-[var(--color-ink-muted)]">Pengelolaan tagihan instansi, dosen, dan rapat kampus (KF-20, KF-21)</span>
        </div>

        <!-- Outstanding Balance Badge -->
        <div class="px-4 py-2 rounded-xl bg-amber-50 border border-amber-200 flex items-center gap-3">
            <span class="text-xs font-semibold text-amber-800">Total Piutang Belum Lunas:</span>
            <span class="text-base font-black text-amber-700 tabular-nums">
                Rp{{ number_format($totalUnpaidAmount, 0, ',', '.') }}
            </span>
        </div>
    </div>

    <!-- Toolbar: Tabs & Search -->
    <div class="py-3 flex flex-col sm:flex-row sm:items-center justify-between gap-2 shrink-0">
        <!-- Tabs -->
        <div class="flex items-center gap-1 bg-white p-1 rounded-xl border border-[var(--color-line)] text-xs">
            <button type="button"
                    wire:click="$set('tab', 'unpaid')"
                    class="px-3.5 py-1.5 rounded-lg font-semibold transition-all {{ $tab === 'unpaid' ? 'bg-amber-600 text-white shadow-2xs' : 'text-[var(--color-ink-muted)] hover:text-[var(--color-ink)]' }}">
                Belum Lunas (Aktif)
            </button>
            <button type="button"
                    wire:click="$set('tab', 'settled')"
                    class="px-3.5 py-1.5 rounded-lg font-semibold transition-all {{ $tab === 'settled' ? 'bg-[var(--color-brand-600)] text-white shadow-2xs' : 'text-[var(--color-ink-muted)] hover:text-[var(--color-ink)]' }}">
                Riwayat Sudah Lunas
            </button>
        </div>

        <!-- Search Bar -->
        <div class="relative w-full sm:w-72">
            <input type="text"
                   wire:model.live.debounce.250ms="search"
                   placeholder="Cari nama pemesan, nota, keperluan..."
                   class="w-full h-9 pl-8 pr-3 text-xs rounded-lg border border-[var(--color-line)] bg-white outline-none focus:border-[var(--color-brand-600)]">
            <i data-lucide="search" class="w-3.5 h-3.5 text-[var(--color-ink-muted)] absolute left-2.5 top-2.5"></i>
        </div>
    </div>

    <!-- Bills Table / Cards -->
    <div class="flex-1 overflow-y-auto pb-4">
        <div class="bg-[var(--color-surface)] border border-[var(--color-line)] rounded-xl overflow-hidden shadow-2xs">
            <table class="w-full text-left text-xs">
                <thead class="bg-[var(--color-bg)]/80 text-[var(--color-ink-muted)] border-b border-[var(--color-line)]">
                    <tr>
                        <th class="py-3 px-4 font-bold">No. Order</th>
                        <th class="py-3 px-4 font-bold">Pemesan / Instansi</th>
                        <th class="py-3 px-4 font-bold">Waktu & Umur Nota</th>
                        <th class="py-3 px-4 font-bold">Keperluan / Catatan</th>
                        <th class="py-3 px-4 font-bold text-right">Total Tagihan</th>
                        <th class="py-3 px-4 font-bold text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[var(--color-line)]">
                    @forelse ($orders as $ord)
                    <tr wire:key="open-bill-{{ $ord->id }}" class="hover:bg-[var(--color-brand-50)]/40 transition-colors">
                        <td class="py-3 px-4 font-bold text-[var(--color-ink)]">
                            {{ $ord->number }}
                        </td>
                        <td class="py-3 px-4 font-bold text-neutral-800">
                            {{ $ord->customer_name }}
                        </td>
                        <td class="py-3 px-4">
                            <div class="font-medium text-[var(--color-ink)]">{{ $ord->created_at->format('d/m/Y H:i') }}</div>
                            <div class="text-[10px] text-amber-700 font-semibold mt-0.5">{{ $ord->created_at->diffForHumans() }}</div>
                        </td>
                        <td class="py-3 px-4 text-[var(--color-ink-muted)] max-w-xs">
                            <span class="line-clamp-2">{{ $ord->open_bill_note ?: '-' }}</span>
                        </td>
                        <td class="py-3 px-4 text-right font-extrabold text-[var(--color-ink)] tabular-nums text-sm">
                            {{ $ord->formattedTotal() }}
                        </td>
                        <td class="py-3 px-4 text-right">
                            <div class="flex items-center justify-end gap-1.5">
                                <!-- Print Bill Button (KF-21) -->
                                <button type="button"
                                        wire:click="openPrintBillModal({{ $ord->id }})"
                                        class="px-2.5 py-1.5 rounded-lg border border-[var(--color-line)] bg-white hover:bg-[var(--color-brand-50)] text-xs font-semibold text-[var(--color-ink)] flex items-center gap-1 transition-colors">
                                    <i data-lucide="printer" class="w-3.5 h-3.5 text-neutral-500"></i>
                                    <span>Cetak Bill</span>
                                </button>

                                @if (! $ord->isPaid())
                                <!-- Settle Payment Button -->
                                <button type="button"
                                        wire:click="openPayModal({{ $ord->id }})"
                                        class="px-3 py-1.5 rounded-lg bg-[var(--color-brand-600)] hover:bg-[var(--color-brand-700)] text-white text-xs font-bold flex items-center gap-1 shadow-2xs transition-colors">
                                    <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                    <span>Lunasi</span>
                                </button>

                                <!-- Void Button (KF-07) -->
                                <button type="button"
                                        wire:click="openVoidModal({{ $ord->id }})"
                                        class="p-1.5 rounded-lg border border-red-200 bg-white hover:bg-red-50 text-red-600 text-xs transition-colors"
                                        title="Batalkan (Void)">
                                    <i data-lucide="x-circle" class="w-4 h-4"></i>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-[var(--color-ink-muted)] text-xs">
                            Tidak ada data open bill pada tab ini.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL 1: SETTLE PAYMENT MODAL -->
    @if ($showPayModal && $selectedOrder)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs">
        <div class="bg-[var(--color-surface)] w-full max-w-md rounded-2xl border border-[var(--color-line)] shadow-2xl p-6 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-[var(--color-line)]">
                <div>
                    <h3 class="text-base font-bold text-[var(--color-ink)]">Pelunasan Open Bill</h3>
                    <span class="text-xs text-[var(--color-ink-muted)]">No. Order: {{ $selectedOrder->number }} ({{ $selectedOrder->customer_name }})</span>
                </div>
                <button type="button" wire:click="$set('showPayModal', false)" class="text-[var(--color-ink-muted)] hover:text-[var(--color-ink)]">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Total Banner -->
            <div class="p-3.5 rounded-xl bg-[var(--color-brand-50)] border border-[var(--color-brand-100)] flex items-center justify-between">
                <span class="text-xs font-semibold text-[var(--color-brand-900)]">Total yang Harus Dibayar:</span>
                <span class="text-2xl font-black text-[var(--color-brand-700)] tabular-nums">
                    {{ $selectedOrder->formattedTotal() }}
                </span>
            </div>

            <!-- Method Selection -->
            <div class="grid grid-cols-2 gap-2">
                <button type="button"
                        wire:click="$set('paymentMethod', 'cash')"
                        class="p-2.5 rounded-xl border text-center transition-all flex items-center justify-center gap-1.5 {{ $paymentMethod === 'cash' ? 'border-[var(--color-brand-600)] bg-[var(--color-brand-50)] text-[var(--color-brand-700)] font-bold' : 'border-[var(--color-line)] bg-white text-[var(--color-ink)]' }}">
                    <i data-lucide="banknote" class="w-4 h-4"></i>
                    <span class="text-xs">Uang Tunai</span>
                </button>

                <button type="button"
                        wire:click="$set('paymentMethod', 'qris')"
                        class="p-2.5 rounded-xl border text-center transition-all flex items-center justify-center gap-1.5 {{ $paymentMethod === 'qris' ? 'border-[var(--color-brand-600)] bg-[var(--color-brand-50)] text-[var(--color-brand-700)] font-bold' : 'border-[var(--color-line)] bg-white text-[var(--color-ink)]' }}">
                    <i data-lucide="qr-code" class="w-4 h-4"></i>
                    <span class="text-xs">QRIS UMCO</span>
                </button>
            </div>

            @if ($paymentMethod === 'cash')
            <div class="space-y-2 text-xs">
                <label class="block font-semibold">Uang Tunai Diterima:</label>
                <input type="number"
                       wire:model.live.debounce.150ms="cashReceived"
                       class="w-full h-11 px-3 text-lg font-bold text-right rounded-lg border border-[var(--color-line)] bg-white outline-none focus:border-[var(--color-brand-600)] tabular-nums">
                @error('cashReceived') <span class="text-red-600 text-[11px] block">{{ $message }}</span> @enderror

                <div class="p-3 rounded-lg bg-[var(--color-bg)] flex justify-between items-center text-xs">
                    <span class="text-[var(--color-ink-muted)]">Kembalian:</span>
                    <span class="font-bold text-base tabular-nums {{ $cashReceived >= $selectedOrder->total ? 'text-emerald-700' : 'text-red-600' }}">
                        Rp{{ number_format(max(0, $cashReceived - $selectedOrder->total), 0, ',', '.') }}
                    </span>
                </div>
            </div>
            @else
            <div class="space-y-2 text-xs text-center pt-1">
                <p class="font-medium text-[var(--color-ink-muted)]">Tunjukkan QRIS kasir dan konfirmasi penerimaan dana.</p>
                <input type="text"
                       wire:model="qrisReference"
                       placeholder="No. Referensi / RRN QRIS (Opsional)"
                       class="w-full h-9 px-3 text-xs text-center rounded-lg border border-[var(--color-line)] bg-white outline-none focus:border-[var(--color-brand-600)]">
            </div>
            @endif

            <div class="pt-3 border-t border-[var(--color-line)] flex items-center justify-end gap-2">
                <button type="button" wire:click="$set('showPayModal', false)" class="px-3 py-2 text-xs font-semibold text-[var(--color-ink-muted)]">
                    Batal
                </button>
                <button type="button" wire:click="settlePayment" class="px-4 py-2 rounded-lg bg-[var(--color-brand-600)] hover:bg-[var(--color-brand-700)] text-white text-xs font-bold shadow-sm">
                    Konfirmasi Pelunasan
                </button>
            </div>
        </div>
    </div>
    @endif

    <!-- MODAL 2: PRINTABLE BILL MODAL (KF-21) -->
    @if ($showPrintBillModal && $printOrder)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs">
        <div class="bg-[var(--color-surface)] w-full max-w-sm rounded-2xl border border-[var(--color-line)] shadow-2xl p-5 space-y-4 max-h-[90vh] flex flex-col">
            <div class="flex items-center justify-between pb-2 border-b border-[var(--color-line)] shrink-0">
                <h3 class="text-sm font-bold text-[var(--color-ink)]">Cetak Surat Tagihan (Bill)</h3>
                <button type="button" wire:click="$set('showPrintBillModal', false)" class="text-[var(--color-ink-muted)] hover:text-[var(--color-ink)]">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <!-- Bill Content -->
            <div class="flex-1 overflow-y-auto p-4 bg-white border border-[var(--color-line)] rounded-xl font-mono text-[11px] leading-tight text-neutral-800 space-y-2 select-text" id="printable-receipt">
                <div class="text-center space-y-0.5 border-b border-dashed border-neutral-300 pb-2">
                    <div class="font-extrabold text-sm uppercase tracking-wider text-black">UMCO HOUSE</div>
                    <div class="text-[10px]">Unit Bisnis BUMK Univ. Muhammadiyah Riau</div>
                    <div class="mt-1.5 p-1 bg-amber-100 text-amber-900 font-black text-xs text-center border border-amber-300">
                        *** SURAT TAGIHAN (OPEN BILL) ***<br>
                        STATUS: BELUM DIBAYAR
                    </div>
                </div>

                <div class="space-y-0.5 text-[10px] border-b border-dashed border-neutral-300 pb-2">
                    <div class="flex justify-between">
                        <span>No. Order:</span>
                        <span class="font-bold text-black">{{ $printOrder->number }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Tanggal:</span>
                        <span>{{ $printOrder->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Pemesan / Instansi:</span>
                        <span class="font-bold text-black">{{ $printOrder->customer_name }}</span>
                    </div>
                    @if ($printOrder->open_bill_note)
                    <div class="text-[9px] text-neutral-600 mt-1 p-1 bg-neutral-50 border">
                        Keperluan: {{ $printOrder->open_bill_note }}
                    </div>
                    @endif
                </div>

                <!-- Items -->
                <div class="border-b border-dashed border-neutral-300 py-1 space-y-1">
                    @foreach ($printOrder->items as $it)
                    <div class="flex justify-between">
                        <div>
                            <span class="font-bold text-black">{{ $it->name }}</span>
                            <div class="text-[10px] text-neutral-600">{{ $it->qty }} × Rp{{ number_format($it->unit_price, 0, ',', '.') }}</div>
                        </div>
                        <span class="tabular-nums font-bold">Rp{{ number_format($it->lineSubtotal(), 0, ',', '.') }}</span>
                    </div>
                    @endforeach
                </div>

                <!-- Total -->
                <div class="space-y-0.5 text-[10px] border-b border-dashed border-neutral-300 pb-2">
                    <div class="flex justify-between text-xs font-black text-black pt-1">
                        <span>TOTAL TAGIHAN:</span>
                        <span class="tabular-nums text-sm">{{ $printOrder->formattedTotal() }}</span>
                    </div>
                </div>

                <!-- Signature Section for University / Faculty Verification -->
                <div class="pt-2 text-[10px] space-y-6">
                    <div class="flex justify-between text-center pt-2">
                        <div>
                            <span>Kasir UMCO,</span>
                            <div class="h-8"></div>
                            <span>({{ $printOrder->cashier?->name ?? 'Kasir' }})</span>
                        </div>
                        <div>
                            <span>Penerima / Pemesan,</span>
                            <div class="h-8"></div>
                            <span>({{ $printOrder->customer_name }})</span>
                        </div>
                    </div>
                    <div class="text-center text-[9px] text-neutral-500">
                        Harap membawa struk bill ini saat melakukan pelunasan di kasir UMCO House.
                    </div>
                </div>
            </div>

            <div class="pt-2 flex items-center justify-between gap-2 shrink-0">
                <button type="button" onclick="window.print()" class="flex-1 h-10 rounded-lg bg-[var(--color-brand-600)] hover:bg-[var(--color-brand-700)] text-white text-xs font-bold flex items-center justify-center gap-1.5 shadow-xs">
                    <i data-lucide="printer" class="w-4 h-4"></i>
                    <span>Cetak Lembar Bill</span>
                </button>
                <button type="button" wire:click="$set('showPrintBillModal', false)" class="h-10 px-4 rounded-lg border border-[var(--color-line)] bg-white text-xs font-bold text-[var(--color-ink)]">
                    Tutup
                </button>
            </div>
        </div>
    </div>
    @endif

    <!-- MODAL 3: VOID WITH MANAGER PIN (KF-07, KNF-05) -->
    @if ($showVoidModal && $voidOrder)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs">
        <div class="bg-[var(--color-surface)] w-full max-w-sm rounded-2xl border border-[var(--color-line)] shadow-2xl p-5 space-y-4">
            <div class="flex items-center justify-between pb-2 border-b border-[var(--color-line)]">
                <div class="flex items-center gap-2">
                    <span class="p-1 rounded-md bg-red-100 text-red-700">
                        <i data-lucide="alert-triangle" class="w-4 h-4"></i>
                    </span>
                    <div>
                        <h3 class="text-sm font-bold text-red-900">Pembatalan Nota (Void)</h3>
                        <span class="text-[11px] text-[var(--color-ink-muted)]">No: {{ $voidOrder->number }}</span>
                    </div>
                </div>
                <button type="button" wire:click="$set('showVoidModal', false)" class="text-[var(--color-ink-muted)] hover:text-[var(--color-ink)]">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <div class="space-y-3 text-xs">
                <div>
                    <label class="block font-semibold mb-1 text-[var(--color-ink)]">Alasan Pembatalan (Wajib):</label>
                    <select wire:model="voidReason" class="w-full h-9 px-2 text-xs rounded-lg border border-[var(--color-line)] bg-white outline-none focus:border-red-500">
                        <option value="input_error">Kesalahan Input Kasir</option>
                        <option value="customer_cancel">Pelanggan Membatalkan</option>
                        <option value="out_of_stock">Bahan Baku / Menu Habis</option>
                        <option value="other">Alasan Lainnya (Ketik di bawah)</option>
                    </select>
                </div>

                @if ($voidReason === 'other')
                <div>
                    <input type="text"
                           wire:model="voidDetail"
                           placeholder="Ketik rincian alasan pembatalan..."
                           class="w-full h-9 px-2 text-xs rounded-lg border border-[var(--color-line)] bg-white outline-none focus:border-red-500">
                </div>
                @endif

                <!-- Manager Selector -->
                <div>
                    <label class="block font-semibold mb-1 text-[var(--color-ink)]">Pilih Manajer Penyetuju:</label>
                    <select wire:model="selectedManagerId" class="w-full h-9 px-2 text-xs rounded-lg border border-[var(--color-line)] bg-white outline-none focus:border-red-500">
                        <option value="">Semua Manajer Berwenang</option>
                        @foreach ($managers as $mgr)
                        <option value="{{ $mgr->id }}">{{ $mgr->name }} ({{ $mgr->getRoleNames()->first() }})</option>
                        @endforeach
                    </select>
                </div>

                <!-- PIN Display (Dots) -->
                <div>
                    <label class="block font-semibold mb-1 text-center text-[var(--color-ink)]">Masukkan PIN Manajer (6 Digit):</label>
                    <div class="flex items-center justify-center gap-2 py-2">
                        @for ($i = 0; $i < 6; $i++)
                        <span class="w-3.5 h-3.5 rounded-full border border-neutral-300 {{ strlen($managerPin) > $i ? 'bg-red-600 border-red-600 scale-110' : 'bg-neutral-100' }} transition-all"></span>
                        @endfor
                    </div>
                    @error('managerPin')
                    <span class="text-red-600 text-[11px] font-semibold text-center block mt-1">{{ $message }}</span>
                    @enderror
                </div>

                <!-- On-Screen Numpad for Tablet & Touch -->
                <div class="grid grid-cols-3 gap-1.5 pt-1">
                    @for ($d = 1; $d <= 9; $d++)
                    <button type="button"
                            wire:click="appendPin('{{ $d }}')"
                            class="h-10 rounded-lg border border-[var(--color-line)] bg-white hover:bg-neutral-100 font-bold text-sm text-[var(--color-ink)] active:scale-95 transition-all">
                        {{ $d }}
                    </button>
                    @endfor
                    <button type="button"
                            wire:click="clearPin"
                            class="h-10 rounded-lg border border-[var(--color-line)] bg-white hover:bg-red-50 font-bold text-xs text-red-600 active:scale-95 transition-all">
                        C
                    </button>
                    <button type="button"
                            wire:click="appendPin('0')"
                            class="h-10 rounded-lg border border-[var(--color-line)] bg-white hover:bg-neutral-100 font-bold text-sm text-[var(--color-ink)] active:scale-95 transition-all">
                        0
                    </button>
                    <button type="button"
                            wire:click="backspacePin"
                            class="h-10 rounded-lg border border-[var(--color-line)] bg-white hover:bg-neutral-100 font-bold text-xs text-[var(--color-ink)] flex items-center justify-center active:scale-95 transition-all">
                        <i data-lucide="delete" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>

            <div class="pt-3 border-t border-[var(--color-line)] flex items-center justify-end gap-2">
                <button type="button" wire:click="$set('showVoidModal', false)" class="px-3 py-2 text-xs font-semibold text-[var(--color-ink-muted)]">
                    Batal
                </button>
                <button type="button"
                        wire:click="confirmVoid"
                        class="px-4 py-2 rounded-lg bg-red-600 hover:bg-red-700 text-white text-xs font-bold shadow-xs">
                    Setujui Pembatalan (Void)
                </button>
            </div>
        </div>
    </div>
    @endif

</div>
