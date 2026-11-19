/**
 * Free-plan live-event allowance. Drafts are unlimited; an event goes "live"
 * when it gets its first voter. When adding voters would exceed the allowance,
 * a friendly modal (<EventLimitModal /> in the dashboard layout) explains the
 * options instead of an error.
 */
export function useNewEvent() {
    const { plans, loadPlans } = useBilling();
    const limitOpen = useState<boolean>('event-limit-open', () => false);
    /** The event the organiser was trying to make live. */
    const limitEvent = useState<{ id: number; name: string } | null>('event-limit-event', () => null);

    const quota = computed(() => plans.value?.event_quota ?? null);

    /** Fetch the latest allowance (events may have been created, closed or upgraded). */
    function refreshQuota() {
        return loadPlans(true);
    }

    async function showLimit(event?: { id: number; name: string } | null) {
        limitEvent.value = event ?? null;
        limitOpen.value = true;
        await refreshQuota();
    }

    async function startNewEvent() {
        await navigateTo('/dashboard/events/create');
    }

    return { quota, limitOpen, limitEvent, refreshQuota, showLimit, startNewEvent };
}
