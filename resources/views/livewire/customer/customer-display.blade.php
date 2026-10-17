<div wire:poll.3s class="flex-1 flex flex-col bg-neutral-900 text-white min-h-screen select-none overflow-hidden">
    <!-- Top Header -->
    <header class="h-20 px-8 flex items-center justify-between border-b border-white/10 bg-neutral-950/40 backdrop-blur-md">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-[var(--color-brand-600)] flex items-center justify-center font-black text-xl text-white shadow-lg shadow-emerald-950/50">
                UH
            </div>
            <div>
                <h1 class="text-xl font-black tracking-tight leading-none text-white">UMCO HOUSE</h1>
                <p class="text-xs text-neutral-400 font-medium mt-1">Universitas Muhammadiyah Riau — Coffee & Workspace</p>
            </div>
        </div>

        <div class="flex items-center gap-6">
            <div class="text-right">
                <div class="text-xs text-neutral-400">Wi-Fi Pelanggan</div>
                <div class="text-xs font-bold text-emerald-400">UMCO_GUEST / umco2026</div>
            </div>
            <div class="h-8 w-px bg-white/10"></div>
            <div class="text-right">
                <div class="text-lg font-black tabular-nums leading-none text-white">{{ now()->format('H:i') }}</div>
                <div class="text-[11px] text-neutral-400 mt-1">{{ now()->translatedFormat('l, d M Y') }}</div>
            </div>
        </div>
    </header>

    @if($viewMode === 'paid' && $order)
    <!-- Paid Success Screen -->
    <div class="flex-1 flex flex-col items-center justify-center p-8 text-center animate-in zoom-in-95 duration-200">
        <div class="w-24 h-24 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center mb-6 ring-8 ring-emerald-500/10">
            <i data-lucide="check-circle-2" class="w-14 h-14"></i>
        </div>
        <h2 class="text-3xl font-black text-white mb-2">Terima Kasih!</h2>
        <p class="text-lg text-emerald-400 font-bold mb-4">Pembayaran Berhasil — #{{ $order->number }}</p>
        <p class="text-sm text-neutral-400 max-w-md mx-auto leading-relaxed">
            Pesanan Anda segera disajikan oleh tim barista kami. Silakan mengambil tempat duduk dan menikmati waktu Anda di UMCO House.
        </p>
    </div>

    @elseif($viewMode === 'checkout' && $order)
    <!-- Active Transaction / Order Breakdown -->
    <div class="flex-1 grid grid-cols-12 p-8 gap-8 items-start overflow-hidden">
        <!-- Left: Cart Items -->
        <div class="col-span-7 flex flex-col h-full bg-neutral-950/50 rounded-3xl border border-white/10 p-6 shadow-2xl">
            <div class="flex items-center justify-between pb-4 border-b border-white/10 mb-4">
                <div>
                    <h2 class="text-lg font-bold text-white">Pesanan Anda</h2>
                    <span class="text-xs text-neutral-400">Nota #{{ $order->number }} ({{ $order->table_label }})</span>
                </div>
                <div class="px-3 py-1 rounded-full bg-amber-500/20 text-amber-300 border border-amber-500/30 text-xs font-bold">
                    {{ $order->status->label() }}
                </div>
            </div>

            <div class="flex-1 divide-y divide-white/5 overflow-y-auto pr-2 space-y-1">
                @foreach($order->items as $item)
                <div class="py-3 flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <span class="w-7 h-7 rounded-lg bg-white/10 text-white font-extrabold text-xs flex items-center justify-center shrink-0">
                            {{ $item->qty }}x
                        </span>
                        <div>
                            <div class="text-sm font-bold text-white leading-tight">{{ $item->name }}</div>
                            @if($item->note)
                            <div class="text-xs text-amber-400/90 italic mt-0.5">{{ $item->note }}</div>
                            @endif
                        </div>
                    </div>
                    <div class="text-sm font-bold tabular-nums text-neutral-200">
                        Rp{{ number_format($item->unit_price * $item->qty, 0, ',', '.') }}
                    </div>
                </div>
                @endforeach
            </div>

            <!-- Price Breakdown -->
            <div class="pt-4 border-t border-white/10 space-y-2 mt-auto">
                <div class="flex justify-between text-xs text-neutral-400">
                    <span>Subtotal</span>
                    <span class="tabular-nums font-semibold text-neutral-300">Rp{{ number_format($order->subtotal, 0, ',', '.') }}</span>
                </div>
                @if($order->discount_total > 0)
                <div class="flex justify-between text-xs text-emerald-400">
                    <span>Total Diskon</span>
                    <span class="tabular-nums font-bold">-Rp{{ number_format($order->discount_total, 0, ',', '.') }}</span>
                </div>
                @endif
                <div class="flex justify-between items-center text-lg font-black text-white pt-2 border-t border-white/10">
                    <span>Total Tagihan</span>
                    <span class="text-2xl text-emerald-400 tabular-nums">Rp{{ number_format($order->total, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>

        <!-- Right: Payment & QRIS instructions -->
        <div class="col-span-5 flex flex-col items-center justify-center h-full bg-neutral-950/50 rounded-3xl border border-white/10 p-6 text-center">
            <h3 class="text-base font-bold text-white mb-1">Scan QRIS untuk Bayar</h3>
            <p class="text-xs text-neutral-400 mb-6">Mendukung BCA, Mandiri, BRI, BSI, Gopay, OVO, ShopeePay</p>

            <div class="w-56 h-56 p-4 rounded-3xl bg-white flex flex-col items-center justify-center shadow-2xl mb-4">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=https://umcohouse.id/pay/{{ $order->number }}" 
                     alt="QRIS Pembayaran" class="w-44 h-44 object-contain">
                <span class="text-[10px] font-black text-neutral-900 tracking-widest uppercase mt-1">QRIS STANDAR NASIONAL</span>
            </div>

            <div class="text-xs font-semibold text-emerald-400 flex items-center gap-1.5 mb-2">
                <i data-lucide="shield-check" class="w-4 h-4"></i>
                <span>Transaksi Cepat, Aman & Praktis</span>
            </div>
            <div class="text-[11px] text-neutral-500">
                Atau bayar dengan Tunai langsung ke Kasir.
            </div>
        </div>
    </div>

    @else
    <!-- Idle Ambient Mode -->
    <div class="flex-1 flex flex-col items-center justify-center p-12 text-center relative overflow-hidden">
        <div class="absolute -top-32 -left-32 w-96 h-96 rounded-full bg-emerald-600/10 blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-32 -right-32 w-96 h-96 rounded-full bg-amber-600/10 blur-3xl pointer-events-none"></div>

        <div class="max-w-2xl relative z-10">
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/10 backdrop-blur-md text-emerald-400 text-xs font-bold mb-6 border border-white/10">
                <i data-lucide="coffee" class="w-4 h-4"></i>
                <span>Selamat Datang di UMCO House UMRI</span>
            </div>

            <h2 class="text-4xl md:text-5xl font-black text-white leading-tight mb-4 tracking-tight">
                Kopi Pilihan & Sajian Segar Terbaik
            </h2>
            <p class="text-base text-neutral-300 mb-8 max-w-lg mx-auto leading-relaxed font-normal">
                Dapatkan potongan harga khusus <strong class="text-emerald-400">Rp5.000</strong> untuk Mahasiswa UMRI dan <strong class="text-amber-400">15%</strong> untuk Dosen & Karyawan!
            </p>

            <div class="grid grid-cols-3 gap-4 text-left">
                <div class="p-4 rounded-2xl bg-white/5 border border-white/10 backdrop-blur-sm">
                    <div class="text-xs font-bold text-emerald-400 mb-1">🎓 Mahasiswa UMRI</div>
                    <div class="text-xs text-neutral-300">Diskon Rp5.000 per cup minuman (Tunjukkan KTM & Follow IG).</div>
                </div>
                <div class="p-4 rounded-2xl bg-white/5 border border-white/10 backdrop-blur-sm">
                    <div class="text-xs font-bold text-amber-400 mb-1">👨‍🏫 Dosen & Karyawan</div>
                    <div class="text-xs text-neutral-300">Diskon 15% untuk seluruh menu kopi & minuman segar.</div>
                </div>
                <div class="p-4 rounded-2xl bg-white/5 border border-white/10 backdrop-blur-sm">
                    <div class="text-xs font-bold text-blue-400 mb-1">📱 Self-Service QR</div>
                    <div class="text-xs text-neutral-300">Pesan tanpa antre dengan scan QR di meja Anda.</div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
