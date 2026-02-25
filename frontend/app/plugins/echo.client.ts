// @ts-nocheck
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

export default defineNuxtPlugin((nuxtApp) => {
    const config = useRuntimeConfig();

    if (process.client) {
        window.Pusher = Pusher;

        const echo = new Echo({
            broadcaster: 'pusher',
            key: config.public.pusherKey,
            cluster: config.public.pusherCluster,
            forceTLS: true
        });

        return {
            provide: {
                echo: echo
            }
        };
    }
});
