import type { MeetingRulesSummary, QuorumStatus } from '~/types';

/**
 * Plain-English description of the meeting rules used (weighted votes, proxies,
 * quorum), for the printable report, the results CSV and the certificate.
 */

const n = (x: number | undefined | null) => (x ?? 0).toLocaleString('en-NG');

/** "50% of members (60 of 120)" / "60 votes by weight". */
export function quorumRequirement(q: QuorumStatus): string {
    const unit = q.basis === 'weight' ? 'votes by weight' : 'members';
    return q.type === 'percent'
        ? `${q.value}% of ${q.basis === 'weight' ? 'all votes (by weight)' : 'members'}, i.e. ${n(q.required)} of ${n(q.total)} ${unit}`
        : `${n(q.required)} ${unit}`;
}

/** The quorum outcome: reached, not reached (final) or still open. */
export function quorumVerdict(q: QuorumStatus, final: boolean): { ok: boolean; text: string } {
    const unit = q.basis === 'weight' ? 'votes by weight' : 'members';
    const got = `${n(q.achieved)} of ${n(q.required)} ${unit} needed`;
    if (q.met) return { ok: true, text: `Quorum reached: ${got}.` };
    return final
        ? { ok: false, text: `Quorum NOT reached: ${got}. Under the meeting rules, this result is not valid.` }
        : { ok: false, text: `Quorum not reached yet: ${got}.` };
}

/** One line per rule in force. */
export function describeRules(r: MeetingRulesSummary | null | undefined): string[] {
    if (!r) return [];
    const lines: string[] = [];
    if (r.weighted) {
        lines.push(`Weighted votes: each ballot counted by the member’s weight (shares or units)`
            + (r.total_weight ? `; ${n(r.voted_weight)} of ${n(r.total_weight)} votes were cast.` : '.'));
    }
    if (r.proxies) {
        lines.push(`Proxy voting allowed (up to ${r.max_proxies ?? 1} per member); `
            + `${n(r.proxy_votes)} member${r.proxy_votes === 1 ? '' : 's'} voted by proxy.`);
    }
    if (r.quorum) lines.push(`Quorum: ${quorumRequirement(r.quorum)}.`);
    return lines;
}
