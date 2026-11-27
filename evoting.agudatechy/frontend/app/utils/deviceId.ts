const KEY = 'evoting_device';

/**
 * A random, anonymous ID this browser keeps, so the server can tell apart people
 * who share one internet address (common on mobile networks). Nothing personal is
 * in it. Returns undefined when storage is blocked (the server then uses the IP alone).
 */
export function deviceId(): string | undefined {
    try {
        let id = localStorage.getItem(KEY);
        if (!id) {
            id = typeof crypto !== 'undefined' && 'randomUUID' in crypto
                ? crypto.randomUUID()
                : `${Date.now().toString(36)}-${Math.random().toString(36).slice(2)}${Math.random().toString(36).slice(2)}`;
            localStorage.setItem(KEY, id);
        }
        return id;
    } catch {
        return undefined;
    }
}
