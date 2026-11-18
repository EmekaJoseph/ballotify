import { isAxiosError } from 'axios';

/** Best human-readable message from an API error (Laravel validation aware). */
export function apiErrorMessage(err: unknown, fallback = 'Something went wrong. Please try again.'): string {
    if (isAxiosError(err)) {
        if (!err.response) return 'Cannot reach the server. Check your connection and try again.';
        const data = err.response.data as { message?: string; errors?: Record<string, string[]> } | undefined;
        const firstFieldError = data?.errors ? Object.values(data.errors)[0]?.[0] : undefined;
        if (firstFieldError) return firstFieldError;
        if (err.response.status === 429) return 'Too many attempts. Please wait a minute and try again.';
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

export function apiStatus(err: unknown): number | undefined {
    return isAxiosError(err) ? err.response?.status : undefined;
}
