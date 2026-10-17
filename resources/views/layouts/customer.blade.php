<!DOCTYPE html>
<html lang="id" class="h-full bg-neutral-900/5">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#1F7A4D">
    <link rel="icon" href="/favicon.ico">
    <title>{{ $title ?? 'Pesan Menu' }} — UMCO House</title>

    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Lucide Icons via unpkg -->
    <script src="https://unpkg.com/lucide@latest"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full bg-[var(--color-bg)] text-[var(--color-ink)] antialiased font-sans flex flex-col items-center">

    <!-- Mobile Shell Container (Max Width 480px on larger screens for realistic mobile view) -->
    <div class="w-full max-w-lg min-h-screen flex flex-col bg-[var(--color-surface)] shadow-xl relative border-x border-[var(--color-line)]">
        {{ $slot }}
    </div>

    @livewireScripts
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) {
                window.lucide.createIcons();
            }
        });
        document.addEventListener('livewire:navigated', () => {
            if (window.lucide) {
                window.lucide.createIcons();
            }
        });
        document.addEventListener('livewire:load', () => {
            if (window.lucide) {
                window.lucide.createIcons();
            }
        });
    </script>
</body>
</html>
