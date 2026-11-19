/** Whether the ballot layout shows "Powered by Ballotify" (Free events). Pages set it once loaded. */
export function useBallotBranding() {
    return useState<boolean>('ballot-branding', () => true);
}

/** The ballot layout's header note: secret ballot (codes) or secure payments (paid voting). */
export function useBallotMode() {
    return useState<'codes' | 'paid'>('ballot-mode', () => 'codes');
}
