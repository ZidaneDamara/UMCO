<div class="h-full flex flex-col p-4 bg-[var(--color-bg)] overflow-hidden">
    
    <!-- Top Header & Week Switcher -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-[var(--color-line)] shrink-0">
        <div>
            <h2 class="text-base font-bold text-[var(--color-ink)]">Jadwal Shift Karyawan</h2>
            <span class="text-xs text-[var(--color-ink-muted)]">Pengaturan giliran kerja mingguan Senin–Sabtu (KF-08, KF-09)</span>
        </div>

        <!-- Week Navigator -->
        <div class="flex items-center gap-2">
            <button type="button"
                    wire:click="previousWeek"
                    class="p-2 rounded-lg border border-[var(--color-line)] bg-white hover:bg-[var(--color-brand-50)] text-xs text-[var(--color-ink)] transition-colors"
                    title="Minggu Sebelumnya">
                <i data-lucide="chevron-left" class="w-4 h-4"></i>
            </button>

            <button type="button"
                    wire:click="currentWeek"
                    class="px-3 py-1.5 rounded-lg border border-[var(--color-line)] bg-white hover:bg-[var(--color-brand-50)] text-xs font-semibold text-[var(--color-ink)] transition-colors">
                Minggu Ini
            </button>

            <button type="button"
                    wire:click="nextWeek"
                    class="p-2 rounded-lg border border-[var(--color-line)] bg-white hover:bg-[var(--color-brand-50)] text-xs text-[var(--color-ink)] transition-colors"
                    title="Minggu Berikutnya">
                <i data-lucide="chevron-right" class="w-4 h-4"></i>
            </button>
        </div>
    </div>

    <!-- Weekly Board Grid (Senin - Sabtu) -->
    <div class="flex-1 overflow-x-auto overflow-y-auto pt-3 pb-6">
        <div class="min-w-[850px] bg-[var(--color-surface)] border border-[var(--color-line)] rounded-2xl overflow-hidden shadow-2xs">
            
            <!-- Table Header: Days of the week -->
            <div class="grid grid-cols-7 border-b border-[var(--color-line)] bg-[var(--color-bg)]/80 text-xs font-bold divide-x divide-[var(--color-line)]">
                <div class="p-3 text-[var(--color-ink-muted)] flex items-center justify-center">
                    Shift / Waktu
                </div>
                @foreach ($days as $day)
                <div class="p-3 text-center {{ $day['is_today'] ? 'bg-[var(--color-brand-50)] text-[var(--color-brand-800)]' : 'text-[var(--color-ink)]' }}">
                    <div class="font-extrabold text-sm">{{ $day['day_name'] }}</div>
                    <div class="text-[11px] font-medium text-[var(--color-ink-muted)] mt-0.5">{{ $day['formatted'] }}</div>
                    @if ($day['is_today'])
                    <span class="inline-block mt-1 px-2 py-0.2 rounded-full text-[9px] font-black bg-[var(--color-brand-600)] text-white uppercase tracking-wider">
                        Hari Ini
                    </span>
                    @endif
                </div>
                @endforeach
            </div>

            <!-- Shift Rows -->
            @php
                $slots = [
                    'shift1' => ['title' => 'Shift 1', 'time' => '08.00–15.00', 'color' => 'amber'],
                    'shift2' => ['title' => 'Shift 2', 'time' => '15.00–22.00', 'color' => 'blue'],
                    'kitchen' => ['title' => 'Kitchen', 'time' => '09.00–17.00', 'color' => 'emerald'],
                ];
            @endphp

            @foreach ($slots as $slotKey => $slotMeta)
            <div class="grid grid-cols-7 border-b border-[var(--color-line)] divide-x divide-[var(--color-line)] text-xs min-h-[140px]">
                <!-- Slot Label Column -->
                <div class="p-3 bg-[var(--color-bg)]/40 flex flex-col justify-center items-center text-center">
                    <span class="font-extrabold text-[var(--color-ink)] text-sm">{{ $slotMeta['title'] }}</span>
                    <span class="text-[10px] font-semibold text-[var(--color-ink-muted)] mt-0.5">{{ $slotMeta['time'] }}</span>
                    <span class="mt-2 w-2 h-2 rounded-full bg-{{ $slotMeta['color'] }}-500"></span>
                </div>

                <!-- Day Cells -->
                @foreach ($days as $day)
                @php
                    $cellSchedules = $schedules->where('work_date', $day['date'])->where('slot', $slotKey);
                @endphp
                <div class="p-2 space-y-1.5 flex flex-col justify-between hover:bg-[var(--color-brand-50)]/20 transition-colors {{ $day['is_today'] ? 'bg-[var(--color-brand-50)]/15' : '' }}">
                    <div class="space-y-1.5 flex-1">
                        @forelse ($cellSchedules as $sch)
                        @php
                            $isMe = $sch->user_id === Auth::id();
                        @endphp
                        <div class="p-2 rounded-xl border text-xs relative group transition-all {{ $isMe ? 'bg-emerald-50 border-emerald-300 shadow-2xs' : 'bg-white border-[var(--color-line)]' }}">
                            <div class="flex items-center justify-between gap-1">
                                <span class="font-bold text-[var(--color-ink)] truncate max-w-[90px]">
                                    {{ $sch->user->name }}
                                </span>
                                @can('schedule.manage')
                                <button type="button"
                                        wire:click="deleteSchedule({{ $sch->id }})"
                                        wire:confirm="Hapus penugasan jadwal ini?"
                                        class="opacity-0 group-hover:opacity-100 text-neutral-400 hover:text-red-600 transition-opacity p-0.5"
                                        title="Hapus Penugasan">
                                    <i data-lucide="x" class="w-3 h-3"></i>
                                </button>
                                @endcan
                            </div>

                            <div class="flex items-center justify-between mt-1 text-[10px]">
                                <span class="px-1.5 py-0.2 rounded bg-neutral-100 text-neutral-700 uppercase font-semibold">
                                    {{ $sch->user->getRoleNames()->first() ?? 'Staff' }}
                                </span>
                                @if ($isMe)
                                <span class="text-emerald-700 font-extrabold text-[9px]">Anda</span>
                                @endif
                            </div>
                        </div>
                        @empty
                        <div class="h-full flex items-center justify-center text-center text-[10px] text-[var(--color-ink-muted)] py-4">
                            -
                        </div>
                        @endforelse
                    </div>

                    @can('schedule.manage')
                    <button type="button"
                            wire:click="openAssignModal('{{ $day['date'] }}', '{{ $slotKey }}')"
                            class="w-full py-1 text-[10px] font-bold text-[var(--color-brand-600)] hover:text-white hover:bg-[var(--color-brand-600)] rounded-md border border-dashed border-[var(--color-brand-300)] flex items-center justify-center gap-1 transition-colors">
                        <i data-lucide="plus" class="w-3 h-3"></i>
                        <span>Tugaskan</span>
                    </button>
                    @endcan
                </div>
                @endforeach
            </div>
            @endforeach

        </div>
    </div>

    <!-- MODAL: ASSIGN EMPLOYEE TO SCHEDULE -->
    @if ($showAssignModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs">
        <div class="bg-[var(--color-surface)] w-full max-w-sm rounded-xl border border-[var(--color-line)] shadow-xl p-5 space-y-4">
            <div class="flex items-center justify-between pb-2 border-b border-[var(--color-line)]">
                <div>
                    <h3 class="text-sm font-bold text-[var(--color-ink)]">Tugaskan Jadwal Shift</h3>
                    <span class="text-xs text-[var(--color-ink-muted)]">Tanggal: {{ \Carbon\Carbon::parse($selectedDate)->format('d F Y') }}</span>
                </div>
                <button type="button" wire:click="$set('showAssignModal', false)" class="text-[var(--color-ink-muted)] hover:text-[var(--color-ink)]">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <div class="space-y-3 text-xs">
                <div>
                    <label class="block font-semibold mb-1">Pilih Karyawan:</label>
                    <select wire:model="selectedUserId" class="w-full h-10 px-3 rounded-lg border border-[var(--color-line)] bg-white text-xs outline-none focus:border-[var(--color-brand-600)]">
                        @foreach ($users as $u)
                        <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->getRoleNames()->first() ?? 'Staff' }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block font-semibold mb-1">Slot Shift:</label>
                    <select wire:model="selectedSlot" class="w-full h-10 px-3 rounded-lg border border-[var(--color-line)] bg-white text-xs outline-none focus:border-[var(--color-brand-600)]">
                        <option value="shift1">Shift 1 (08.00–15.00)</option>
                        <option value="shift2">Shift 2 (15.00–22.00)</option>
                        <option value="kitchen">Kitchen (09.00–17.00)</option>
                    </select>
                </div>

                <div>
                    <label class="block font-semibold mb-1">Catatan Khusus (Opsional):</label>
                    <input type="text"
                           wire:model="scheduleNotes"
                           placeholder="Contoh: Kasir utama / Handle bar"
                           class="w-full h-10 px-3 rounded-lg border border-[var(--color-line)] bg-white text-xs outline-none focus:border-[var(--color-brand-600)]">
                </div>
            </div>

            <div class="pt-2 flex items-center justify-end gap-2">
                <button type="button" wire:click="$set('showAssignModal', false)" class="px-3 py-2 text-xs font-semibold text-[var(--color-ink-muted)]">
                    Batal
                </button>
                <button type="button" wire:click="assignSchedule" class="px-4 py-2 text-xs font-bold rounded-lg bg-[var(--color-brand-600)] hover:bg-[var(--color-brand-700)] text-white shadow-xs">
                    Simpan Penugasan
                </button>
            </div>
        </div>
    </div>
    @endif

</div>
