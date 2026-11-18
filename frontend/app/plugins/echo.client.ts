import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

/**
 * Real-time updates (live results). Disabled automatically when no Pusher key
 * is configured: pages then fall back to polling.
 */
export default defineNuxtPlugin(() => {
    const config = useRuntimeConfig();
    const key = String(config.public.pusherKey || '');

    let echo: Echo<'pusher'> | null = null;
    if (key) {
        try {
            (window as unknown as { Pusher: typeof Pusher }).Pusher = Pusher;
            echo = new Echo({
                broadcaster: 'pusher',
                key,
                cluster: String(config.public.pusherCluster || 'mt1'),
                forceTLS: true,
            });
        } catch (e) {
            console.warn('Live updates unavailable:', e);
            echo = null;
        }
    }

    return { provide: { echo } };
});
