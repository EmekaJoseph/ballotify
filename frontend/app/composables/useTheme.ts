export type ThemePref = 'system' | 'light' | 'dark';

const STORAGE_KEY = 'theme';

function readPref(): ThemePref {
    try {
        const v = localStorage.getItem(STORAGE_KEY);
        return v === 'light' || v === 'dark' ? v : 'system';
    } catch {
        return 'system';
    }
}

function systemDark(): boolean {
    return typeof window !== 'undefined' && window.matchMedia?.('(prefers-color-scheme: dark)').matches;
}

/** Apply to <html>: our tokens use data-theme, Bootstrap uses data-bs-theme. */
function apply(pref: ThemePref) {
    const dark = pref === 'dark' || (pref === 'system' && systemDark());
    const el = document.documentElement;
    el.classList.add('theme-switching');
    el.dataset.theme = dark ? 'dark' : 'light';
    el.setAttribute('data-bs-theme', dark ? 'dark' : 'light');
    document.querySelector('meta[name="theme-color"]')?.setAttribute('content', dark ? '#0a1019' : '#0e7c77');
    requestAnimationFrame(() => el.classList.remove('theme-switching'));
    return dark;
}

/** Light / dark / follow-the-system theme, remembered per browser. */
export function useTheme() {
    const pref = useState<ThemePref>('theme-pref', () => (import.meta.client ? readPref() : 'system'));
    const isDark = useState<boolean>('theme-dark', () => false);

    function set(next: ThemePref) {
        pref.value = next;
        try { localStorage.setItem(STORAGE_KEY, next); } catch { /* private mode: still applies for this visit */ }
        isDark.value = apply(next);
    }

    return { pref, isDark, set, init: () => { isDark.value = apply(pref.value); } };
}
