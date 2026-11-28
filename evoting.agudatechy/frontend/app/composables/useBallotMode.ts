/**
 * The ballot layout's header note: secret ballot (codes), nominations, or a
 * neutral note on other public pages.
 */
export type BallotMode = 'codes' | 'nominate' | 'other';
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
