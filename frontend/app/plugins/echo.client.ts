import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

/**
 * Live results over Laravel Reverb (self-hosted websockets).
 *
 * The socket is opened only when a page asks for it (the public results page)
 * and closed again when that page releases it, so other pages never open a
 * connection. Disabled when no Reverb key is configured or the server can't be
 * reached: the results page then falls back to refreshing every 15 seconds.
 */
export interface LiveConnection {
    /** Returns a connected Echo client, or null when live updates are off. */
    acquire(): Echo<'reverb'> | null;
    /** Call once per acquire(); the socket closes when nobody is using it. */
    release(): void;
}

export default defineNuxtPlugin(() => {
    const config = useRuntimeConfig();
    const key = String(config.public.reverbKey || '');

    let echo: Echo<'reverb'> | null = null;
    let users = 0;

    const live: LiveConnection = {
        acquire() {
            if (!key) return null;
            if (!echo) {
                try {
                    // Reverb speaks the Pusher protocol; pusher-js is the client transport.
                    (window as unknown as { Pusher: typeof Pusher }).Pusher = Pusher;
                    const scheme = String(config.public.reverbScheme || 'http');
                    const port = Number(config.public.reverbPort) || (scheme === 'https' ? 443 : 80);
                    echo = new Echo({
                        broadcaster: 'reverb',
                        key,
                        wsHost: String(config.public.reverbHost || window.location.hostname),
                        wsPort: port,
                        wssPort: port,
                        forceTLS: scheme === 'https',
                        enabledTransports: ['ws', 'wss'],
                    });
                } catch (e) {
                    console.warn('Live updates unavailable:', e);
                    return null;
                }
            }
            users++;
            return echo;
        },
        release() {
            users = Math.max(0, users - 1);
            if (users === 0 && echo) {
                try { echo.disconnect(); } catch { /* already closed */ }
                echo = null;
            }
        },
    };

    return { provide: { live } };
});
