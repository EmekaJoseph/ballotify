import type { BallotEvent, EventResults, ResultCategory } from '~/types';

const STATUS_LABEL = { upcoming: 'Not started', active: 'Voting open (provisional)', completed: 'Final' } as const;

export const categoryTotal = (cat: ResultCategory) => cat.candidates.reduce((n, c) => n + c.votes, 0);

/** Winners of a category by its voting method (several = a tie; empty when nobody has votes). */
export function categoryWinners(cat: ResultCategory) {
    if (cat.winners) return cat.candidates.filter(c => cat.winners!.includes(c.id));
    const top = Math.max(0, ...cat.candidates.map(c => c.votes));
    return top > 0 ? cat.candidates.filter(c => c.votes === top) : [];
}

/**
 * What percentages are "of": the voters who answered the category. For choose-one
 * that equals the votes; for approval / pick-several it's the share of voters who
 * picked someone (so they can add up to more than 100%). Paid voting: the votes.
 */
export const categoryBase = (cat: ResultCategory) => (cat.ballots ? cat.ballots : categoryTotal(cat));
export const categoryShare = (votes: number, cat: ResultCategory) => percent(votes, categoryBase(cat));

export const isRanked = (cat: { method?: string }) => cat.method === 'ranked';
/** "Approval": a plurality category where voters may pick any number. */
export const isApproval = (cat: { method?: string; max_choices?: number | null }) => !isRanked(cat) && cat.max_choices === null;

/** Ranked choice: the deciding round, and a candidate's votes in it (0 if eliminated before). */
export function finalRound(cat: ResultCategory) {
    return cat.rounds?.length ? cat.rounds[cat.rounds.length - 1]! : null;
}
export function finalVotes(cat: ResultCategory, candidateId: number): number {
    return finalRound(cat)?.counts.find(c => c.id === candidateId)?.votes ?? 0;
}

/** How voters fill the category in, in words (organiser-facing, English). */
export function methodName(cat: { method?: string; max_choices?: number | null }): string {
    if (isRanked(cat)) return cat.max_choices ? `Ranked choice (up to ${cat.max_choices})` : 'Ranked choice';
    if (cat.max_choices === null) return 'Approval (pick any number)';
    return cat.max_choices === 1 || cat.max_choices === undefined ? 'Choose one' : `Pick up to ${cat.max_choices}`;
}

export function resultsStatusLabel(results: EventResults): string {
    return STATUS_LABEL[results.event.status];
}

/** Spreadsheet of the full tally, with an event summary above the table. */
export function resultsCsv(results: EventResults, event?: BallotEvent | null): string {
    const { stats } = results;
    const rows: (string | number | null)[][] = [
        ['Event', results.event.name],
        ['Status', resultsStatusLabel(results)],
        ['Voting window', formatWindow(results.event.starts_at, results.event.ends_at)],
        ['Voting', event?.voting_mode === 'paid' ? `Paid public voting (NGN ${event.vote_price_naira ?? 0} per vote)` : 'Secret ballot with one-time codes'],
        ['Ballots cast', stats.voted],
        ['Registered voters', stats.total_voters],
        ['Turnout', stats.total_voters ? `${percent(stats.voted, stats.total_voters)}%` : ''],
        ['Changes after voting began', results.integrity?.changes_during_voting ?? 0],
        ['Exported', new Date().toLocaleString()],
        [],
        ['Category', 'Method', 'Rank', 'Candidate', 'Description', 'Votes', 'Share of voters', 'Result'],
    ];
    for (const cat of results.categories) {
        const winners = categoryWinners(cat);
        const tie = winners.length > 1;
        const final = results.event.status === 'completed';
        const method = methodName(cat) + (isRanked(cat) ? ' · votes are first choices' : '');
        cat.candidates.forEach((c, i) => {
            const isTop = winners.includes(c);
            rows.push([
                cat.name, method, i + 1, c.name, c.description ?? '', c.votes, categoryBase(cat) ? `${categoryShare(c.votes, cat)}%` : '0%',
                isTop ? (tie ? 'Tied' : final ? 'Winner' : 'Leading') : '',
            ]);
        });
        if (!cat.candidates.length) rows.push([cat.name, method, '', '(no candidates)', '', '', '', '']);
        // Ranked choice: one line per round of the count.
        const name = (id: number) => cat.candidates.find(c => c.id === id)?.name ?? `#${id}`;
        (cat.rounds ?? []).forEach((r, i) => {
            const counts = r.counts.map(c => `${name(c.id)} ${c.votes}`).join(' · ');
            const out = r.eliminated.length ? ` · eliminated: ${r.eliminated.map(name).join(', ')}` : '';
            rows.push([cat.name, `Round ${i + 1}`, '', counts + out + (r.exhausted ? ` · no choices left: ${r.exhausted}` : ''), '', '', '', '']);
        });
    }
    return toCsv(rows);
}
