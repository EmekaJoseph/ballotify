/**
 * Icons appear in the admin area (dashboard, reports) but not on public pages.
 * Shared components (stat cards, empty states, dialogs...) check this.
 */
export function useAdminIcons() {
    const route = useRoute();
    return computed(() => /^\/(dashboard|report|ballot-sheet)(\/|$)/.test(route.path));
}
