<div wire:poll.4s="refreshStatus" class="flex-1 flex flex-col p-5 select-none bg-[var(--color-bg)]">
    @if(! $order)
    <div class="flex-1 flex flex-col items-center justify-center text-center p-6">
        <div class="w-16 h-16 rounded-full bg-neutral-200 text-neutral-500 flex items-center justify-center mb-4">
            <i data-lucide="help-circle" class="w-8 h-8"></i>
        </div>
        <h2 class="text-base font-bold text-neutral-800 mb-1">Pesanan Tidak Ditemukan</h2>
        <p class="text-xs text-neutral-500 max-w-xs mb-4">
            Nomor pesanan {{ $orderNumber }} tidak ditemukan di sistem.
        </p>
        <a href="/" class="px-4 py-2 rounded-xl bg-[var(--color-brand-600)] text-white text-xs font-semibold">
            Kembali ke Beranda
        </a>
    </div>
    @else
    <!-- Top Header -->
    <div class="flex items-center justify-between pb-4 border-b border-[var(--color-line)] mb-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-[var(--color-brand-600)] text-white flex items-center justify-center font-bold text-base shadow-sm">
                UH
            </div>
            <div>
                <h1 class="text-xs font-semibold text-neutral-500 uppercase tracking-wider">Status Pesanan</h1>
                <div class="text-base font-extrabold text-neutral-900 leading-tight">#{{ $order->number }}</div>
            </div>
        </div>

        <button wire:click="refreshStatus" class="p-2 rounded-xl bg-white border border-neutral-200 text-neutral-600 hover:bg-neutral-50 shadow-xs flex items-center gap-1.5 text-xs font-semibold">
            <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
            <span>Segarkan</span>
        </button>
    </div>

    <!-- Table & Customer Badge -->
    <div class="p-3.5 rounded-2xl bg-white border border-neutral-200 shadow-xs mb-4 flex items-center justify-between">
        <div class="flex items-center gap-2.5">
            <div class="w-9 h-9 rounded-xl {{ $order->source->value === 'delivery' ? 'bg-amber-100 text-amber-800' : 'bg-[var(--color-brand-50)] text-[var(--color-brand-700)]' }} flex items-center justify-center">
                <i data-lucide="{{ $order->source->value === 'delivery' ? 'truck' : 'map-pin' }}" class="w-4.5 h-4.5"></i>
            </div>
            <div>
                <div class="text-xs font-bold text-neutral-900 leading-tight">
                    {{ $order->table ? $order->table->label : 'Takeaway' }}
                </div>
                <div class="text-[11px] text-neutral-500">
                    Pemesan: <span class="font-semibold text-neutral-700">{{ $order->customer_name ?: 'Pelanggan' }}</span>
                </div>
            </div>
        </div>

        <div class="text-right">
            <div class="text-[11px] text-neutral-400">Waktu Pesan</div>
            <div class="text-xs font-bold text-neutral-700 tabular-nums">
                {{ $order->created_at->format('H:i') }} WIB
            </div>
        </div>
    </div>

    @if($order->open_bill_note && $order->source->value === 'delivery')
    <div class="p-3 rounded-xl bg-amber-50 border border-amber-200/80 mb-4 text-xs text-amber-900 flex items-start gap-2">
        <i data-lucide="navigation" class="w-4 h-4 text-amber-700 shrink-0 mt-0.5"></i>
        <div>
            <div class="font-bold text-[11px] text-amber-800">Tujuan Antar:</div>
            <div>{{ $order->open_bill_note }}</div>
        </div>
    </div>
    @endif

    <!-- Stepper Status Tracker -->
    @php
        $status = $order->status->value;
        $isVoid = ($status === 'void');
        
        $step1Active = true;
        $step1Done = in_array($status, ['confirmed', 'preparing', 'ready', 'completed']);
        
        $step2Active = in_array($status, ['confirmed', 'preparing']);
        $step2Done = in_array($status, ['ready', 'completed']);
        
        $step3Active = ($status === 'ready');
        $step3Done = ($status === 'completed');
        
        $step4Done = ($status === 'completed');
    @endphp

    @if($isVoid)
    <div class="p-5 rounded-2xl bg-red-50 border border-red-200 text-center mb-5">
        <div class="w-12 h-12 rounded-full bg-red-100 text-red-600 flex items-center justify-center mx-auto mb-2">
            <i data-lucide="x-circle" class="w-6 h-6"></i>
        </div>
        <h3 class="text-sm font-bold text-red-900 mb-1">Pesanan Dibatalkan</h3>
        <p class="text-xs text-red-700 max-w-xs mx-auto">
            {{ $order->void?->reason ?? 'Pesanan ini telah dibatalkan oleh kasir/manajer.' }}
        </p>
    </div>
    @else
    <div class="p-5 rounded-2xl bg-white border border-neutral-200 shadow-xs mb-5">
        <h2 class="text-xs font-bold uppercase tracking-wider text-neutral-400 mb-4">Kemajuan Pesanan</h2>
        
        <div class="space-y-6 relative before:absolute before:left-4 before:top-2 before:bottom-2 before:w-0.5 before:bg-neutral-200">
            <!-- Step 1: Menunggu Konfirmasi -->
            <div class="relative flex items-start gap-3.5">
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold z-10 shrink-0 transition-all {{ $step1Done ? 'bg-emerald-600 text-white shadow-sm' : ($status === 'pending_confirmation' ? 'bg-amber-500 text-white ring-4 ring-amber-100 animate-pulse' : 'bg-neutral-200 text-neutral-500') }}">
                    @if($step1Done)
                    <i data-lucide="check" class="w-4 h-4"></i>
                    @else
                    <i data-lucide="clock" class="w-4 h-4"></i>
                    @endif
                </div>
                <div class="pt-0.5 flex-1">
                    <div class="text-xs font-bold {{ $status === 'pending_confirmation' ? 'text-amber-900' : 'text-neutral-800' }}">
                        1. Menunggu Konfirmasi Kasir
                    </div>
                    <div class="text-[11px] text-neutral-500 mt-0.5">
                        @if($status === 'pending_confirmation')
                        <span class="font-medium text-amber-700">Pesanan terkirim. Menunggu kasir menekan terima...</span>
                        @else
                        Pesanan telah diverifikasi oleh kasir.
                        @endif
                    </div>
                </div>
            </div>

            <!-- Step 2: Diracik di Barista / Dapur -->
            <div class="relative flex items-start gap-3.5">
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold z-10 shrink-0 transition-all {{ $step2Done ? 'bg-emerald-600 text-white shadow-sm' : ($step2Active ? 'bg-blue-600 text-white ring-4 ring-blue-100 animate-pulse' : 'bg-neutral-200 text-neutral-500') }}">
                    @if($step2Done)
                    <i data-lucide="check" class="w-4 h-4"></i>
                    @else
                    <i data-lucide="coffee" class="w-4 h-4"></i>
                    @endif
                </div>
                <div class="pt-0.5 flex-1">
                    <div class="text-xs font-bold {{ $step2Active ? 'text-blue-900' : 'text-neutral-800' }}">
                        2. Sedang Diracik (Barista / Dapur)
                    </div>
                    <div class="text-[11px] text-neutral-500 mt-0.5">
                        @if($step2Active)
                        <span class="font-medium text-blue-700">Tiket pesanan sedang dikerjakan tim Barista & Dapur.</span>
                        @elseif($step2Done)
                        Selesai diracik.
                        @else
                        Menunggu antrean peracikan.
                        @endif
                    </div>
                </div>
            </div>

            <!-- Step 3: Siap Disajikan / Diambil -->
            <div class="relative flex items-start gap-3.5">
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold z-10 shrink-0 transition-all {{ $step3Done ? 'bg-emerald-600 text-white shadow-sm' : ($step3Active ? 'bg-emerald-500 text-white ring-4 ring-emerald-100 animate-bounce' : 'bg-neutral-200 text-neutral-500') }}">
                    @if($step3Done)
                    <i data-lucide="check" class="w-4 h-4"></i>
                    @else
                    <i data-lucide="bell-ring" class="w-4 h-4"></i>
                    @endif
                </div>
                <div class="pt-0.5 flex-1">
                    <div class="text-xs font-bold {{ $step3Active ? 'text-emerald-900 font-extrabold text-sm' : 'text-neutral-800' }}">
                        3. Pesanan Siap!
                    </div>
                    <div class="text-[11px] text-neutral-500 mt-0.5">
                        @if($step3Active)
                        <span class="font-bold text-emerald-700">Pesanan Anda siap diambil di bar konter atau sedang diantar ke meja Anda!</span>
                        @elseif($step3Done)
                        Sudah diserahkan kepada pemesan.
                        @else
                        Segera siap setelah diracik.
                        @endif
                    </div>
                </div>
            </div>

            <!-- Step 4: Selesai -->
            <div class="relative flex items-start gap-3.5">
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold z-10 shrink-0 transition-all {{ $step4Done ? 'bg-emerald-600 text-white shadow-sm' : 'bg-neutral-200 text-neutral-500' }}">
                    <i data-lucide="party-popper" class="w-4 h-4"></i>
                </div>
                <div class="pt-0.5 flex-1">
                    <div class="text-xs font-bold {{ $step4Done ? 'text-neutral-900' : 'text-neutral-500' }}">
                        4. Selesai
                    </div>
                    <div class="text-[11px] text-neutral-400 mt-0.5">
                        Nikmati santapan Anda di UMCO House!
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Payment Status Badge -->
    <div class="p-4 rounded-2xl border mb-5 {{ $order->payment_status->value === 'paid' ? 'bg-emerald-50 border-emerald-200 text-emerald-950' : 'bg-amber-50 border-amber-200 text-amber-950' }} flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl {{ $order->payment_status->value === 'paid' ? 'bg-emerald-600 text-white' : 'bg-amber-500 text-white' }} flex items-center justify-center font-bold">
                <i data-lucide="{{ $order->payment_status->value === 'paid' ? 'check-check' : 'wallet' }}" class="w-4.5 h-4.5"></i>
            </div>
            <div>
                <div class="text-xs font-bold leading-tight">
                    {{ $order->payment_status->value === 'paid' ? 'Pembayaran Lunas' : 'Belum Dibayar' }}
                </div>
                <div class="text-[11px] {{ $order->payment_status->value === 'paid' ? 'text-emerald-700' : 'text-amber-700' }} mt-0.5">
                    @if($order->payment_status->value === 'paid')
                    Metode: {{ $order->payments->first()?->method->value === 'qris' ? 'QRIS' : 'Tunai' }}
                    @else
                    Silakan lakukan pembayaran di kasir (Tunai / QRIS)
                    @endif
                </div>
            </div>
        </div>

        <div class="text-right">
            <div class="text-sm font-black tabular-nums">
                Rp{{ number_format($order->total, 0, ',', '.') }}
            </div>
        </div>
    </div>

    <!-- Items Breakdown -->
    <div class="p-4 rounded-2xl bg-white border border-neutral-200 shadow-xs mb-6">
        <h3 class="text-xs font-bold uppercase tracking-wider text-neutral-400 mb-3">Rincian Menu</h3>
        <div class="divide-y divide-neutral-100">
            @foreach($order->items as $item)
            <div class="py-2.5 flex items-start justify-between gap-3">
                <div class="flex-1 min-w-0">
                    <div class="text-xs font-bold text-neutral-900 leading-snug">
                        {{ $item->name }}
                    </div>
                    @if($item->note)
                    <div class="text-[11px] text-amber-700 italic mt-0.5">
                        Catatan: {{ $item->note }}
                    </div>
                    @endif
                    <div class="text-[11px] text-neutral-400 tabular-nums">
                        Rp{{ number_format($item->unit_price, 0, ',', '.') }} x {{ $item->qty }}
                    </div>
                </div>

                <div class="text-xs font-extrabold text-neutral-800 tabular-nums shrink-0">
                    Rp{{ number_format($item->unit_price * $item->qty, 0, ',', '.') }}
                </div>
            </div>
            @endforeach
        </div>

        <div class="pt-3 border-t border-neutral-200 flex justify-between items-center text-xs">
            <span class="font-bold text-neutral-600">Total Tagihan</span>
            <span class="text-sm font-black text-[var(--color-brand-700)] tabular-nums">
                Rp{{ number_format($order->total, 0, ',', '.') }}
            </span>
        </div>
    </div>

    <!-- Bottom Actions -->
    <div class="mt-auto space-y-2 pt-2">
        @if($order->table && $order->table->token)
        <a href="{{ route('customer.menu', ['token' => $order->table->token]) }}" 
           class="w-full py-3 rounded-2xl bg-[var(--color-brand-600)] text-white text-xs font-bold shadow hover:bg-[var(--color-brand-700)] transition flex items-center justify-center gap-2">
            <i data-lucide="plus" class="w-4 h-4"></i>
            <span>Pesan Tambahan Menu</span>
        </a>
        @endif
        
        <p class="text-[11px] text-center text-neutral-400">
            Halaman ini otomatis diperbarui setiap beberapa detik.
        </p>
    </div>
    @endif
</div>
