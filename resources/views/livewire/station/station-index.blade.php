<div class="h-full flex flex-col p-4 bg-[var(--color-bg)] overflow-hidden" wire:poll.5s>
    <!-- Screen Header -->
    <div class="flex items-center justify-between pb-3 border-b border-[var(--color-line)] shrink-0">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg {{ $station === 'bar' ? 'bg-amber-600' : 'bg-emerald-700' }} text-white flex items-center justify-center font-bold">
                <i data-lucide="{{ $station === 'bar' ? 'coffee' : 'utensils' }}" class="w-4 h-4"></i>
            </div>
            <div>
                <h2 class="text-base font-bold text-[var(--color-ink)]">{{ $stationTitle }}</h2>
                <span class="text-xs text-[var(--color-ink-muted)]">Papan tiket pesanan real-time (Otomatis sinkron)</span>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <span class="text-xs px-2.5 py-1 rounded-full bg-[var(--color-brand-100)] text-[var(--color-brand-900)] font-semibold flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span>Live Feed</span>
            </span>
        </div>
    </div>

    <!-- 3-Column Ticket Board -->
    <div class="flex-1 grid grid-cols-1 md:grid-cols-3 gap-4 pt-4 overflow-hidden min-h-0">
        
        <!-- COLUMN 1: PESANAN BARU -->
        <div class="flex flex-col bg-amber-50/50 border border-amber-200/80 rounded-2xl overflow-hidden">
            <div class="p-3 bg-amber-100/60 border-b border-amber-200 flex items-center justify-between shrink-0">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                    <h3 class="text-xs font-bold text-amber-950 uppercase tracking-wider">Baru Masuk</h3>
                </div>
                <span class="text-xs font-extrabold px-2 py-0.5 rounded-full bg-amber-200 text-amber-900">
                    {{ $newOrders->count() }}
                </span>
            </div>

            <div class="flex-1 p-3 overflow-y-auto space-y-3">
                @forelse ($newOrders as $order)
                <div wire:key="ticket-new-{{ $order->id }}"
                     class="p-4 rounded-xl border border-amber-200 bg-white shadow-xs space-y-3">
                    <div class="flex items-start justify-between border-b border-neutral-100 pb-2">
                        <div>
                            <span class="text-xs font-black text-[var(--color-ink)]">{{ $order->number }}</span>
                            <div class="text-[11px] font-semibold text-neutral-600 mt-0.5">
                                {{ $order->customer_name ?: 'Walk-in' }} · <span class="text-amber-700 font-bold">{{ $order->table_label }}</span>
                            </div>
                        </div>
                        <span class="text-[10px] text-neutral-400 font-medium">
                            {{ $order->created_at->diffForHumans(null, true) }}
                        </span>
                    </div>

                    <!-- Items List -->
                    <div class="space-y-1.5 text-xs">
                        @foreach ($order->items as $it)
                        <div class="space-y-0.5">
                            <div class="flex items-start justify-between font-bold text-neutral-800">
                                <span>{{ $it->name }}</span>
                                <span class="px-1.5 py-0.5 rounded bg-neutral-100 text-[11px]">×{{ $it->qty }}</span>
                            </div>
                            @if ($it->note)
                            <div class="text-[11px] font-semibold text-amber-800 bg-amber-50 px-2 py-1 rounded border border-amber-100">
                                ⚠️ {{ $it->note }}
                            </div>
                            @endif
                        </div>
                        @endforeach
                    </div>

                    <!-- Advance Action Button -->
                    <button type="button"
                            wire:click="advanceStatus({{ $order->id }})"
                            class="w-full h-10 rounded-lg bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs flex items-center justify-center gap-1.5 shadow-xs transition-colors">
                        <i data-lucide="play" class="w-3.5 h-3.5"></i>
                        <span>Mulai Racik / Proses</span>
                    </button>
                </div>
                @empty
                <div class="h-40 flex flex-col items-center justify-center text-center text-amber-800/60 text-xs">
                    <p>Tidak ada tiket baru.</p>
                </div>
                @endforelse
            </div>
        </div>

        <!-- COLUMN 2: SEDANG DIPROSES -->
        <div class="flex flex-col bg-blue-50/40 border border-blue-200/80 rounded-2xl overflow-hidden">
            <div class="p-3 bg-blue-100/60 border-b border-blue-200 flex items-center justify-between shrink-0">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-blue-500 animate-pulse"></span>
                    <h3 class="text-xs font-bold text-blue-950 uppercase tracking-wider">Sedang Dibuat</h3>
                </div>
                <span class="text-xs font-extrabold px-2 py-0.5 rounded-full bg-blue-200 text-blue-900">
                    {{ $preparingOrders->count() }}
                </span>
            </div>

            <div class="flex-1 p-3 overflow-y-auto space-y-3">
                @forelse ($preparingOrders as $order)
                <div wire:key="ticket-prep-{{ $order->id }}"
                     class="p-4 rounded-xl border border-blue-200 bg-white shadow-xs space-y-3">
                    <div class="flex items-start justify-between border-b border-neutral-100 pb-2">
                        <div>
                            <span class="text-xs font-black text-[var(--color-ink)]">{{ $order->number }}</span>
                            <div class="text-[11px] font-semibold text-neutral-600 mt-0.5">
                                {{ $order->customer_name ?: 'Walk-in' }} · <span class="text-blue-700 font-bold">{{ $order->table_label }}</span>
                            </div>
                        </div>
                        <span class="text-[10px] text-blue-600 font-semibold bg-blue-50 px-2 py-0.5 rounded-full">
                            Diproses
                        </span>
                    </div>

                    <!-- Items List -->
                    <div class="space-y-1.5 text-xs">
                        @foreach ($order->items as $it)
                        <div class="space-y-0.5">
                            <div class="flex items-start justify-between font-bold text-neutral-800">
                                <span>{{ $it->name }}</span>
                                <span class="px-1.5 py-0.5 rounded bg-neutral-100 text-[11px]">×{{ $it->qty }}</span>
                            </div>
                            @if ($it->note)
                            <div class="text-[11px] font-semibold text-amber-800 bg-amber-50 px-2 py-1 rounded border border-amber-100">
                                ⚠️ {{ $it->note }}
                            </div>
                            @endif
                        </div>
                        @endforeach
                    </div>

                    <!-- Advance Action Button -->
                    <button type="button"
                            wire:click="advanceStatus({{ $order->id }})"
                            class="w-full h-10 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs flex items-center justify-center gap-1.5 shadow-xs transition-colors">
                        <i data-lucide="check" class="w-3.5 h-3.5"></i>
                        <span>Tandai Siap Saji</span>
                    </button>
                </div>
                @empty
                <div class="h-40 flex flex-col items-center justify-center text-center text-blue-800/60 text-xs">
                    <p>Tidak ada tiket dalam proses.</p>
                </div>
                @endforelse
            </div>
        </div>

        <!-- COLUMN 3: SIAP SAJI -->
        <div class="flex flex-col bg-emerald-50/40 border border-emerald-200/80 rounded-2xl overflow-hidden">
            <div class="p-3 bg-emerald-100/60 border-b border-emerald-200 flex items-center justify-between shrink-0">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                    <h3 class="text-xs font-bold text-emerald-950 uppercase tracking-wider">Siap Disajikan</h3>
                </div>
                <span class="text-xs font-extrabold px-2 py-0.5 rounded-full bg-emerald-200 text-emerald-900">
                    {{ $readyOrders->count() }}
                </span>
            </div>

            <div class="flex-1 p-3 overflow-y-auto space-y-3">
                @forelse ($readyOrders as $order)
                <div wire:key="ticket-ready-{{ $order->id }}"
                     class="p-4 rounded-xl border border-emerald-200 bg-white shadow-xs space-y-3">
                    <div class="flex items-start justify-between border-b border-neutral-100 pb-2">
                        <div>
                            <span class="text-xs font-black text-[var(--color-ink)]">{{ $order->number }}</span>
                            <div class="text-[11px] font-semibold text-neutral-600 mt-0.5">
                                {{ $order->customer_name ?: 'Walk-in' }} · <span class="text-emerald-700 font-bold">{{ $order->table_label }}</span>
                            </div>
                        </div>
                        <span class="text-[10px] text-emerald-700 font-bold bg-emerald-100 px-2 py-0.5 rounded-full">
                            Siap
                        </span>
                    </div>

                    <!-- Items List -->
                    <div class="space-y-1 text-xs">
                        @foreach ($order->items as $it)
                        <div class="flex items-start justify-between font-medium text-neutral-700">
                            <span>{{ $it->name }}</span>
                            <span class="font-bold">×{{ $it->qty }}</span>
                        </div>
                        @endforeach
                    </div>

                    <!-- Complete Action Button -->
                    <button type="button"
                            wire:click="advanceStatus({{ $order->id }})"
                            class="w-full h-10 rounded-lg bg-neutral-800 hover:bg-neutral-900 text-white font-bold text-xs flex items-center justify-center gap-1.5 shadow-xs transition-colors">
                        <i data-lucide="check-check" class="w-3.5 h-3.5"></i>
                        <span>Selesaikan (Sudah Diambil)</span>
                    </button>
                </div>
                @empty
                <div class="h-40 flex flex-col items-center justify-center text-center text-emerald-800/60 text-xs">
                    <p>Tidak ada pesanan siap saji.</p>
                </div>
                @endforelse
            </div>
        </div>

    </div>
</div>
