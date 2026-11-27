/**
 * Starting a new event. There are no plans or limits here; a new account only has
 * to be approved by the admin first (the dashboard shows that, and the server refuses
 * event creation until then).
 */
export function useNewEvent() {
    async function startNewEvent() {
        await navigateTo('/dashboard/events/create');
    }
    return { startNewEvent };
}
