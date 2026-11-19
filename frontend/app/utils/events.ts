import type { BallotEvent } from '~/types';

/** A code-voting event that has no voters yet: a draft (doesn't count as a live event). */
export function isDraft(e: Pick<BallotEvent, 'voting_mode' | 'voters_count' | 'status'>): boolean {
    return e.voting_mode !== 'paid' && (e.voters_count ?? 0) === 0 && e.status !== 'completed';
}
