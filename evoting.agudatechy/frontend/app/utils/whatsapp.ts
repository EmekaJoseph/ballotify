/**
 * WhatsApp "click to chat" links. They open WhatsApp (app or web) with the
 * message filled in; the organiser presses Send. Free: no SMS costs.
 */
export function whatsappLink(phone: string | null | undefined, text: string): string {
    const digits = (phone ?? '').replace(/\D/g, '');
    return `https://wa.me/${digits}?text=${encodeURIComponent(text)}`;
}

/** The personal message with a voter's code. */
export function voterCodeMessage(opts: { eventName: string; voterName?: string | null; code: string; voteUrl: string; endsAt?: string | null; reminder?: boolean }): string {
    const hello = opts.voterName ? `Hello ${opts.voterName.trim().split(/\s+/)[0]},` : 'Hello,';
    const closes = opts.endsAt ? `\nVoting closes ${formatDate(opts.endsAt, true)}.` : '';
    const lead = opts.reminder
        ? `${hello} a reminder that you haven't voted in *${opts.eventName}* yet.`
        : `${hello} you're invited to vote in *${opts.eventName}*.`;
    return `${lead}\n\nYour voting code: *${opts.code}*\nVote here: ${opts.voteUrl}\n\nThe code works once, so please keep it private.${closes}`;
}
