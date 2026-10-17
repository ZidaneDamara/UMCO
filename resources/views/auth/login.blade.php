<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk — UMCO House</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-[var(--color-bg)] flex items-center justify-center p-4">

    <div class="w-full max-w-md">
        <!-- Logo & Title -->
        <div class="text-center mb-8">
            <div class="w-16 h-16 mx-auto mb-3 rounded-2xl bg-[var(--color-brand-600)] text-white flex items-center justify-center font-bold text-2xl shadow-md shadow-emerald-900/10">
                UH
            </div>
            <h1 class="text-2xl font-bold text-[var(--color-ink)]">UMCO House</h1>
            <p class="text-sm text-[var(--color-ink-muted)] mt-1">Sistem Terpadu POS & Operasional · BUMK UMRI</p>
        </div>

        <!-- Login Card -->
        <div class="bg-[var(--color-surface)] border border-[var(--color-line)] rounded-2xl p-6 sm:p-8 shadow-sm">
            <div class="mb-6">
                <h2 class="text-lg font-bold text-[var(--color-ink)]">Masuk ke Sistem</h2>
                <p class="text-xs text-[var(--color-ink-muted)] mt-0.5">Gunakan akun terdaftar Anda untuk mulai bertransaksi</p>
            </div>

            @if ($errors->any())
            <div class="mb-5 p-3 rounded-xl bg-red-50 border border-red-200 text-xs text-red-700 flex items-start gap-2.5">
                <i data-lucide="alert-circle" class="w-4 h-4 text-red-600 shrink-0 mt-0.5"></i>
                <div class="space-y-1">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="login" class="block text-xs font-semibold text-[var(--color-ink)] mb-1.5">
                        Email atau Username
                    </label>
                    <div class="relative">
                        <input type="text" 
                               id="login" 
                               name="login" 
                               value="{{ old('login') }}" 
                               required 
                               autofocus 
                               placeholder="kasir@umco.test atau kasir"
                               class="w-full h-11 pl-10 pr-3 text-sm rounded-lg border border-[var(--color-line)] bg-white text-[var(--color-ink)] placeholder-[var(--color-ink-muted)]/50 focus:border-[var(--color-brand-600)] focus:ring-2 focus:ring-[var(--color-brand-600)]/20 outline-none transition-all">
                        <i data-lucide="user" class="w-4 h-4 text-[var(--color-ink-muted)] absolute left-3.5 top-3.5"></i>
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-xs font-semibold text-[var(--color-ink)] mb-1.5">
                        Kata Sandi
                    </label>
                    <div class="relative">
                        <input type="password" 
                               id="password" 
                               name="password" 
                               required 
                               placeholder="••••••••"
                               class="w-full h-11 pl-10 pr-3 text-sm rounded-lg border border-[var(--color-line)] bg-white text-[var(--color-ink)] placeholder-[var(--color-ink-muted)]/50 focus:border-[var(--color-brand-600)] focus:ring-2 focus:ring-[var(--color-brand-600)]/20 outline-none transition-all">
                        <i data-lucide="lock" class="w-4 h-4 text-[var(--color-ink-muted)] absolute left-3.5 top-3.5"></i>
                    </div>
                </div>

                <div class="flex items-center justify-between text-xs pt-1">
                    <label class="flex items-center gap-2 cursor-pointer select-none">
                        <input type="checkbox" name="remember" class="w-4 h-4 rounded text-[var(--color-brand-600)] focus:ring-[var(--color-brand-600)] border-[var(--color-line)]">
                        <span class="text-[var(--color-ink-muted)]">Ingat saya</span>
                    </label>
                </div>

                <button type="submit" 
                        class="w-full h-11 rounded-lg bg-[var(--color-brand-600)] hover:bg-[var(--color-brand-700)] text-white font-semibold text-sm shadow-sm transition-all flex items-center justify-center gap-2 mt-2">
                    <span>Masuk</span>
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </button>
            </form>

            <!-- Quick Account Fill for Development & Testing -->
            <div class="mt-8 pt-6 border-t border-[var(--color-line)]">
                <span class="block text-[11px] font-semibold text-[var(--color-ink-muted)] uppercase tracking-wider mb-2.5">
                    Akses Cepat (Akun Uji):
                </span>
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <button type="button" 
                            onclick="fillForm('kasir@umco.test', 'password')"
                            class="p-2 border border-[var(--color-line)] rounded-lg text-left hover:bg-[var(--color-brand-50)] hover:border-[var(--color-brand-600)] transition-all">
                        <div class="font-bold text-[var(--color-ink)]">Kasir</div>
                        <div class="text-[10px] text-[var(--color-ink-muted)]">POS & Transaksi</div>
                    </button>
                    <button type="button" 
                            onclick="fillForm('manager@umco.test', 'password')"
                            class="p-2 border border-[var(--color-line)] rounded-lg text-left hover:bg-[var(--color-brand-50)] hover:border-[var(--color-brand-600)] transition-all">
                        <div class="font-bold text-[var(--color-ink)]">Manajer</div>
                        <div class="text-[10px] text-[var(--color-ink-muted)]">Akses Penuh / Void</div>
                    </button>
                    <button type="button" 
                            onclick="fillForm('barista@umco.test', 'password')"
                            class="p-2 border border-[var(--color-line)] rounded-lg text-left hover:bg-[var(--color-brand-50)] hover:border-[var(--color-brand-600)] transition-all">
                        <div class="font-bold text-[var(--color-ink)]">Barista</div>
                        <div class="text-[10px] text-[var(--color-ink-muted)]">Tiket Minuman</div>
                    </button>
                    <button type="button" 
                            onclick="fillForm('kitchen@umco.test', 'password')"
                            class="p-2 border border-[var(--color-line)] rounded-lg text-left hover:bg-[var(--color-brand-50)] hover:border-[var(--color-brand-600)] transition-all">
                        <div class="font-bold text-[var(--color-ink)]">Dapur UMC</div>
                        <div class="text-[10px] text-[var(--color-ink-muted)]">Tiket Makanan</div>
                    </button>
                </div>
            </div>
        </div>

        <p class="text-center text-xs text-[var(--color-ink-muted)] mt-6">
            © 2026 UMCO House · Universitas Muhammadiyah Riau
        </p>
    </div>

    <script>
        lucide.createIcons();

        function fillForm(login, password) {
            document.getElementById('login').value = login;
            document.getElementById('password').value = password;
        }
    </script>
</body>
</html>
