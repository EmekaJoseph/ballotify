/**
 * "New event" entry point used by every create button.
 * If the account has used its free open-event allowance, a friendly modal
 * (<EventLimitModal /> in the dashboard layout) explains why and what to do,
 * instead of letting the organiser fill a form that will be refused.
 */
export function useNewEvent() {
    const { plans, loadPlans } = useBilling();
    const limitOpen = useState<boolean>('event-limit-open', () => false);

    const quota = computed(() => plans.value?.event_quota ?? null);
    /** Known to be blocked (used to show a lock on the buttons). Unknown = allowed. */
    const blocked = computed(() => quota.value?.can_create === false);

    /** Fetch the latest allowance (events may have been created, closed or upgraded). */
    function refreshQuota() {
        return loadPlans(true);
    }

    function showLimit() {
        limitOpen.value = true;
    }

    async function startNewEvent() {
        if (blocked.value) {
            showLimit();
            refreshQuota(); // in case it changed; the modal updates itself
            return;
        }
        await navigateTo('/dashboard/events/create');
    }

    return { quota, blocked, limitOpen, refreshQuota, showLimit, startNewEvent };
}
