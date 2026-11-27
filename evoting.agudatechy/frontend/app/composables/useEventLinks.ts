/** Public URLs for an event, built from the site the organiser is using. */
export function useEventLinks(token: MaybeRefOrGetter<string | undefined>) {
    const origin = import.meta.client ? window.location.origin : '';
    const voteUrl = computed(() => (toValue(token) ? `${origin}/vote/${toValue(token)}` : ''));
    const resultsUrl = computed(() => (toValue(token) ? `${origin}/event/${toValue(token)}/results` : ''));
    return { voteUrl, resultsUrl };
}

export async function copyText(text: string, label = 'Copied to clipboard') {
    const toast = useToast();
    try {
        await navigator.clipboard.writeText(text);
        toast.success(label);
    } catch {
        // Fallback for http:// origins where the async clipboard API is unavailable.
        const ta = document.createElement('textarea');
        ta.value = text;
        ta.style.position = 'fixed';
        ta.style.opacity = '0';
        document.body.appendChild(ta);
        ta.select();
        const ok = document.execCommand('copy');
        ta.remove();
        if (ok) toast.success(label);
        else toast.error('Could not copy. Select the text and copy it manually.');
    }
}
