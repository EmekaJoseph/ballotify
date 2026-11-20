import { isAxiosError } from 'axios';
import { t } from './i18n';

/** Best human-readable message from an API error (Laravel validation aware). */
export function apiErrorMessage(err: unknown, fallback = t('err.generic')): string {
    if (isAxiosError(err)) {
        if (!err.response) return t('err.network');
        const data = err.response.data as { message?: string; errors?: Record<string, string[]> } | undefined;
        const firstFieldError = data?.errors ? Object.values(data.errors)[0]?.[0] : undefined;
        if (firstFieldError) return firstFieldError;
        if (err.response.status === 429) return t('err.tooMany');
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
