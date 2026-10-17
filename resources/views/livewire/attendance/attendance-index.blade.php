<div class="h-full flex flex-col p-4 bg-[var(--color-bg)] overflow-hidden">
    
    <!-- Top Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-[var(--color-line)] shrink-0">
        <div>
            <h2 class="text-base font-bold text-[var(--color-ink)]">Absensi Karyawan & Bukti Kehadiran</h2>
            <span class="text-xs text-[var(--color-ink-muted)]">Presensi masuk & pulang dengan validasi jadwal kerja dan foto selfie (KF-02)</span>
        </div>

        @can('attendance.manage')
        <div class="flex items-center gap-1 bg-white p-1 rounded-xl border border-[var(--color-line)] text-xs">
            <button type="button"
                    wire:click="$set('viewTab', 'self')"
                    class="px-3.5 py-1.5 rounded-lg font-semibold transition-all {{ $viewTab === 'self' ? 'bg-[var(--color-brand-600)] text-white shadow-2xs' : 'text-[var(--color-ink-muted)] hover:text-[var(--color-ink)]' }}">
                Absen Saya
            </button>
            <button type="button"
                    wire:click="$set('viewTab', 'recap')"
                    class="px-3.5 py-1.5 rounded-lg font-semibold transition-all {{ $viewTab === 'recap' ? 'bg-[var(--color-brand-600)] text-white shadow-2xs' : 'text-[var(--color-ink-muted)] hover:text-[var(--color-ink)]' }}">
                Rekap Kehadiran Tim
            </button>
        </div>
        @endcan
    </div>

    <!-- MAIN BODY AREA -->
    <div class="flex-1 overflow-y-auto pt-3 pb-6">

        @if ($viewTab === 'self')
        <!-- TAB 1: EMPLOYEE SELF-ATTENDANCE -->
        <div class="max-w-2xl mx-auto space-y-4">
            
            <!-- Today's Schedule & Attendance Card -->
            <div class="bg-[var(--color-surface)] border border-[var(--color-line)] rounded-2xl p-6 shadow-sm space-y-5">
                <div class="flex items-center justify-between border-b border-[var(--color-line)] pb-4">
                    <div>
                        <span class="text-xs font-semibold text-[var(--color-brand-600)] uppercase tracking-wider block">Hari Ini</span>
                        <h3 class="text-lg font-extrabold text-[var(--color-ink)] mt-0.5">
                            {{ now()->isoFormat('dddd, D MMMM Y') }}
                        </h3>
                    </div>

                    <div class="text-right">
                        <span class="text-xs font-semibold text-[var(--color-ink-muted)] block">Jadwal Shift Anda:</span>
                        @if ($todaySchedule)
                        <span class="text-sm font-extrabold text-[var(--color-brand-700)]">
                            {{ $todaySchedule->slotLabel() }}
                        </span>
                        @else
                        <span class="text-xs font-medium text-amber-700 bg-amber-50 px-2.5 py-1 rounded-full">
                            Tidak ada jadwal terjadwal
                        </span>
                        @endif
                    </div>
                </div>

                <!-- Clock Status & Action Buttons -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Check In Box -->
                    <div class="p-4 rounded-xl border border-[var(--color-line)] bg-[var(--color-bg)]/50 flex flex-col justify-between">
                        <div>
                            <span class="text-[11px] font-bold text-[var(--color-ink-muted)] uppercase tracking-wider block">Jam Masuk</span>
                            @if ($todayAttendance && $todayAttendance->check_in_at)
                            <div class="mt-2">
                                <span class="text-2xl font-black text-emerald-700 tabular-nums">
                                    {{ $todayAttendance->check_in_at->format('H:i') }}
                                </span>
                                <span class="text-xs text-[var(--color-ink-muted)] ml-1">WIB</span>
                                <div class="mt-2">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $todayAttendance->statusBadgeClass() }}">
                                        {{ $todayAttendance->statusLabel() }}
                                    </span>
                                </div>
                            </div>
                            @else
                            <div class="mt-2 text-sm text-[var(--color-ink-muted)] font-medium">
                                Belum absen masuk
                            </div>
                            @endif
                        </div>

                        @if (! $todayAttendance || ! $todayAttendance->check_in_at)
                        <button type="button"
                                wire:click="openCheckInModal"
                                class="mt-4 w-full py-2.5 px-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs flex items-center justify-center gap-1.5 shadow-xs transition-colors">
                            <i data-lucide="camera" class="w-4 h-4"></i>
                            <span>Absen Masuk (Selfie)</span>
                        </button>
                        @endif
                    </div>

                    <!-- Check Out Box -->
                    <div class="p-4 rounded-xl border border-[var(--color-line)] bg-[var(--color-bg)]/50 flex flex-col justify-between">
                        <div>
                            <span class="text-[11px] font-bold text-[var(--color-ink-muted)] uppercase tracking-wider block">Jam Pulang</span>
                            @if ($todayAttendance && $todayAttendance->check_out_at)
                            <div class="mt-2">
                                <span class="text-2xl font-black text-blue-700 tabular-nums">
                                    {{ $todayAttendance->check_out_at->format('H:i') }}
                                </span>
                                <span class="text-xs text-[var(--color-ink-muted)] ml-1">WIB</span>
                                <div class="mt-2">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800 border border-blue-200">
                                        Sudah Pulang
                                    </span>
                                </div>
                            </div>
                            @else
                            <div class="mt-2 text-sm text-[var(--color-ink-muted)] font-medium">
                                {{ $todayAttendance ? 'Sedang bertugas' : 'Belum mulai shift' }}
                            </div>
                            @endif
                        </div>

                        @if ($todayAttendance && $todayAttendance->check_in_at && ! $todayAttendance->check_out_at)
                        <button type="button"
                                wire:click="openCheckOutModal"
                                class="mt-4 w-full py-2.5 px-3 rounded-xl bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs flex items-center justify-center gap-1.5 shadow-xs transition-colors">
                            <i data-lucide="log-out" class="w-4 h-4"></i>
                            <span>Absen Pulang</span>
                        </button>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Recent History Table -->
            <div class="bg-[var(--color-surface)] border border-[var(--color-line)] rounded-2xl overflow-hidden shadow-2xs">
                <div class="p-3.5 border-b border-[var(--color-line)] bg-[var(--color-bg)]/80 flex items-center justify-between">
                    <span class="text-xs font-bold text-[var(--color-ink)]">Riwayat Kehadiran 14 Hari Terakhir</span>
                    <span class="text-xs text-[var(--color-ink-muted)]">{{ $myHistory->count() }} hari tercatat</span>
                </div>

                <table class="w-full text-left text-xs">
                    <thead class="bg-[var(--color-bg)]/80 text-[var(--color-ink-muted)] border-b border-[var(--color-line)]">
                        <tr>
                            <th class="py-2.5 px-4 font-bold">Tanggal</th>
                            <th class="py-2.5 px-4 font-bold">Shift</th>
                            <th class="py-2.5 px-4 font-bold text-center">Masuk</th>
                            <th class="py-2.5 px-4 font-bold text-center">Pulang</th>
                            <th class="py-2.5 px-4 font-bold text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--color-line)]">
                        @forelse ($myHistory as $item)
                        <tr class="hover:bg-[var(--color-brand-50)]/40">
                            <td class="py-2.5 px-4 font-bold text-[var(--color-ink)]">
                                {{ $item->work_date->format('d/m/Y') }}
                            </td>
                            <td class="py-2.5 px-4 text-[var(--color-ink-muted)]">
                                {{ $item->schedule ? $item->schedule->slotLabel() : 'Mandiri' }}
                            </td>
                            <td class="py-2.5 px-4 text-center font-bold tabular-nums">
                                {{ $item->check_in_at ? $item->check_in_at->format('H:i') : '-' }}
                            </td>
                            <td class="py-2.5 px-4 text-center font-bold tabular-nums">
                                {{ $item->check_out_at ? $item->check_out_at->format('H:i') : '-' }}
                            </td>
                            <td class="py-2.5 px-4 text-center">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $item->statusBadgeClass() }}">
                                    {{ $item->statusLabel() }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="py-6 text-center text-[var(--color-ink-muted)] text-xs">
                                Belum ada riwayat absensi.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
        @else
        <!-- TAB 2: REKAP KEHADIRAN TIM (MANAGER / BUMK) (KF-02) -->
        <div class="space-y-4">
            <!-- Filter Bar -->
            <div class="p-4 rounded-xl bg-white border border-[var(--color-line)] flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-2xs">
                <div>
                    <h3 class="text-sm font-bold text-[var(--color-ink)]">Rekap Kehadiran Karyawan</h3>
                    <p class="text-xs text-[var(--color-ink-muted)] mt-0.5">Laporan presensi, ketepatan waktu, dan verifikasi foto selfie tim</p>
                </div>

                <div class="flex items-center gap-2">
                    <input type="date"
                           wire:model.live="selectedDateFilter"
                           class="h-9 px-3 text-xs rounded-lg border border-[var(--color-line)] bg-white outline-none focus:border-[var(--color-brand-600)]">
                    @if ($selectedDateFilter)
                    <button type="button"
                            wire:click="$set('selectedDateFilter', '')"
                            class="text-xs text-neutral-500 hover:text-neutral-800 underline">
                        Bulan Ini
                    </button>
                    @endif
                </div>
            </div>

            <!-- Attendance Table -->
            <div class="bg-[var(--color-surface)] border border-[var(--color-line)] rounded-xl overflow-hidden shadow-2xs">
                <table class="w-full text-left text-xs">
                    <thead class="bg-[var(--color-bg)]/80 text-[var(--color-ink-muted)] border-b border-[var(--color-line)]">
                        <tr>
                            <th class="py-3 px-4 font-bold">Karyawan</th>
                            <th class="py-3 px-4 font-bold">Role</th>
                            <th class="py-3 px-4 font-bold">Tanggal</th>
                            <th class="py-3 px-4 font-bold">Shift</th>
                            <th class="py-3 px-4 font-bold text-center">Masuk</th>
                            <th class="py-3 px-4 font-bold text-center">Pulang</th>
                            <th class="py-3 px-4 font-bold text-center">Status</th>
                            <th class="py-3 px-4 font-bold text-center">Foto Selfie</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--color-line)]">
                        @forelse ($recapAttendances as $att)
                        <tr class="hover:bg-[var(--color-brand-50)]/40 transition-colors">
                            <td class="py-2.5 px-4 font-bold text-[var(--color-ink)]">
                                {{ $att->user->name }}
                            </td>
                            <td class="py-2.5 px-4">
                                <span class="px-2 py-0.5 rounded bg-neutral-100 text-neutral-700 text-[10px] font-semibold uppercase">
                                    {{ $att->user->getRoleNames()->first() ?? 'Staff' }}
                                </span>
                            </td>
                            <td class="py-2.5 px-4 text-[var(--color-ink-muted)]">
                                {{ $att->work_date->format('d/m/Y') }}
                            </td>
                            <td class="py-2.5 px-4 text-[var(--color-ink-muted)]">
                                {{ $att->schedule ? $att->schedule->slotLabel() : 'Luar Jadwal' }}
                            </td>
                            <td class="py-2.5 px-4 text-center font-bold tabular-nums">
                                {{ $att->check_in_at ? $att->check_in_at->format('H:i') : '-' }}
                            </td>
                            <td class="py-2.5 px-4 text-center font-bold tabular-nums">
                                {{ $att->check_out_at ? $att->check_out_at->format('H:i') : '-' }}
                            </td>
                            <td class="py-2.5 px-4 text-center">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $att->statusBadgeClass() }}">
                                    {{ $att->statusLabel() }}
                                </span>
                            </td>
                            <td class="py-2.5 px-4 text-center">
                                @if ($att->photo_in_path)
                                <span class="inline-flex items-center gap-1 text-[10px] text-emerald-700 font-bold bg-emerald-50 px-2 py-0.5 rounded-full">
                                    <i data-lucide="check-circle" class="w-3 h-3"></i>
                                    <span>Ada Foto</span>
                                </span>
                                @else
                                <span class="text-[10px] text-neutral-400">-</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-[var(--color-ink-muted)] text-xs">
                                Tidak ada data kehadiran pada periode ini.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @endif

    </div>

    <!-- MODAL: SELFIE CAMERA & PRESENSI -->
    @if ($showCameraModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs">
        <div class="bg-[var(--color-surface)] w-full max-w-sm rounded-2xl border border-[var(--color-line)] shadow-2xl p-5 space-y-4">
            <div class="flex items-center justify-between pb-2 border-b border-[var(--color-line)]">
                <div>
                    <h3 class="text-sm font-bold text-[var(--color-ink)]">
                        {{ $cameraAction === 'in' ? 'Absensi Masuk (Selfie)' : 'Absensi Pulang' }}
                    </h3>
                    <span class="text-xs text-[var(--color-ink-muted)]">{{ now()->format('H:i') }} WIB</span>
                </div>
                <button type="button" wire:click="$set('showCameraModal', false)" class="text-[var(--color-ink-muted)] hover:text-[var(--color-ink)]">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <!-- Error Banner -->
            @error('checkInError')
            <div class="p-3 rounded-lg bg-red-50 border border-red-200 text-red-800 text-xs">
                {{ $message }}
            </div>
            @enderror

            <!-- Camera / Photo Input Area -->
            <div class="space-y-3 text-xs">
                <div class="aspect-4/3 w-full rounded-xl bg-neutral-900 border border-neutral-700 overflow-hidden flex flex-col items-center justify-center relative text-white">
                    @if ($capturedPhotoBase64)
                    <img src="{{ $capturedPhotoBase64 }}" class="w-full h-full object-cover">
                    <button type="button"
                            wire:click="$set('capturedPhotoBase64', null)"
                            class="absolute bottom-2 right-2 px-2 py-1 bg-black/70 hover:bg-black text-white text-[10px] font-bold rounded-md">
                        Foto Ulang
                    </button>
                    @else
                    <i data-lucide="camera" class="w-8 h-8 text-neutral-400 mb-2"></i>
                    <span class="text-[11px] text-neutral-300 font-medium text-center px-4">
                        Ambil foto selfie bukti kehadiran
                    </span>
                    <input type="file"
                           accept="image/*"
                           capture="user"
                           id="selfie-file-input"
                           class="hidden"
                           onchange="
                               const file = this.files[0];
                               if (file) {
                                   const reader = new FileReader();
                                   reader.onload = (e) => {
                                       @this.set('capturedPhotoBase64', e.target.result);
                                   };
                                   reader.readAsDataURL(file);
                               }
                           ">
                    <button type="button"
                            onclick="document.getElementById('selfie-file-input').click()"
                            class="mt-3 px-3.5 py-1.5 rounded-lg bg-[var(--color-brand-600)] hover:bg-[var(--color-brand-700)] text-white font-bold text-xs shadow-xs">
                        Buka Kamera / Pilih Foto
                    </button>
                    @endif
                </div>

                <div>
                    <label class="block font-semibold mb-1">Catatan Tambahan (Opsional):</label>
                    <input type="text"
                           wire:model="attendanceNote"
                           placeholder="Keterangan kondisi kerja..."
                           class="w-full h-9 px-3 rounded-lg border border-[var(--color-line)] bg-white text-xs outline-none focus:border-[var(--color-brand-600)]">
                </div>
            </div>

            <div class="pt-2 flex items-center justify-end gap-2 border-t border-[var(--color-line)]">
                <button type="button" wire:click="$set('showCameraModal', false)" class="px-3 py-2 text-xs font-semibold text-[var(--color-ink-muted)]">
                    Batal
                </button>
                @if ($cameraAction === 'in')
                <button type="button" wire:click="processCheckIn" class="px-4 py-2 text-xs font-bold rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs">
                    Konfirmasi Absen Masuk
                </button>
                @else
                <button type="button" wire:click="processCheckOut" class="px-4 py-2 text-xs font-bold rounded-lg bg-amber-600 hover:bg-amber-700 text-white shadow-xs">
                    Konfirmasi Absen Pulang
                </button>
                @endif
            </div>
        </div>
    </div>
    @endif

</div>
