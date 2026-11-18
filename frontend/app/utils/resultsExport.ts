import type { BallotEvent, EventResults, ResultCategory } from '~/types';

const STATUS_LABEL = { upcoming: 'Not started', active: 'Voting open (provisional)', completed: 'Final' } as const;

export const categoryTotal = (cat: ResultCategory) => cat.candidates.reduce((n, c) => n + c.votes, 0);

/** Winners of a category: everyone tied on the top score (empty when nobody has votes). */
export function categoryWinners(cat: ResultCategory) {
    const top = Math.max(0, ...cat.candidates.map(c => c.votes));
    return top > 0 ? cat.candidates.filter(c => c.votes === top) : [];
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
        ['Voting style', event?.selection_mode === 'multiple' ? 'Multiple choice' : 'One per category'],
        ['Ballots cast', stats.voted],
        ['Registered voters', stats.total_voters],
        ['Turnout', stats.total_voters ? `${percent(stats.voted, stats.total_voters)}%` : ''],
        ['Changes after voting began', results.integrity?.changes_during_voting ?? 0],
        ['Exported', new Date().toLocaleString()],
        [],
        ['Category', 'Rank', 'Candidate', 'Description', 'Votes', 'Share of category votes', 'Result'],
    ];
    for (const cat of results.categories) {
        const total = categoryTotal(cat);
        const winners = categoryWinners(cat);
        const tie = winners.length > 1;
        const final = results.event.status === 'completed';
        cat.candidates.forEach((c, i) => {
            const isTop = winners.includes(c);
            rows.push([
                cat.name, i + 1, c.name, c.description ?? '', c.votes, total ? `${percent(c.votes, total)}%` : '0%',
                isTop ? (tie ? 'Tied' : final ? 'Winner' : 'Leading') : '',
            ]);
        });
        if (!cat.candidates.length) rows.push([cat.name, '', '(no candidates)', '', '', '', '']);
    }
    return toCsv(rows);
}
