import type { Ref } from 'vue';

/**
 * Show a page's error message as the pop-up alert instead of a banner.
 * Whenever `error` gets a message it pops up; closing it clears the message.
 */
export function useErrorAlert(error: Ref<string>, title: string | (() => string)) {
    const { alert } = useAlert();
    watch(error, async (message) => {
        if (!message) return;
        await alert({ title: typeof title === 'function' ? title() : title, message, tone: 'danger' });
        if (error.value === message) error.value = '';
    });
}
