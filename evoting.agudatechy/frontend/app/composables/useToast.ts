export type ToastKind = 'success' | 'error' | 'info';

export interface Toast {
    id: number;
    kind: ToastKind;
    message: string;
    /** Optional action button, e.g. "Undo". */
    action?: { label: string; run: () => void };
    /** Countdown bar for actions that commit when the toast closes. */
    timeout?: number;
}

let nextId = 1;
const timers = new Map<number, ReturnType<typeof setTimeout>>();

/**
 * App-wide notifications. Successes and info are quick toasts (rendered by
 * <AppToasts />); errors pop up as an alert dialog (<AppAlert />) so they are
 * never missed.
 */
export function useToast() {
    const toasts = useState<Toast[]>('toasts', () => []);
    const { alert } = useAlert();

    function dismiss(id: number) {
        clearTimeout(timers.get(id));
        timers.delete(id);
        toasts.value = toasts.value.filter(t => t.id !== id);
    }

    function push(kind: ToastKind, message: string, timeout = 3800, action?: Toast['action'], onExpire?: () => void) {
        const id = nextId++;
        toasts.value = [...toasts.value.slice(-3), { id, kind, message, action, timeout: action ? timeout : undefined }];
        if (timeout > 0) {
            timers.set(id, setTimeout(() => {
                dismiss(id);
                onExpire?.();
            }, timeout));
        }
        return id;
    }

    /**
     * Remove something now and commit after a few seconds unless "Undo" is pressed.
     *   hide()   - take it off the screen straight away
     *   commit() - the real delete (runs after the delay)
     *   restore()- put it back (Undo, or if commit fails)
     */
    function undoable(message: string, opts: { hide: () => void; commit: () => Promise<unknown>; restore: () => void; delay?: number }) {
        const delay = opts.delay ?? 5000;
        let undone = false;
        opts.hide();
        const id = push('info', message, delay, {
            label: 'Undo',
            run: () => {
                undone = true;
                dismiss(id);
                opts.restore();
            },
        }, async () => {
            if (undone) return;
            try {
                await opts.commit();
            } catch (e) {
                opts.restore();
                alert({ title: 'Couldn’t remove it', message: apiErrorMessage(e) });
            }
        });
    }

    return {
        toasts,
        dismiss,
        undoable,
        success: (m: string) => push('success', m),
        /** Pops up an alert dialog. Optional title, e.g. 'Payment didn’t start'. */
        error: (m: string, title?: string) => { alert({ title, message: m, tone: 'danger' }); },
        info: (m: string) => push('info', m),
    };
}
