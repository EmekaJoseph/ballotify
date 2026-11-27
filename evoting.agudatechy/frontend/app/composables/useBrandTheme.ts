import type { Brand } from '~/types';

const VARS = ['--brand-50', '--brand-100', '--brand-200', '--brand-500', '--brand-600', '--brand-700'];

/**
 * Pro events: recolour the ballot / results pages with the organiser's accent
 * colour (derived shades via color-mix) for as long as the page is open.
 */
export function useBrandTheme(brand: () => Brand | null | undefined) {
    function apply(color: string | null | undefined) {
        if (import.meta.server) return;
        const root = document.documentElement.style;
        if (!color || !/^#[0-9a-f]{6}$/i.test(color)) {
            VARS.forEach(v => root.removeProperty(v));
            return;
        }
        root.setProperty('--brand-600', color);
        root.setProperty('--brand-500', `color-mix(in srgb, ${color} 88%, white)`);
        root.setProperty('--brand-700', `color-mix(in srgb, ${color} 80%, black)`);
        root.setProperty('--brand-200', `color-mix(in srgb, ${color} 35%, var(--surface))`);
        root.setProperty('--brand-100', `color-mix(in srgb, ${color} 18%, var(--surface))`);
        root.setProperty('--brand-50', `color-mix(in srgb, ${color} 10%, var(--surface))`);
    }

    watch(() => brand()?.color, apply, { immediate: true });
    onBeforeUnmount(() => apply(null));
}
