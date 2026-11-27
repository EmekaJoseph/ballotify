import { isAxiosError } from 'axios';
import { t } from './i18n';

/** Best human-readable message from an API error (Laravel validation aware). */
export function apiErrorMessage(err: unknown, fallback = t('err.generic')): string {
    if (isAxiosError(err)) {
        if (!err.response) return t('err.network');
        const data = err.response.data as { message?: string; errors?: Record<string, string[]> } | undefined;
        const firstFieldError = data?.errors ? Object.values(data.errors)[0]?.[0] : undefined;
        if (firstFieldError) return firstFieldError;
        // Our own 429s explain themselves (e.g. "3 nominations per category", "too many wrong codes",
        // in the voter's language); only the speed limiter's stock "Too Many Attempts." gets the generic text.
        if (err.response.status === 429) return data?.message && data.message !== 'Too Many Attempts.' ? data.message : t('err.tooMany');
        if (data?.message) return data.message;
    }
    return fallback;
}

/** Field → first message map from a Laravel 422 response. */
export function apiFieldErrors(err: unknown): Record<string, string> {
    if (!isAxiosError(err)) return {};
    const errors = (err.response?.data as { errors?: Record<string, string[]> } | undefined)?.errors ?? {};
    return Object.fromEntries(Object.entries(errors).map(([k, v]) => [k, v[0] ?? '']));
}

/** The API refused because of the event's plan (HTTP 402). Returns the reason, e.g. 'voters'. */
export function planLimitReason(err: unknown): string | null {
    if (!isAxiosError(err) || err.response?.status !== 402) return null;
    return (err.response.data as { plan_limit?: string } | undefined)?.plan_limit ?? 'plan';
}

export function apiStatus(err: unknown): number | undefined {
    return isAxiosError(err) ? err.response?.status : undefined;
}

/**
 * Runs a request again when the server is briefly busy (HTTP 429 from the rate limiter),
 * waiting as long as it asks (Retry-After, at most 10 s), up to `tries` times. A 429 that
 * carries `retry_after` in its body is a deliberate block (e.g. too many wrong codes): not retried.
 * Safe for votes: a throttled request is refused before anything is recorded.
 */
export async function retryWhenBusy<T>(run: () => Promise<T>, onWait?: (seconds: number) => void, tries = 3): Promise<T> {
    for (let attempt = 1; ; attempt++) {
        try {
            return await run();
        } catch (e) {
            const blocked = isAxiosError(e) && (e.response?.data as { retry_after?: number } | undefined)?.retry_after;
            if (apiStatus(e) !== 429 || blocked || attempt >= tries) throw e;
            const header = Number(isAxiosError(e) ? e.response?.headers?.['retry-after'] : NaN);
            const seconds = Math.min(10, Math.max(2, Number.isFinite(header) ? header : 3)) + Math.random(); // spread retries out
            onWait?.(Math.round(seconds));
            await new Promise(r => setTimeout(r, seconds * 1000));
        }
    }
}
