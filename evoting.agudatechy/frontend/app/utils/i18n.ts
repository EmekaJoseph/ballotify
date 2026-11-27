import { messages, type MessageKey } from '~/i18n/messages';

/** Text of the public pages (vote, results, nominate). English only; {placeholders} are filled in. */
export function t(key: MessageKey, params?: Record<string, string | number>): string {
    const text = messages[key] ?? key;
    return params ? text.replace(/\{(\w+)\}/g, (m, name: string) => (name in params ? String(params[name]) : m)) : text;
}

/** Plural form: `${base}.one` for 1, `${base}.other` otherwise; {n} is the count. */
export function tn(base: string, n: number, params: Record<string, string | number> = {}): string {
    return t(`${base}.${n === 1 ? 'one' : 'other'}` as MessageKey, { n: n.toLocaleString(), ...params });
}
