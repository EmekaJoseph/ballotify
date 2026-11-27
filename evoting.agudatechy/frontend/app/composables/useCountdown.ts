/** Live "2d 4h 12m" style countdown to a date. Returns '' when there is no target or it has passed. */
export function useCountdown(target: MaybeRefOrGetter<string | null | undefined>) {
    const now = ref(Date.now());
    let timer: ReturnType<typeof setInterval> | undefined;

    onMounted(() => { timer = setInterval(() => { now.value = Date.now(); }, 1000); });
    onBeforeUnmount(() => clearInterval(timer));

    const msLeft = computed(() => {
        const t = toValue(target);
        if (!t) return 0;
        const ms = new Date(t).getTime() - now.value;
        return Number.isFinite(ms) ? Math.max(0, ms) : 0;
    });

    const label = computed(() => {
        const ms = msLeft.value;
        if (ms <= 0) return '';
        const s = Math.floor(ms / 1000);
        const d = Math.floor(s / 86400);
        const h = Math.floor((s % 86400) / 3600);
        const m = Math.floor((s % 3600) / 60);
        const sec = s % 60;
        if (d > 0) return `${d}d ${h}h ${m}m`;
        if (h > 0) return `${h}h ${m}m ${sec}s`;
        return `${m}m ${String(sec).padStart(2, '0')}s`;
    });

    return { msLeft, label };
}
