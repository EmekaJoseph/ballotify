/**
 * Spotting voters who may be on the list twice. Never blocking: two real people
 * can share a name, and families often share a phone. The same person twice
 * would get two codes, so the organiser is warned and decides.
 *
 *   same  = same name AND same email or phone: almost certainly one person
 *   email = same email address
 *   phone = same phone number (could be a shared family phone)
 *   name  = same name only (could be two people)
 */
export type DuplicateKind = 'same' | 'email' | 'phone' | 'name';

export interface ContactLike { name?: string | null; email?: string | null; phone?: string | null }

export interface DuplicateMatch { kind: DuplicateKind; with: string }

/** "Ada  OBI", "obi ada", "Adá Obi." all give "ada obi". */
export function nameKey(name?: string | null): string {
    return (name ?? '').toLowerCase().normalize('NFKD').replace(/[̀-ͯ]/g, '')
        .replace(/[^\p{L}\p{N}\s]/gu, ' ').split(/\s+/).filter(Boolean).sort().join(' ');
}
export function emailKey(email?: string | null): string {
    return (email ?? '').trim().toLowerCase();
}
/** Last 10 digits, so 0801 234 5678 and +234 801 234 5678 match. */
export function phoneKey(phone?: string | null): string {
    const d = (phone ?? '').replace(/\D/g, '');
    return d.length >= 7 ? d.slice(-10) : '';
}

const RANK: Record<DuplicateKind, number> = { same: 0, email: 1, phone: 2, name: 3 };

/** How two contacts look alike (strongest reason), or null. */
export function compareContacts(a: ContactLike, b: ContactLike): DuplicateKind | null {
    const n = nameKey(a.name) !== '' && nameKey(a.name) === nameKey(b.name);
    const e = emailKey(a.email) !== '' && emailKey(a.email) === emailKey(b.email);
    const p = phoneKey(a.phone) !== '' && phoneKey(a.phone) === phoneKey(b.phone);
    if (n && (e || p)) return 'same';
    if (e) return 'email';
    if (p) return 'phone';
    if (n) return 'name';
    return null;
}

/**
 * For each item, the others it looks like (strongest first). Uses indexes by
 * name / email / phone, so a list of 10,000 is quick.
 */
export function findDuplicates<T extends ContactLike>(items: T[], label: (item: T, index: number) => string): Map<number, DuplicateMatch[]> {
    const index = new Map<string, number[]>();
    const add = (key: string, i: number) => { if (key) index.set(key, [...(index.get(key) ?? []), i]); };
    items.forEach((it, i) => {
        add('n:' + nameKey(it.name), i);
        add('e:' + emailKey(it.email), i);
        add('p:' + phoneKey(it.phone), i);
    });
    const out = new Map<number, DuplicateMatch[]>();
    items.forEach((it, i) => {
        const others = new Set<number>();
        for (const key of ['n:' + nameKey(it.name), 'e:' + emailKey(it.email), 'p:' + phoneKey(it.phone)]) {
            if (key.length > 2) for (const j of index.get(key) ?? []) if (j !== i) others.add(j);
        }
        const matches: DuplicateMatch[] = [];
        for (const j of others) {
            const kind = compareContacts(it, items[j]!);
            if (kind) matches.push({ kind, with: label(items[j]!, j) });
        }
        if (matches.length) out.set(i, matches.sort((a, b) => RANK[a.kind] - RANK[b.kind]));
    });
    return out;
}

/** For each item, the entries of `list` it looks like (indexed: quick for big lists). */
export function crossMatches<A extends ContactLike, B extends ContactLike>(items: A[], list: B[], label: (entry: B) => string): Map<number, DuplicateMatch[]> {
    const index = new Map<string, number[]>();
    const add = (key: string, j: number) => { if (key.length > 2) index.set(key, [...(index.get(key) ?? []), j]); };
    list.forEach((e, j) => { add('n:' + nameKey(e.name), j); add('e:' + emailKey(e.email), j); add('p:' + phoneKey(e.phone), j); });
    const out = new Map<number, DuplicateMatch[]>();
    items.forEach((it, i) => {
        const cands = new Set<number>();
        for (const key of ['n:' + nameKey(it.name), 'e:' + emailKey(it.email), 'p:' + phoneKey(it.phone)]) {
            if (key.length > 2) for (const j of index.get(key) ?? []) cands.add(j);
        }
        const matches: DuplicateMatch[] = [];
        for (const j of cands) {
            const kind = compareContacts(it, list[j]!);
            if (kind) matches.push({ kind, with: label(list[j]!) });
        }
        if (matches.length) out.set(i, matches.sort((a, b) => RANK[a.kind] - RANK[b.kind]));
    });
    return out;
}

export const sortMatches = (m: DuplicateMatch[]) => m.sort((a, b) => RANK[a.kind] - RANK[b.kind]);

/** Matches of one contact against a list (e.g. the voter form against the voter list). */
export function matchesIn<T extends ContactLike>(contact: ContactLike, list: T[], label: (item: T) => string): DuplicateMatch[] {
    return list.map(item => ({ kind: compareContacts(contact, item), with: label(item) }))
        .filter((m): m is DuplicateMatch => m.kind !== null)
        .sort((a, b) => RANK[a.kind] - RANK[b.kind]);
}

/** "Same name and phone as Ada Obi" etc. */
export function describeMatch(m: DuplicateMatch): string {
    switch (m.kind) {
        case 'same': return `Same name and contact as ${m.with}: probably the same person`;
        case 'email': return `Same email as ${m.with}`;
        case 'phone': return `Same phone as ${m.with} (fine if they share a phone)`;
        default: return `Same name as ${m.with} (fine if they’re two people)`;
    }
}
