import type { BallotEvent } from '~/types';

/** An event that has no voters yet: a draft. */
export function isDraft(e: Pick<BallotEvent, 'voters_count' | 'status'>): boolean {
    return (e.voters_count ?? 0) === 0 && e.status !== 'completed';
}
