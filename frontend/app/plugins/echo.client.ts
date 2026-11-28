import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import api from '~/api';

/**
 * Live results over websockets: Pusher (hosted, nothing to run on the server) when
 * NUXT_PUBLIC_PUSHER_KEY is set, else Laravel Reverb (self-hosted) when
 * NUXT_PUBLIC_REVERB_KEY is set, else off. The backend's BROADCAST_CONNECTION must
 * name the same one (pusher / reverb).
 *
 * The socket is opened only when a page asks for it (the public results page)
 * and closed again when that page releases it, so other pages never open a
 * connection. Off, or unreachable: the results page refreshes every 15 seconds.
 */

type Listenable = { listen(event: string, callback: (...args: unknown[]) => void): unknown };

/** What the results pages use: the same for Pusher and Reverb. */
export interface LiveClient {
    channel(name: string): Listenable;
    /** Organiser-only channels: the API checks the signed-in user (POST /broadcasting/auth). */
    private(name: string): Listenable;
    leave(name: string): void;
    disconnect(): void;
}

export interface LiveConnection {
    /** Returns a connected client, or null when live updates are off. */
    acquire(): LiveClient | null;
    /** Call once per acquire(); the socket closes when nobody is using it. */
    release(): void;
}

export default defineNuxtPlugin(() => {
    const config = useRuntimeConfig();
    const pusherKey = String(config.public.pusherKey || '');
    const reverbKey = String(config.public.reverbKey || '');

    let echo: LiveClient | null = null;
    let users = 0;

    /**
     * Private channels: ask our API, through the shared axios instance so the
     * current Bearer token (and the API base URL) are always the right ones.
     */
    const authorizer = (channel: { name: string }) => ({
        authorize: (socketId: string, callback: (error: Error | null, data: { auth: string } | null) => void) => {
            api.post('/broadcasting/auth', { socket_id: socketId, channel_name: channel.name })
                .then(res => callback(null, res.data as { auth: string }))
                .catch(err => callback(err instanceof Error ? err : new Error('Live updates not allowed'), null));
        },
    });

    /** A new client for whichever service is configured (Pusher first). */
    function connect(): LiveClient {
        // Both speak the Pusher protocol; pusher-js is the client transport.
        (window as unknown as { Pusher: typeof Pusher }).Pusher = Pusher;
        if (pusherKey) {
            return new Echo({
                broadcaster: 'pusher',
                key: pusherKey,
                cluster: String(config.public.pusherCluster || 'mt1'),
                forceTLS: true,
                authorizer,
            }) as unknown as LiveClient;
        }
        const scheme = String(config.public.reverbScheme || 'http');
        const port = Number(config.public.reverbPort) || (scheme === 'https' ? 443 : 80);
        return new Echo({
            broadcaster: 'reverb',
            key: reverbKey,
            wsHost: String(config.public.reverbHost || window.location.hostname),
            wsPort: port,
            wssPort: port,
            forceTLS: scheme === 'https',
            enabledTransports: ['ws', 'wss'],
            authorizer,
        }) as unknown as LiveClient;
    }

    const live: LiveConnection = {
        acquire() {
            if (!pusherKey && !reverbKey) return null;
            if (!echo) {
                try {
                    echo = connect();
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
