import type { Candidate, EventResults } from '~/types';

/** Sample data for the public demo pages. No API calls involved. */
export const DEMO_EVENT = {
    name: 'Riverside Student Union Elections',
    description: 'Choose the students who will lead the union next year.',
    categories: [
        { id: 1, name: 'President' },
        { id: 2, name: 'Vice President' },
        { id: 3, name: 'Welfare Officer' },
    ],
};

const people: [number, number, string][] = [
    [1, 1, 'Amara Okafor'], [2, 1, 'Daniel Mensah'], [3, 1, 'Sofia Lindqvist'],
    [4, 2, 'Kwame Asante'], [5, 2, 'Priya Raman'],
    [6, 3, 'Lucas Moreau'], [7, 3, 'Zainab Bello'], [8, 3, 'Ethan Park'],
];

export const DEMO_CANDIDATES: Candidate[] = people.map(([id, category_id, name]) => ({
    id, category_id, name, image_path: null, image_url: null,
}));

const startVotes: Record<number, number> = { 1: 412, 2: 368, 3: 205, 4: 501, 5: 489, 6: 290, 7: 344, 8: 178 };

export function demoResults(extra: Record<number, number> = {}): EventResults {
    const votes = (id: number) => (startVotes[id] ?? 0) + (extra[id] ?? 0);
    const categories = DEMO_EVENT.categories.map((cat) => {
        const candidates = DEMO_CANDIDATES
            .filter(c => c.category_id === cat.id)
            .map(c => ({ id: c.id, name: c.name, image_url: null, votes: votes(c.id) }))
            .sort((a, b) => b.votes - a.votes);
        return { id: cat.id, name: cat.name, candidates, leader: candidates[0] ?? null };
    });
    const totalVotes = categories.reduce((s, c) => s + c.candidates.reduce((a, b) => a + b.votes, 0), 0);
    const voted = Math.max(...categories.map(c => c.candidates.reduce((a, b) => a + b.votes, 0)));
    return {
        event: { id: 0, name: DEMO_EVENT.name, status: 'active', starts_at: null, ends_at: new Date(Date.now() + 3 * 3600_000).toISOString() },
        stats: { total_votes: totalVotes, total_voters: 1400, voted, total_candidates: DEMO_CANDIDATES.length },
        categories,
    };
}
