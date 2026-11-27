import { ref } from 'vue';
import { messages, type MessageKey } from '~/i18n/messages';

/**
 * Languages of the public voting pages (vote, results, nominate). The
 * organiser picks the event's default; voters can switch with the picker in
 * the header, and their choice is remembered on this device. Everywhere else
 * (dashboard, landing) the app stays in English.
 */
export const LANGUAGES = [
    { code: 'en', name: 'English' },
    { code: 'yo', name: 'Yorùbá' },
    { code: 'ha', name: 'Hausa' },
    { code: 'ig', name: 'Igbo' },
    { code: 'fr', name: 'Français' },
] as const;
export type Lang = (typeof LANGUAGES)[number]['code'];

const CODES: readonly string[] = LANGUAGES.map(l => l.code);
const STORAGE_KEY = 'ballot-lang';
export const isLang = (v: unknown): v is Lang => typeof v === 'string' && CODES.includes(v);

/** The language public pages render in ('en' outside them). */
export const ballotLang = ref<Lang>('en');
let eventDefault: Lang | null = null;

function storedLang(): Lang | null {
    try {
        const v = localStorage.getItem(STORAGE_KEY);
        return isLang(v) ? v : null;
    } catch {
        return null;
    }
}

/**
 * English by default. Another language only when the voter picked one with
 * the language button (remembered), or the organiser set one for the event.
 */
function resolve(): Lang {
    return storedLang() ?? eventDefault ?? 'en';
}

/** Called by the ballot layout when a public page opens / closes. */
export function enterBallotPages() {
    eventDefault = null;
    ballotLang.value = resolve();
}
export function leaveBallotPages() {
    eventDefault = null;
    ballotLang.value = 'en';
}

/** Pages call this once the event loads (its organiser-chosen default language). */
export function setEventLanguage(code: unknown) {
    eventDefault = isLang(code) ? code : null;
    ballotLang.value = resolve();
}

/** The voter picked a language: use and remember it. */
export function setBallotLang(code: Lang) {
    ballotLang.value = code;
    try { localStorage.setItem(STORAGE_KEY, code); } catch { /* private mode */ }
}

/** Translate a key for the current public-page language, filling {placeholders}. */
export function t(key: MessageKey, params?: Record<string, string | number>): string {
    const text = messages[ballotLang.value]?.[key] ?? messages.en[key] ?? key;
    return params ? text.replace(/\{(\w+)\}/g, (m, name: string) => (name in params ? String(params[name]) : m)) : text;
}

/** Plural form: `${base}.one` for 1, `${base}.other` otherwise; {n} is the count. */
export function tn(base: string, n: number, params: Record<string, string | number> = {}): string {
    return t(`${base}.${n === 1 ? 'one' : 'other'}` as MessageKey, { n: n.toLocaleString(), ...params });
}
