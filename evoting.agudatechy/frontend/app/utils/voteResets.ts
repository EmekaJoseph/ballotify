import type { VoteReset } from '~/types';

/**
 * "Votes were reset once during a test run (25 Nov 2026 by Ada Obi: 12 voters' votes erased)."
 * For the organiser's report and the certificate (English, like the rest of those documents).
 */
export function describeResets(resets?: VoteReset[] | null): string {
    if (!resets?.length) return '';
    const times = resets.length === 1 ? 'once' : `${resets.length} times`;
    const each = resets.map((r) => {
        const when = r.at ? formatDate(r.at, true) : 'unknown date';
        const who = r.by ? ` by ${r.by}` : '';
        const what = r.voters === 1 ? '1 voter’s vote erased' : `${r.voters} voters’ votes erased`;
        return `${when}${who}: ${what}`;
    });
    return `Votes were reset ${times} during a test run, before these results (${each.join('; ')}). The voters could then vote again with the same codes.`;
}
