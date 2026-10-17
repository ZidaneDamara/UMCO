// resources/js/sound-manager.js (DESIGN.md Section 5.3)

const SOUNDS = {
    'new-order': '/sounds/new-order.mp3',
    'ticket': '/sounds/ticket.mp3',
    'payment-success': '/sounds/payment-success.mp3',
    'ready': '/sounds/ready.mp3',
    'alert': '/sounds/alert.mp3',
};

class SoundManager {
    constructor() {
        this.enabled = localStorage.getItem('umco.sound.enabled') === '1';
        this.muted = localStorage.getItem('umco.sound.muted') === '1';
        this.volume = parseFloat(localStorage.getItem('umco.sound.volume') ?? '0.8');
        this.cache = {};
        this.played = new Set();
        this.timer = null;
        this.audioCtx = null;
    }

    /**
     * Unlock audio on first user interaction (button click/touch)
     * Solves modern browser autoplay restriction.
     */
    async unlock() {
        try {
            // Unlock standard Audio element
            const a = new Audio(SOUNDS['ticket']);
            a.volume = 0;
            await a.play();
            a.pause();

            // Unlock Web Audio API context for zero-latency fallback
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (AudioContext) {
                if (!this.audioCtx) this.audioCtx = new AudioContext();
                if (this.audioCtx.state === 'suspended') {
                    await this.audioCtx.resume();
                }
            }

            this.enabled = true;
            this.muted = false;
            localStorage.setItem('umco.sound.enabled', '1');
            localStorage.setItem('umco.sound.muted', '0');
            this.updateHeaderUI();
            return true;
        } catch (e) {
            console.warn('Audio unlock waiting for user gesture:', e);
            return false;
        }
    }

    /**
     * Play sound with deduplication and repeat options.
     */
    play(name, { key = null, repeat = 1, every = 8000 } = {}) {
        if (!this.enabled || this.muted) return;
        if (key && this.played.has(`${name}:${key}`)) return;
        if (key) this.played.add(`${name}:${key}`);

        // Try vibration API on mobile (Section 5.3)
        if ('vibrate' in navigator) {
            try {
                navigator.vibrate([100, 50, 100]);
            } catch (_) {}
        }

        const run = (n) => {
            if (this.muted) return;

            try {
                const a = this.cache[name] ??= new Audio(SOUNDS[name]);
                a.volume = this.volume;
                a.currentTime = 0;
                const promise = a.play();
                if (promise !== undefined) {
                    promise.catch(() => {
                        // Fallback to Web Audio synthesis if audio element is blocked
                        this.playWebAudioFallback(name);
                    });
                }
            } catch (err) {
                this.playWebAudioFallback(name);
            }

            if (n > 1) {
                this.timer = setTimeout(() => run(n - 1), every);
            }
        };

        run(repeat);
    }

    /**
     * Web Audio API synthesized fallback for 100% reliability.
     */
    playWebAudioFallback(name) {
        try {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (!AudioContext) return;
            if (!this.audioCtx) this.audioCtx = new AudioContext();
            if (this.audioCtx.state === 'suspended') this.audioCtx.resume();

            const now = this.audioCtx.currentTime;
            const osc = this.audioCtx.createOscillator();
            const gain = this.audioCtx.createGain();

            osc.type = 'sine';
            gain.gain.setValueAtTime(this.volume * 0.4, now);
            gain.gain.exponentialRampToValueAtTime(0.001, now + 0.5);

            const freqs = {
                'new-order': 587.33,
                'ticket': 880.00,
                'payment-success': 659.25,
                'ready': 523.25,
                'alert': 440.00,
            };

            osc.frequency.setValueAtTime(freqs[name] || 440, now);
            osc.connect(gain);
            gain.connect(this.audioCtx.destination);

            osc.start(now);
            osc.stop(now + 0.5);
        } catch (_) {}
    }

    stopRepeat() {
        if (this.timer) {
            clearTimeout(this.timer);
            this.timer = null;
        }
    }

    toggleMute() {
        this.muted = !this.muted;
        localStorage.setItem('umco.sound.muted', this.muted ? '1' : '0');
        this.updateHeaderUI();
        if (!this.muted && !this.enabled) {
            this.unlock();
        }
        return this.muted;
    }

    setVolume(val) {
        this.volume = Math.max(0, Math.min(1, parseFloat(val)));
        localStorage.setItem('umco.sound.volume', this.volume.toString());
    }

    updateHeaderUI() {
        const icon = document.getElementById('sound-icon');
        const text = document.getElementById('sound-text');
        const banner = document.getElementById('sound-activation-banner');

        if (banner) {
            banner.style.display = (this.enabled && !this.muted) ? 'none' : 'flex';
        }

        if (icon) {
            if (this.muted || !this.enabled) {
                icon.setAttribute('data-lucide', 'volume-x');
                icon.className = 'w-4 h-4 text-amber-500';
            } else {
                icon.setAttribute('data-lucide', 'volume-2');
                icon.className = 'w-4 h-4 text-emerald-600';
            }
        }

        if (text) {
            text.innerText = (this.muted || !this.enabled) ? 'Senyap' : 'Suara Aktif';
        }

        if (window.lucide) {
            window.lucide.createIcons();
        }
    }
}

export const sound = new SoundManager();
window.umcoSound = sound;
