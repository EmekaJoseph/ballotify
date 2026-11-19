<template>
    <div>
        <!-- Loading -->
        <template v-if="loading">
            <div class="skeleton mb-2" style="width: 120px"></div>
            <div class="skeleton mb-4" style="width: 50%; height: 32px"></div>
            <div class="skeleton mb-4" style="width: 420px; max-width: 100%; height: 44px"></div>
            <div class="row g-3"><div v-for="i in 4" :key="i" class="col-6 col-md-3"><div class="skeleton" style="height: 92px"></div></div></div>
        </template>

        <div v-else-if="notFound" class="card-b">
            <EmptyState icon="bi-calendar-x" title="Event not found" text="It may have been deleted, or it belongs to another account.">
                <NuxtLink to="/dashboard/events" class="btn btn-primary">Back to events</NuxtLink>
            </EmptyState>
        </div>

        <div v-else-if="loadError" class="card-b">
            <EmptyState icon="bi-wifi-off" title="Couldn't load this event" :text="loadError">
                <button class="btn btn-soft" @click="load()">Try again</button>
            </EmptyState>
        </div>

        <template v-else-if="event">
            <PageHeader :title="event.name" back="/dashboard/events" back-label="Events">
                <template #meta>
                    <div class="d-flex flex-wrap align-items-center gap-2 mt-2">
                        <StatusBadge :status="event.status" :draft="isDraft(event)" />
                        <span v-if="event.limits && event.limits.max_voters !== null" class="pill" :class="event.limits.plan === 'pro' ? 'pill-violet' : 'pill-completed'">
                            <i class="bi" :class="event.limits.plan === 'pro' ? 'bi-gem' : 'bi-box'"></i> {{ event.limits.plan === 'pro' ? 'Pro' : 'Free' }}
                        </span>
                        <span v-if="event.voting_mode === 'paid'" class="pill pill-amber"><i class="bi bi-cash-coin"></i> Paid voting</span>
                        <span v-if="event.my_role && event.my_role !== 'owner'" class="pill pill-completed" :title="ROLE_INFO[event.my_role].text"><i class="bi bi-people"></i> Shared with you · {{ roleLabel(event.my_role) }}</span>
                        <span class="small text-muted"><i class="bi bi-calendar3"></i> {{ formatWindow(event.starts_at, event.ends_at) }}</span>
                    </div>
                </template>
                <button class="btn btn-ghost" @click="tab = 'results'"><i class="bi bi-bar-chart-line"></i> Results</button>
                <button class="btn btn-primary" @click="copyText(voteUrl, 'Voting link copied')"><i class="bi bi-link-45deg"></i> Copy voting link</button>
            </PageHeader>

            <div class="segmented mb-4" role="tablist">
                <button v-for="t in tabs" :key="t.key" type="button" role="tab" :aria-selected="tab === t.key" :data-tour="`tab-${t.key}`"
                    :class="{ active: tab === t.key }" @click="tab = t.key">
                    <i class="bi" :class="t.icon"></i>{{ t.label }}
                </button>
            </div>

            <EventOverview v-if="tab === 'overview'" :event="event" @go="goTab($event)" />
            <EventBallotManager v-else-if="tab === 'ballot'" :event="event" @changed="load(true)" />
            <EventVoterManager v-else-if="tab === 'voters'" :event="event" @changed="load(true)" />
            <EventAdminResults v-else-if="tab === 'results'" :event="event" />
            <EventSharePanel v-else-if="tab === 'share'" :event="event" />
            <EventActivityLog v-else-if="tab === 'activity'" :event-id="event.id" />
            <EventTeamPanel v-else-if="tab === 'team'" :event="event" @upgrade="upgrade" @left="onLeft" />
            <EventEarningsPanel v-else-if="tab === 'earnings'" :event="event" />

            <div v-else-if="tab === 'settings' && can('manage')" class="row g-4">
                <div class="col-lg-8">
                    <div class="card-b pad">
                        <h3 class="h6 fw-800 mb-4">Event settings</h3>
                        <EventForm :key="event.id + '-' + formVersion" :event="event" :busy="saving" :server-errors="fieldErrors"
                            submit-label="Save changes" @submit="save" />
                    </div>
                </div>
                <div class="col-lg-4 d-grid gap-4 align-content-start">
                    <EventBrandingCard :event="event" @upgrade="upgrade" @saved="load(true)" />
                    <div class="card-b pad">
                        <h3 class="h6 fw-800 mb-2">Duplicate event</h3>
                        <p class="small text-muted mb-3">
                            Copies the ballot (categories, pick limits, candidates, descriptions and photos) into a new event.
                            Voters, votes and dates are not copied.
                        </p>
                        <button class="btn btn-ghost w-100" :disabled="duplicating" @click="duplicate">
                            <span v-if="duplicating" class="spinner-border spinner-border-sm"></span><i v-else class="bi bi-copy"></i> Duplicate event
                        </button>
                    </div>
                    <div class="card-b pad">
                        <h3 class="h6 fw-800 mb-2">Close voting now</h3>
                        <p class="small text-muted mb-3">Ends the voting window immediately. Results stay available.</p>
                        <button class="btn btn-ghost w-100" :disabled="event.status !== 'active' || saving" @click="closeNow">
                            <i class="bi bi-stop-circle"></i> Close voting
                        </button>
                    </div>
                    <div class="card-b pad">
                        <h3 class="h6 fw-800 mb-2">Erase voter contact details</h3>
                        <p class="small text-muted mb-3">
                            Removes every voter’s email and phone number. Codes, turnout and results are kept.
                            They are also erased automatically some time after voting closes (30 days by default).
                        </p>
                        <button class="btn btn-ghost w-100" :disabled="erasing" @click="eraseContacts">
                            <span v-if="erasing" class="spinner-border spinner-border-sm"></span><i v-else class="bi bi-eraser"></i> Erase contact details
                        </button>
                    </div>
                    <div v-if="can('own')" class="card-b pad danger-zone">
                        <h3 class="h6 fw-800 mb-2 text-danger">Delete event</h3>
                        <p class="small text-muted mb-3">Permanently removes the ballot, voter list, results and activity log.</p>
                        <button class="btn btn-danger-soft w-100" @click="remove"><i class="bi bi-trash"></i> Delete event</button>
                    </div>
                </div>
            </div>
            <EventTour v-if="showTour" @done="finishTour" />
        </template>
    </div>
</template>

<script setup lang="ts">
import { adminApi, billingApi, type EventPayload } from '~/api/endpoints/admin';
import { useAuthStore } from '~/stores/auth';
import type { BallotEvent, EventAbility } from '~/types';

definePageMeta({ layout: 'dashboard', middleware: ['auth'] });

type TabKey = 'overview' | 'ballot' | 'voters' | 'earnings' | 'results' | 'share' | 'team' | 'activity' | 'settings';
const ALL_TABS: { key: TabKey; label: string; icon: string; needs?: EventAbility; mode?: 'codes' | 'paid' }[] = [
    { key: 'overview', label: 'Overview', icon: 'bi-speedometer2' },
    { key: 'ballot', label: 'Ballot', icon: 'bi-ui-checks-grid' },
    { key: 'voters', label: 'Voters', icon: 'bi-people', needs: 'voters', mode: 'codes' },
    { key: 'earnings', label: 'Earnings', icon: 'bi-cash-coin', needs: 'manage', mode: 'paid' },
    { key: 'results', label: 'Results', icon: 'bi-bar-chart-line' },
    { key: 'share', label: 'Share', icon: 'bi-send' },
    { key: 'team', label: 'Team', icon: 'bi-person-gear' },
    { key: 'activity', label: 'Activity', icon: 'bi-clock-history' },
    { key: 'settings', label: 'Settings', icon: 'bi-sliders', needs: 'manage' },
];

const route = useRoute();
const router = useRouter();
const toast = useToast();
const { confirm } = useConfirm();

const eventId = computed(() => String(route.params.id));
const event = ref<BallotEvent | null>(null);
const auth = useAuthStore();

/** What this user may do here (owners and old responses: everything). */
const can = (a: EventAbility) => event.value?.abilities?.includes(a) ?? true;
const tabs = computed(() => ALL_TABS.filter(t => (!t.needs || can(t.needs)) && (!t.mode || t.mode === (event.value?.voting_mode ?? 'codes'))));
const loading = ref(true);
const notFound = ref(false);
const loadError = ref('');
const saving = ref(false);
const fieldErrors = ref<Record<string, string>>({});
const formVersion = ref(0);

const initialTab = String(route.query.tab ?? 'overview');
const tab = ref<TabKey>(ALL_TABS.some(t => t.key === initialTab) ? (initialTab as TabKey) : 'overview');
// A tab this user can't see (or wrong for the voting mode) falls back to the overview.
watch(tabs, (list) => { if (event.value && !list.some(t => t.key === tab.value)) tab.value = 'overview'; });
function goTab(key: string) { if (tabs.value.some(t => t.key === key)) tab.value = key as TabKey; }
// Links to another tab of the same event (e.g. ?tab=settings) reuse this page: follow the URL.
watch(() => route.query.tab, (q) => {
    const key = String(q ?? 'overview');
    if (key !== tab.value) {
        if (tabs.value.some(t => t.key === key)) tab.value = key as TabKey;
        else router.replace({ query: { ...route.query, tab: tab.value === 'overview' ? undefined : tab.value } });
    }
});

const { startUpgrade } = useBilling();
function upgrade() { startUpgrade(eventId.value); }
async function onLeft() {
    toast.success('You left the event');
    await navigateTo('/dashboard/events');
}

// ----- First-run tour: once per account, on the owner's first event page
const showTour = computed(() => !!event.value && !loading.value && event.value.my_role === 'owner' && !!auth.user && !auth.user.onboarded_at);
async function finishTour() {
    if (auth.user) auth.user = { ...auth.user, onboarded_at: new Date().toISOString() };
    try { auth.user = (await adminApi.markOnboarded()).data; } catch { /* shown again next time at worst */ }
}
watch(tab, (t) => router.replace({ query: { ...route.query, tab: t === 'overview' ? undefined : t } }));

const { voteUrl } = useEventLinks(() => event.value?.link_token);

const { refreshQuota, showLimit } = useNewEvent();
const duplicating = ref(false);
async function duplicate() {
    duplicating.value = true;
    try {
        const { data } = await adminApi.duplicateEvent(eventId.value);
        toast.success(`Created “${data.name}”. Set its dates and add voters when you’re ready.`);
        await navigateTo({ path: `/dashboard/events/${data.id}`, query: { tab: 'settings' } });
    } catch (e) {
        if (planLimitReason(e) === 'events') {
            await refreshQuota();
            showLimit();
            return;
        }
        toast.error(apiErrorMessage(e));
    } finally {
        duplicating.value = false;
    }
}

const erasing = ref(false);
async function eraseContacts() {
    const ok = await confirm({
        title: 'Erase all voter contact details?',
        message: 'Every email address and phone number for this event will be permanently removed. You won’t be able to send codes or reminders afterwards.',
        confirmText: 'Erase details',
        danger: true,
    });
    if (!ok) return;
    erasing.value = true;
    try {
        const { data } = await adminApi.eraseVoterContacts(eventId.value);
        toast.success(`Contact details erased for ${data.erased} voter${data.erased === 1 ? '' : 's'}`);
    } catch (e) {
        toast.error(apiErrorMessage(e));
    } finally {
        erasing.value = false;
    }
}
useHead({ title: computed(() => (event.value ? `${event.value.name} · Ballotify` : 'Event · Ballotify')) });

/** silent = refresh in place without the loading skeleton. */
async function load(silent = false) {
    if (!silent) loading.value = true;
    loadError.value = '';
    try {
        event.value = (await adminApi.getEvent(eventId.value)).data;
        notFound.value = false;
    } catch (e) {
        if (apiStatus(e) === 404) notFound.value = true;
        else if (!silent) loadError.value = apiErrorMessage(e);
        else toast.error(apiErrorMessage(e));
    } finally {
        loading.value = false;
    }
}
onMounted(async () => {
    await load();
    confirmPaymentReturn();
});

/**
 * Back from Paystack: it appends ?reference=… (and trxref) to our callback URL.
 * Confirm it with the API (which asks Paystack), then tidy the URL.
 */
async function confirmPaymentReturn() {
    const reference = String(route.query.reference ?? route.query.trxref ?? '');
    if (!route.query.payment || !reference) return;
    const { payment: _p, reference: _r, trxref: _t, ...rest } = route.query;
    router.replace({ query: rest });
    try {
        const { data } = await billingApi.verify(reference);
        if (data.status === 'success') toast.success(data.message);
        else if (data.status === 'failed') toast.error(data.message);
        else toast.info(data.message);
        await load(true);
    } catch (e) {
        toast.error(apiErrorMessage(e, 'We couldn’t confirm the payment. If you were charged, it will be applied shortly.'));
    }
}
watch(eventId, () => load());

async function save(payload: EventPayload) {
    saving.value = true;
    fieldErrors.value = {};
    try {
        await adminApi.updateEvent(eventId.value, payload);
        toast.success('Settings saved');
        await load(true);
        formVersion.value++;
    } catch (e) {
        fieldErrors.value = apiFieldErrors(e);
        toast.error(apiErrorMessage(e));
    } finally {
        saving.value = false;
    }
}

async function closeNow() {
    const ok = await confirm({ title: 'Close voting now?', message: 'Voters will no longer be able to submit ballots.', confirmText: 'Close voting', danger: true });
    if (!ok || !event.value) return;
    // ends_at must not be earlier than starts_at.
    const now = new Date();
    const start = event.value.starts_at ? new Date(event.value.starts_at) : null;
    await save({ ends_at: now.toISOString(), starts_at: start && start > now ? now.toISOString() : event.value.starts_at });
}

async function remove() {
    if (!event.value) return;
    const ok = await confirm({
        title: `Delete “${event.value.name}”?`,
        message: 'This permanently deletes the ballot, all voter codes and all votes. This cannot be undone.',
        confirmText: 'Delete permanently',
        danger: true,
    });
    if (!ok) return;
    try {
        await adminApi.deleteEvent(eventId.value);
        toast.success('Event deleted');
        await navigateTo('/dashboard/events');
    } catch (e) {
        toast.error(apiErrorMessage(e));
    }
}
</script>

<style scoped>
.danger-zone { border-color: var(--danger-line); }
</style>
