/**
 * Closes something after a stretch of no activity (taps, keys, scrolling,
 * mouse). A warning with a countdown shows for the last `warnMs`; any activity
 * resets it. Uses the wall clock, so a phone that slept or a tab left in the
 * background closes as soon as it's looked at again.
 */
export function useIdleTimeout(opts: {
    /** Only counts while this is true (e.g. a ballot is open). */
    active: () => boolean;
    idleMs: number;
    warnMs: number;
    onTimeout: () => void;
}) {
    const warning = ref(false);
    const secondsLeft = ref(0);
    let last = Date.now();
    let timer: ReturnType<typeof setInterval> | undefined;
    let lastMove = 0;

    function activity(e?: Event) {
        // Mouse movement is noisy: count it at most once a second.
        if (e?.type === 'mousemove') {
            const now = Date.now();
            if (now - lastMove < 1000) return;
            lastMove = now;
        }
        last = Date.now();
        warning.value = false;
    }

    function tick() {
        if (!opts.active()) {
            warning.value = false;
            last = Date.now();
            return;
        }
        const idle = Date.now() - last;
        if (idle >= opts.idleMs) {
            warning.value = false;
            last = Date.now();
            opts.onTimeout();
        } else if (idle >= opts.idleMs - opts.warnMs) {
            warning.value = true;
            secondsLeft.value = Math.max(1, Math.ceil((opts.idleMs - idle) / 1000));
        } else {
            warning.value = false;
        }
    }

    const EVENTS = ['pointerdown', 'keydown', 'touchstart', 'wheel', 'scroll', 'mousemove'] as const;
    onMounted(() => {
        EVENTS.forEach(ev => window.addEventListener(ev, activity, { passive: true, capture: true }));
        document.addEventListener('visibilitychange', tick);
        timer = setInterval(tick, 1000);
    });
    onBeforeUnmount(() => {
        EVENTS.forEach(ev => window.removeEventListener(ev, activity, { capture: true }));
        document.removeEventListener('visibilitychange', tick);
        clearInterval(timer);
    });

    /** "I'm still here". */
    const stay = () => activity();
    return { warning, secondsLeft, stay };
}
