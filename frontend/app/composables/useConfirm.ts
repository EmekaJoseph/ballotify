export interface ConfirmOptions {
    title: string;
    message?: string;
    confirmText?: string;
    cancelText?: string;
    danger?: boolean;
}

interface ConfirmState extends ConfirmOptions {
    open: boolean;
}

let resolver: ((ok: boolean) => void) | null = null;

/** Promise-based confirmation dialog (rendered by <ConfirmDialog /> in app.vue). */
export function useConfirm() {
    const state = useState<ConfirmState>('confirm', () => ({ open: false, title: '' }));

    function confirm(options: ConfirmOptions): Promise<boolean> {
        resolver?.(false);
        state.value = { ...options, open: true };
        return new Promise<boolean>((resolve) => { resolver = resolve; });
    }

    function settle(ok: boolean) {
        state.value = { ...state.value, open: false };
        resolver?.(ok);
        resolver = null;
    }

    return { state, confirm, settle };
}
