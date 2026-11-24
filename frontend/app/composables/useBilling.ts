import { billingApi } from '~/api/endpoints/admin';
import type { EventLimits, PlanInfo, PlanTier, ProTier } from '~/types';

/** Format a whole-naira amount, e.g. 10000 -> "₦10,000". */
export function formatNaira(amount: number): string {
    return '₦' + Math.round(amount).toLocaleString('en-NG');
}

/** The event an upgrade dialog is for (just what the dialog needs). */
export interface UpgradeTarget { id: number; name: string; limits?: EventLimits | null; voters?: number }

/**
 * Plan prices/limits (fetched once per session), the Pro upgrade flow (three
 * sizes) and prepaid SMS credits. Checkout sends the organiser to the payment
 * page; they come back to the event (or account) page, which confirms it.
 */
export function useBilling() {
    const plans = useState<PlanInfo | null>('billing-plans', () => null);
    const loading = useState<boolean>('billing-plans-loading', () => false);
    /** Open dialogs (mounted once in the dashboard layout). */
    const upgradeTarget = useState<UpgradeTarget | null>('billing-upgrade-target', () => null);
    const smsTarget = useState<{ eventId?: number; suggest?: number } | null>('billing-sms-target', () => null);
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

    const tiers = computed<PlanTier[]>(() => plans.value?.tiers ?? []);
    const tierByKey = (key?: string | null) => tiers.value.find(t => t.key === key) ?? null;
    /** "₦25,000": the standard Pro price. */
    const priceLabel = computed(() => (plans.value ? formatNaira(plans.value.pro_price_naira) : ''));
    /** "from ₦10,000": the smallest size. */
    const fromPriceLabel = computed(() => (tiers.value.length ? `from ${formatNaira(Math.min(...tiers.value.map(t => t.price_naira)))}` : priceLabel.value));

    async function startUpgrade(eventId: number | string, tier: ProTier = 'standard') {
        redirecting.value = true;
        try {
            const { data } = await billingApi.checkout(eventId, tier);
            window.location.assign(data.authorization_url);
        } catch (e) {
            toast.error(apiErrorMessage(e, 'Could not start the payment. Please try again.'));
            redirecting.value = false;
        }
    }

    async function buySms(quantity: number, eventId?: number) {
        redirecting.value = true;
        try {
            const { data } = await billingApi.buySmsCredits(quantity, eventId);
            window.location.assign(data.authorization_url);
        } catch (e) {
            toast.error(apiErrorMessage(e, 'Could not start the payment. Please try again.'));
            redirecting.value = false;
        }
    }

    /** Open the "choose a size" dialog for an event. */
    function openUpgrade(target: UpgradeTarget) {
        upgradeTarget.value = target;
        loadPlans();
    }
    /** Open the "buy SMS credits" dialog (suggest = how many are missing). */
    function openBuySms(opts: { eventId?: number; suggest?: number } = {}) {
        smsTarget.value = opts;
        loadPlans();
    }

    return {
        plans, loadPlans, tiers, tierByKey, priceLabel, fromPriceLabel,
        startUpgrade, buySms, redirecting, upgradeTarget, smsTarget, openUpgrade, openBuySms,
    };
}

/**
 * Back from checkout: Paystack appends ?reference=… (and trxref), Flutterwave
 * ?tx_ref=…&status=…&transaction_id=…. Call in setup; the returned function
 * confirms it with the API (which asks the provider), tidies the URL and
 * resolves true when a payment was confirmed.
 */
export function usePaymentReturn() {
    const route = useRoute();
    const router = useRouter();
    const toast = useToast();
    return () => confirmPaymentReturn(route, router, toast);
}

async function confirmPaymentReturn(route: ReturnType<typeof useRoute>, router: ReturnType<typeof useRouter>, toast: ReturnType<typeof useToast>): Promise<boolean> {
    const reference = String(route.query.reference ?? route.query.trxref ?? route.query.tx_ref ?? '');
    if (!route.query.payment || !reference) return false;
    const { payment: _p, reference: _r, trxref: _t, tx_ref: _x, status: _s, transaction_id: _i, ...rest } = route.query;
    router.replace({ query: rest });
    try {
        const { data } = await billingApi.verify(reference);
        if (data.status === 'success') toast.success(data.message);
        else if (data.status === 'failed') toast.error(data.message);
        else toast.info(data.message);
        return data.status === 'success';
    } catch (e) {
        toast.error(apiErrorMessage(e, 'We couldn’t confirm the payment. If you were charged, it will be applied shortly.'));
        return false;
    }
}
