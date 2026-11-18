export type ToastKind = 'success' | 'error' | 'info';

export interface Toast {
    id: number;
    kind: ToastKind;
    message: string;
}

let nextId = 1;

/** App-wide toast notifications (rendered by <AppToasts /> in app.vue). */
export function useToast() {
    const toasts = useState<Toast[]>('toasts', () => []);

    function dismiss(id: number) {
        toasts.value = toasts.value.filter(t => t.id !== id);
    }

    function push(kind: ToastKind, message: string, timeout = 3800) {
        const id = nextId++;
        toasts.value = [...toasts.value.slice(-3), { id, kind, message }];
        if (timeout > 0) setTimeout(() => dismiss(id), timeout);
    }

    return {
        toasts,
        dismiss,
        success: (m: string) => push('success', m),
        error: (m: string) => push('error', m, 6000),
        info: (m: string) => push('info', m),
    };
}
