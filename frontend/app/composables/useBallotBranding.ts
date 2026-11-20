/** Whether the ballot layout shows "Powered by Ballotify" (Free events). Pages set it once loaded. */
export function useBallotBranding() {
    return useState<boolean>('ballot-branding', () => true);
}

/**
 * The ballot layout's header note: secret ballot (codes), secure payments
 * (paid voting), nominations, or a neutral note on other public pages.
 */
export type BallotMode = 'codes' | 'paid' | 'nominate' | 'other';
export function useBallotMode() {
    return useState<BallotMode>('ballot-mode', () => 'codes');
}

/** Set the header note for this page, and put the default back when leaving it. */
export function useBallotModeFor(mode: BallotMode) {
    const state = useBallotMode();
    state.value = mode;
    // Again once mounted: the previous page's clean-up may run after our setup.
    onMounted(() => { state.value = mode; });
    onBeforeUnmount(() => { if (state.value === mode) state.value = 'codes'; });
}
