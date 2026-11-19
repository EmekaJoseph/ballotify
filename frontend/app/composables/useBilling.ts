import { billingApi } from '~/api/endpoints/admin';
import type { PlanInfo } from '~/types';

/** Format a whole-naira amount, e.g. 10000 -> "₦10,000". */
export function formatNaira(amount: number): string {
    return '₦' + Math.round(amount).toLocaleString('en-NG');
}

/**
 * Plan prices/limits (fetched once per session) and the Paystack upgrade flow.
 * start() sends the organiser to Paystack; they come back to the event page,
 * which confirms the payment (see pages/dashboard/events/[id].vue).
 */
export function useBilling() {
    const plans = useState<PlanInfo | null>('billing-plans', () => null);
    const loading = useState<boolean>('billing-plans-loading', () => false);
    const redirecting = ref(false);
    const toast = useToast();

    async function loadPlans(force = false) {
        if ((plans.value && !force) || loading.value) return plans.value;
        loading.value = true;
        try {
            plans.value = (await billingApi.plans()).data;
        } catch {
            /* pricing is optional UI; leave it empty */
        } finally {
            loading.value = false;
        }
        return plans.value;
    }

    const priceLabel = computed(() => (plans.value ? formatNaira(plans.value.pro_price_naira) : ''));

    async function startUpgrade(eventId: number | string) {
        redirecting.value = true;
        try {
            const { data } = await billingApi.checkout(eventId);
            window.location.assign(data.authorization_url);
        } catch (e) {
            toast.error(apiErrorMessage(e, 'Could not start the payment. Please try again.'));
            redirecting.value = false;
        }
    }

    return { plans, loadPlans, priceLabel, startUpgrade, redirecting };
}
