/** Whether the ballot layout shows "Powered by Ballotify" (Free events). Pages set it once loaded. */
export function useBallotBranding() {
    return useState<boolean>('ballot-branding', () => true);
}
