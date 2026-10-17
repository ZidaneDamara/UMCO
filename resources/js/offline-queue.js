/**
 * UMCO House — Offline Queue & Network Status Manager (DESIGN.md Section 9)
 * Handles offline detection, local cart saving, and syncing pending offline orders.
 */

class OfflineQueueManager {
    constructor() {
        this.storageKey = 'umco_offline_orders';
        this.isOnline = navigator.onLine;
        this.init();
    }

    init() {
        window.addEventListener('online', () => this.handleNetworkChange(true));
        window.addEventListener('offline', () => this.handleNetworkChange(false));
        this.updateUI();

        // Register Service Worker if supported
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js').then((reg) => {
                console.log('[PWA] Service Worker registered:', reg.scope);
            }).catch((err) => {
                console.warn('[PWA] Service Worker registration failed:', err);
            });
        }
    }

    handleNetworkChange(online) {
        this.isOnline = online;
        this.updateUI();

        if (online) {
            console.log('[OfflineQueue] Back online, syncing queue...');
            this.syncQueue();
        }
    }

    updateUI() {
        const dot = document.getElementById('connection-dot');
        const text = document.getElementById('connection-text');

        if (!dot || !text) return;

        if (this.isOnline) {
            dot.className = 'w-2 h-2 rounded-full bg-emerald-500';
            const pendingCount = this.getPendingOrders().length;
            if (pendingCount > 0) {
                text.textContent = `Online (${pendingCount} antrean tersinkron)`;
            } else {
                text.textContent = 'Online / Terhubung';
            }
        } else {
            dot.className = 'w-2 h-2 rounded-full bg-rose-500 animate-ping';
            text.textContent = 'Offline: Pesanan disimpan di perangkat';
        }
    }

    getPendingOrders() {
        try {
            return JSON.parse(localStorage.getItem(this.storageKey) || '[]');
        } catch {
            return [];
        }
    }

    enqueueOrder(orderData) {
        const queue = this.getPendingOrders();
        const idempotencyKey = 'off_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        const orderRecord = {
            idempotency_key: idempotencyKey,
            data: orderData,
            enqueued_at: new Date().toISOString(),
        };

        queue.push(orderRecord);
        localStorage.setItem(this.storageKey, JSON.stringify(queue));
        this.updateUI();
        return idempotencyKey;
    }

    async syncQueue() {
        const queue = this.getPendingOrders();
        if (queue.length === 0) return;

        console.log(`[OfflineQueue] Syncing ${queue.length} pending orders...`);
        // If Livewire POS component is active on page, notify it
        if (window.Livewire) {
            window.Livewire.dispatch('sync-offline-queue', { orders: queue });
        }
    }

    clearQueue() {
        localStorage.removeItem(this.storageKey);
        this.updateUI();
    }
}

window.umcoOffline = new OfflineQueueManager();
