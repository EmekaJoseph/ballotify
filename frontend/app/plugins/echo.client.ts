import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

/**
 * Live results over Laravel Reverb (self-hosted websockets). Disabled when no
 * Reverb key is configured or the server can't be reached: the results page
 * then falls back to refreshing every 15 seconds.
 */
export default defineNuxtPlugin(() => {
    const config = useRuntimeConfig();
    const key = String(config.public.reverbKey || '');

    let echo: Echo<'reverb'> | null = null;
    if (key) {
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
            echo = null;
        }
    }

    return { provide: { echo } };
});
