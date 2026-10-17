<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0E7044">
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/favicon.ico">
    <title>{{ $title ?? 'POS' }} — UMCO House</title>

    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Lucide Icons via unpkg -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <script>
        window.UMCO_USER_ROLE = "{{ Auth::user()->getRoleNames()->first() ?? 'cashier' }}";
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full bg-[var(--color-bg)] text-[var(--color-ink)] antialiased font-sans flex flex-col md:flex-row overflow-hidden">

    <!-- Desktop Sidebar (232px) -->
    <aside id="main-sidebar" class="hidden md:flex flex-col w-58 shrink-0 bg-[var(--color-surface)] border-r border-[var(--color-line)] z-20 transition-all duration-200">
        <!-- Brand Header -->
        <div class="h-16 px-4 flex items-center justify-between border-b border-[var(--color-line)]">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-lg bg-[var(--color-brand-600)] text-white flex items-center justify-center font-bold text-base shadow-sm">
                    UH
                </div>
                <div>
                    <h1 class="text-sm font-bold tracking-tight text-[var(--color-ink)] leading-none">UMCO House</h1>
                    <span class="text-[11px] font-medium text-[var(--color-ink-muted)]">BUMK UMRI</span>
                </div>
            </div>
        </div>

        <!-- Navigation Links -->
        <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
            @can('pos.access')
            <a href="{{ route('pos.index') }}" 
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('pos.*') ? 'bg-[var(--color-brand-50)] text-[var(--color-brand-600)] font-semibold' : 'text-[var(--color-ink-muted)] hover:bg-[var(--color-brand-50)] hover:text-[var(--color-brand-600)]' }}">
                <i data-lucide="calculator" class="w-4.5 h-4.5"></i>
                <span>Kasir (POS)</span>
            </a>
            @endcan

            @can('orders.open_bill')
            <a href="{{ route('open-bills.index') }}" 
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('open-bills.*') ? 'bg-[var(--color-brand-50)] text-[var(--color-brand-600)] font-semibold' : 'text-[var(--color-ink-muted)] hover:bg-[var(--color-brand-50)] hover:text-[var(--color-brand-600)]' }}">
                <i data-lucide="receipt" class="w-4.5 h-4.5"></i>
                <span>Open Bill</span>
            </a>
            @endcan

            @can('production.bar')
            <a href="{{ route('station.bar') }}" 
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('station.bar') ? 'bg-[var(--color-brand-50)] text-[var(--color-brand-600)] font-semibold' : 'text-[var(--color-ink-muted)] hover:bg-[var(--color-brand-50)] hover:text-[var(--color-brand-600)]' }}">
                <i data-lucide="coffee" class="w-4.5 h-4.5"></i>
                <span>Layar Barista</span>
            </a>
            @endcan

            @can('production.kitchen')
            <a href="{{ route('station.kitchen') }}" 
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('station.kitchen') ? 'bg-[var(--color-brand-50)] text-[var(--color-brand-600)] font-semibold' : 'text-[var(--color-ink-muted)] hover:bg-[var(--color-brand-50)] hover:text-[var(--color-brand-600)]' }}">
                <i data-lucide="utensils" class="w-4.5 h-4.5"></i>
                <span>Layar Dapur</span>
            </a>
            @endcan

            @can('shifts.manage')
            <a href="{{ route('shifts.index') }}" 
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('shifts.*') ? 'bg-[var(--color-brand-50)] text-[var(--color-brand-600)] font-semibold' : 'text-[var(--color-ink-muted)] hover:bg-[var(--color-brand-50)] hover:text-[var(--color-brand-600)]' }}">
                <i data-lucide="wallet" class="w-4.5 h-4.5"></i>
                <span>Kelola Shift</span>
            </a>
            @endcan

            @can('menu.view')
            <a href="{{ route('menu.index') }}" 
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('menu.*') ? 'bg-[var(--color-brand-50)] text-[var(--color-brand-600)] font-semibold' : 'text-[var(--color-ink-muted)] hover:bg-[var(--color-brand-50)] hover:text-[var(--color-brand-600)]' }}">
                <i data-lucide="book-open" class="w-4.5 h-4.5"></i>
                <span>Daftar Menu</span>
            </a>
            @endcan

            @can('inventory.view')
            <a href="{{ route('inventory.index') }}" 
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('inventory.*') ? 'bg-[var(--color-brand-50)] text-[var(--color-brand-600)] font-semibold' : 'text-[var(--color-ink-muted)] hover:bg-[var(--color-brand-50)] hover:text-[var(--color-brand-600)]' }}">
                <i data-lucide="boxes" class="w-4.5 h-4.5"></i>
                <span>Stok Bahan</span>
            </a>
            @endcan

            @can('reports.view')
            <a href="{{ route('reports.index') }}" 
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('reports.*') ? 'bg-[var(--color-brand-50)] text-[var(--color-brand-600)] font-semibold' : 'text-[var(--color-ink-muted)] hover:bg-[var(--color-brand-50)] hover:text-[var(--color-brand-600)]' }}">
                <i data-lucide="bar-chart-3" class="w-4.5 h-4.5"></i>
                <span>Laporan Kasir</span>
            </a>
            @endcan

            @can('schedule.view')
            <a href="{{ route('schedule.index') }}" 
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('schedule.*') ? 'bg-[var(--color-brand-50)] text-[var(--color-brand-600)] font-semibold' : 'text-[var(--color-ink-muted)] hover:bg-[var(--color-brand-50)] hover:text-[var(--color-brand-600)]' }}">
                <i data-lucide="calendar" class="w-4.5 h-4.5"></i>
                <span>Jadwal Shift</span>
            </a>
            @endcan

            @can('attendance.self')
            <a href="{{ route('attendance.index') }}" 
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('attendance.*') ? 'bg-[var(--color-brand-50)] text-[var(--color-brand-600)] font-semibold' : 'text-[var(--color-ink-muted)] hover:bg-[var(--color-brand-50)] hover:text-[var(--color-brand-600)]' }}">
                <i data-lucide="user-check" class="w-4.5 h-4.5"></i>
                <span>Absensi Selfie</span>
            </a>
            @endcan
        </nav>

        <!-- Current Shift & User Footer -->
        <div class="p-3 border-t border-[var(--color-line)] bg-[var(--color-bg)]/60">
            <div class="flex items-center justify-between mb-2">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span class="text-xs font-semibold text-[var(--color-ink)] truncate max-w-[120px]">
                        {{ Auth::user()->name }}
                    </span>
                </div>
                <span class="text-[10px] font-medium px-2 py-0.5 rounded bg-[var(--color-brand-100)] text-[var(--color-brand-900)] uppercase">
                    {{ Auth::user()->getRoleNames()->first() ?? 'Staff' }}
                </span>
            </div>

            <div class="flex items-center gap-1.5 pt-1">
                <button type="button" 
                        onclick="document.getElementById('switch-user-modal').classList.remove('hidden')"
                        class="flex-1 flex items-center justify-center gap-1 py-1.5 px-2 text-xs font-medium bg-[var(--color-surface)] border border-[var(--color-line)] rounded-md hover:bg-[var(--color-brand-50)] text-[var(--color-ink)] transition-colors">
                    <i data-lucide="users" class="w-3.5 h-3.5 text-[var(--color-ink-muted)]"></i>
                    <span>Ganti User</span>
                </button>
                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button type="submit" 
                            title="Keluar"
                            class="p-1.5 text-xs font-medium bg-[var(--color-surface)] border border-[var(--color-line)] rounded-md hover:bg-red-50 hover:text-red-600 text-[var(--color-ink-muted)] transition-colors">
                        <i data-lucide="log-out" class="w-3.5 h-3.5"></i>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <!-- Main Content Shell -->
    <div class="flex-1 flex flex-col h-full min-w-0 overflow-hidden">
        <!-- Top App Bar -->
        <header class="h-14 px-4 bg-[var(--color-surface)] border-b border-[var(--color-line)] flex items-center justify-between shrink-0 z-10">
            <!-- Left: Page Title & Indicators -->
            <div class="flex items-center gap-3">
                <div class="md:hidden flex items-center gap-2">
                    <div class="w-7 h-7 rounded bg-[var(--color-brand-600)] text-white flex items-center justify-center font-bold text-xs">
                        UH
                    </div>
                </div>
                <h2 class="text-base font-bold text-[var(--color-ink)]">{{ $header ?? $title ?? 'POS Kasir' }}</h2>
                <div class="hidden sm:flex items-center gap-1.5 text-xs text-[var(--color-ink-muted)] border-l border-[var(--color-line)] pl-3">
                    <span id="connection-dot" class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span id="connection-text" class="text-[11px] font-medium">WebSocket Aktif</span>
                </div>
            </div>

            <!-- Right: Quick Indicators & Controls -->
            <div class="flex items-center gap-2">
                <!-- Sound Toggle Indicator -->
                <button type="button" 
                        id="sound-toggle-btn"
                        onclick="window.umcoSound.toggleMute()"
                        title="Status Suara Notifikasi"
                        class="p-2 text-xs rounded-lg border border-[var(--color-line)] bg-[var(--color-surface)] hover:bg-[var(--color-brand-50)] text-[var(--color-ink-muted)] transition-colors flex items-center gap-1.5">
                    <i id="sound-icon" data-lucide="volume-2" class="w-4 h-4 text-emerald-600"></i>
                    <span id="sound-text" class="hidden sm:inline text-xs font-medium">Suara Aktif</span>
                </button>

                <!-- Test Sound Button (DESIGN.md Section 5.3) -->
                <button type="button" 
                        onclick="window.umcoSound.play('ticket')"
                        title="Uji Bunyi Notifikasi"
                        class="hidden sm:flex p-2 text-xs rounded-lg border border-[var(--color-line)] bg-[var(--color-surface)] hover:bg-[var(--color-brand-50)] text-[var(--color-ink-muted)] hover:text-[var(--color-brand-700)] transition-colors items-center gap-1">
                    <i data-lucide="bell" class="w-3.5 h-3.5"></i>
                    <span class="text-[10px] font-semibold">Tes Bunyi</span>
                </button>

                <!-- Mobile Switch User Icon -->
                <button type="button" 
                        onclick="document.getElementById('switch-user-modal').classList.remove('hidden')"
                        class="md:hidden p-2 text-xs rounded-lg border border-[var(--color-line)] bg-[var(--color-surface)] hover:bg-[var(--color-brand-50)] text-[var(--color-ink)]">
                    <i data-lucide="user" class="w-4 h-4"></i>
                </button>
            </div>
        </header>

        <!-- Audio Activation Banner (DESIGN.md Section 5.3) -->
        <div id="sound-activation-banner" class="hidden mx-4 mt-3 p-3 bg-amber-50 border border-amber-200 text-amber-900 rounded-xl text-xs flex items-center justify-between shrink-0 shadow-2xs">
            <div class="flex items-center gap-2.5">
                <span class="p-1 rounded-md bg-amber-200/80 text-amber-800">
                    <i data-lucide="volume-x" class="w-4 h-4"></i>
                </span>
                <div>
                    <span class="font-bold">Aktifkan Suara Notifikasi:</span>
                    <span class="text-amber-800">Agar bunyi pesanan masuk dan notifikasi tiket berbunyi otomatis.</span>
                </div>
            </div>
            <button type="button" 
                    onclick="window.umcoSound.unlock()"
                    class="px-3.5 py-1.5 rounded-lg bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs shadow-xs transition-colors shrink-0">
                Aktifkan Suara
            </button>
        </div>

        <!-- Flash Notifications -->
        @if (session('success'))
        <div class="mx-4 mt-3 p-3 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-lg text-sm flex items-center justify-between animate-fade-in shrink-0">
            <div class="flex items-center gap-2">
                <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-600 shrink-0"></i>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-600 hover:text-emerald-800">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        @endif

        @if (session('error'))
        <div class="mx-4 mt-3 p-3 bg-red-50 border border-red-200 text-red-800 rounded-lg text-sm flex items-center justify-between animate-fade-in shrink-0">
            <div class="flex items-center gap-2">
                <i data-lucide="alert-circle" class="w-4 h-4 text-red-600 shrink-0"></i>
                <span>{{ session('error') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-red-600 hover:text-red-800">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        @endif

        <!-- Main Body Area -->
        <main class="flex-1 overflow-hidden relative">
            {{ $slot }}
        </main>

        <!-- Mobile Bottom Navigation (Visible on phones & tablet portrait) -->
        <nav class="md:hidden h-16 bg-[var(--color-surface)] border-t border-[var(--color-line)] grid grid-cols-5 items-center shrink-0 z-20">
            <a href="{{ route('pos.index') }}" 
               class="flex flex-col items-center justify-center py-1 text-[11px] font-medium {{ request()->routeIs('pos.*') ? 'text-[var(--color-brand-600)] font-bold' : 'text-[var(--color-ink-muted)]' }}">
                <i data-lucide="calculator" class="w-5 h-5 mb-0.5"></i>
                <span>POS</span>
            </a>
            <a href="{{ route('open-bills.index') }}" 
               class="flex flex-col items-center justify-center py-1 text-[11px] font-medium {{ request()->routeIs('open-bills.*') ? 'text-[var(--color-brand-600)] font-bold' : 'text-[var(--color-ink-muted)]' }}">
                <i data-lucide="receipt" class="w-5 h-5 mb-0.5"></i>
                <span>Bill</span>
            </a>
            <a href="{{ route('station.bar') }}" 
               class="flex flex-col items-center justify-center py-1 text-[11px] font-medium {{ request()->routeIs('station.bar') ? 'text-[var(--color-brand-600)] font-bold' : 'text-[var(--color-ink-muted)]' }}">
                <i data-lucide="coffee" class="w-5 h-5 mb-0.5"></i>
                <span>Bar</span>
            </a>
            <a href="{{ route('station.kitchen') }}" 
               class="flex flex-col items-center justify-center py-1 text-[11px] font-medium {{ request()->routeIs('station.kitchen') ? 'text-[var(--color-brand-600)] font-bold' : 'text-[var(--color-ink-muted)]' }}">
                <i data-lucide="utensils" class="w-5 h-5 mb-0.5"></i>
                <span>Dapur</span>
            </a>
            <a href="{{ route('shifts.index') }}" 
               class="flex flex-col items-center justify-center py-1 text-[11px] font-medium {{ request()->routeIs('shifts.*') ? 'text-[var(--color-brand-600)] font-bold' : 'text-[var(--color-ink-muted)]' }}">
                <i data-lucide="wallet" class="w-5 h-5 mb-0.5"></i>
                <span>Shift</span>
            </a>
        </nav>
    </div>

    <!-- Quick Switch User Modal (DESIGN.md Section 3) -->
    <div id="switch-user-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs">
        <div class="bg-[var(--color-surface)] w-full max-w-sm rounded-xl border border-[var(--color-line)] shadow-xl overflow-hidden p-6">
            <div class="flex items-center justify-between pb-3 border-b border-[var(--color-line)] mb-4">
                <h3 class="text-base font-bold text-[var(--color-ink)]">Ganti Pengguna Cepat</h3>
                <button type="button" 
                        onclick="document.getElementById('switch-user-modal').classList.add('hidden')"
                        class="text-[var(--color-ink-muted)] hover:text-[var(--color-ink)]">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('switch-user') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-[var(--color-ink)] mb-1.5">Pilih Petugas:</label>
                    <select name="user_id" required class="w-full h-11 px-3 rounded-lg border border-[var(--color-line)] bg-white text-sm focus:border-[var(--color-brand-600)] focus:ring-1 focus:ring-[var(--color-brand-600)] outline-none">
                        @foreach (\App\Models\User::where('is_active', true)->get() as $u)
                        <option value="{{ $u->id }}" {{ Auth::id() === $u->id ? 'selected' : '' }}>
                            {{ $u->name }} ({{ $u->getRoleNames()->first() ?? 'Staff' }})
                        </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-[var(--color-ink)] mb-1.5">Masukkan PIN (6 Digit):</label>
                    <input type="password" 
                           name="pin" 
                           maxlength="6" 
                           pattern="[0-9]*" 
                           inputmode="numeric" 
                           required 
                           placeholder="••••••"
                           class="w-full h-11 px-3 text-center text-lg tracking-widest font-mono rounded-lg border border-[var(--color-line)] bg-white focus:border-[var(--color-brand-600)] focus:ring-1 focus:ring-[var(--color-brand-600)] outline-none">
                </div>

                <div class="pt-2 flex items-center justify-end gap-2">
                    <button type="button" 
                            onclick="document.getElementById('switch-user-modal').classList.add('hidden')"
                            class="px-4 py-2.5 text-xs font-medium text-[var(--color-ink-muted)] hover:text-[var(--color-ink)]">
                        Batal
                    </button>
                    <button type="submit" 
                            class="px-4 py-2.5 text-xs font-bold rounded-lg bg-[var(--color-brand-600)] hover:bg-[var(--color-brand-700)] text-white shadow-xs">
                        Beralih Pengguna
                    </button>
                </div>
            </form>
        </div>
    </div>

    @livewireScripts
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) lucide.createIcons();
            if (window.umcoSound) window.umcoSound.updateHeaderUI();
        });
    </script>
</body>
</html>
