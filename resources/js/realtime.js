// resources/js/realtime.js (DESIGN.md Section 5)
import { echo } from './echo.js';
import { sound } from './sound-manager.js';

class RealtimeNotificationSystem {
    constructor() {
        this.currentRole = window.UMCO_USER_ROLE || 'cashier';
        this.isConnected = false;
        this.pollTimer = null;
        this.seenOrderIds = new Set();
        this.init();
    }

    init() {
        this.setupEchoListeners();
        this.startFallbackPolling();
        this.setupConnectionMonitoring();
    }

    setupEchoListeners() {
        if (!window.Echo) return;

        // Cashier & Manager channel
        if (['cashier', 'manager', 'bumk_staff'].includes(this.currentRole)) {
            window.Echo.private('cashier')
                .listen('.order.created', (e) => {
                    sound.play('new-order', { key: e.id, repeat: 3 });
                    this.showToast(`Pesanan Masuk #${e.number} (${e.table})`, 'warning', true);
                    this.blinkTitle(`🔔 Pesanan Baru #${e.number}`);
                })
                .listen('.order.paid', (e) => {
                    sound.play('payment-success', { key: e.id });
                    this.showToast(`Pembayaran #${e.number} Berhasil`, 'success');
                })
                .listen('.order.status', (e) => {
                    if (e.status === 'ready') {
                        sound.play('ready', { key: e.id });
                        this.showToast(`Pesanan #${e.number} Siap Disajikan (${e.table})`, 'info');
                    }
                });
        }

        // Barista station channel
        if (['barista', 'manager', 'bumk_staff'].includes(this.currentRole)) {
            window.Echo.private('bar')
                .listen('.order.confirmed', (e) => {
                    sound.play('ticket', { key: e.id });
                    this.showToast(`Tiket Minuman Baru: #${e.number} (${e.table})`, 'ticket');
                    // Refresh Livewire component if on station page
                    if (window.Livewire) {
                        window.Livewire.dispatch('refreshStation');
                    }
                });
        }

        // Kitchen station channel
        if (['kitchen', 'manager', 'bumk_staff'].includes(this.currentRole)) {
            window.Echo.private('kitchen')
                .listen('.order.confirmed', (e) => {
                    sound.play('ticket', { key: e.id });
                    this.showToast(`Tiket Dapur Baru: #${e.number} (${e.table})`, 'ticket');
                    if (window.Livewire) {
                        window.Livewire.dispatch('refreshStation');
                    }
                });
        }
    }

    setupConnectionMonitoring() {
        if (!window.Echo || !window.Echo.connector || !window.Echo.connector.pusher) return;

        const pusher = window.Echo.connector.pusher;
        pusher.connection.bind('connected', () => {
            this.isConnected = true;
            this.updateConnectionStatus('online');
        });

        pusher.connection.bind('disconnected', () => {
            this.isConnected = false;
            this.updateConnectionStatus('polling');
        });

        pusher.connection.bind('error', () => {
            this.isConnected = false;
            this.updateConnectionStatus('polling');
        });
    }

    /**
     * Fallback polling every 10 seconds (DESIGN.md Section 5.3 Rule #5).
     */
    startFallbackPolling() {
        this.pollTimer = setInterval(async () => {
            try {
                const response = await fetch('/api/orders/pending', {
                    headers: { 'Accept': 'application/json' },
                    credentials: 'same-origin',
                });
                if (!response.ok) return;

                const data = await response.json();
                const orders = data.orders || [];

                for (const ord of orders) {
                    if (!this.seenOrderIds.has(ord.id)) {
                        this.seenOrderIds.add(ord.id);

                        // If not connected via websocket, sound from polling
                        if (!this.isConnected) {
                            if (ord.status === 'confirmed') {
                                sound.play('ticket', { key: ord.id });
                                this.showToast(`Tiket Baru (Polling): #${ord.number}`, 'ticket');
                            } else if (ord.status === 'ready') {
                                sound.play('ready', { key: ord.id });
                                this.showToast(`Pesanan Siap: #${ord.number}`, 'info');
                            }
                        }
                    }
                }
            } catch (err) {
                // Network down
                if (!this.isConnected) {
                    this.updateConnectionStatus('offline');
                }
            }
        }, 10000);
    }

    updateConnectionStatus(status) {
        const dot = document.getElementById('connection-dot');
        const text = document.getElementById('connection-text');
        if (!dot || !text) return;

        if (status === 'online') {
            dot.className = 'w-2 h-2 rounded-full bg-emerald-500';
            text.innerText = 'WebSocket Aktif';
        } else if (status === 'polling') {
            dot.className = 'w-2 h-2 rounded-full bg-amber-500 animate-pulse';
            text.innerText = 'Polling 10s';
        } else {
            dot.className = 'w-2 h-2 rounded-full bg-red-500';
            text.innerText = 'Terputus';
        }
    }

    showToast(message, type = 'info', persistent = false) {
        let container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            container.className = 'fixed top-4 right-4 z-50 flex flex-col gap-2 max-w-sm w-full pointer-events-none';
            document.body.appendChild(container);
        }

        const toast = document.createElement('div');
        toast.className = `p-3.5 rounded-xl shadow-lg border text-xs font-semibold flex items-center justify-between gap-3 pointer-events-auto transition-all duration-300 transform translate-y-2 opacity-0 bg-white ${
            type === 'warning' ? 'border-amber-300 bg-amber-50 text-amber-900' :
            type === 'success' ? 'border-emerald-300 bg-emerald-50 text-emerald-900' :
            type === 'ticket' ? 'border-blue-300 bg-blue-50 text-blue-900' :
            'border-neutral-200 text-neutral-800'
        }`;

        toast.innerHTML = `
            <div class="flex items-center gap-2">
                <span>🔔</span>
                <span>${message}</span>
            </div>
            <button class="px-2 py-1 rounded bg-black/10 hover:bg-black/20 text-[11px]" onclick="this.parentElement.remove()">Tutup</button>
        `;

        container.appendChild(toast);
        setTimeout(() => {
            toast.classList.remove('translate-y-2', 'opacity-0');
        }, 50);

        if (!persistent) {
            setTimeout(() => {
                toast.classList.add('opacity-0', 'translate-y-2');
                setTimeout(() => toast.remove(), 300);
            }, 4000);
        }
    }

    blinkTitle(newTitle) {
        const originalTitle = document.title;
        let isOriginal = false;
        let count = 0;
        const interval = setInterval(() => {
            document.title = isOriginal ? originalTitle : newTitle;
            isOriginal = !isOriginal;
            count++;
            if (count > 8) {
                clearInterval(interval);
                document.title = originalTitle;
            }
        }, 800);
    }
}

export const realtime = new RealtimeNotificationSystem();
window.umcoRealtime = realtime;
