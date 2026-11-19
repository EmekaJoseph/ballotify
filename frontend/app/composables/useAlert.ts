export type AlertTone = 'danger' | 'warning' | 'info' | 'success';

export interface AlertOptions {
    title?: string;
    message: string;
    tone?: AlertTone;
    /** Main button (e.g. "Try again"). Without it, only the close button shows. */
    actionText?: string;
    closeText?: string;
}

interface AlertState extends AlertOptions {
    open: boolean;
}

let resolver: ((action: boolean) => void) | null = null;

/**
 * App-wide pop-up alert (rendered once by <AppAlert /> in app.vue).
 * Resolves true when the main button is pressed, false when closed.
 */
export function useAlert() {
    const state = useState<AlertState>('app-alert', () => ({ open: false, message: '' }));

    function alert(options: AlertOptions): Promise<boolean> {
        resolver?.(false); // a newer alert replaces an open one
        state.value = { tone: 'danger', ...options, open: true };
        return new Promise<boolean>((resolve) => { resolver = resolve; });
    }

    function settle(action: boolean) {
        state.value = { ...state.value, open: false };
        resolver?.(action);
        resolver = null;
    }

    return { state, alert, settle };
}
