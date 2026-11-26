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
                        <template v-if="event.limits && event.limits.max_voters !== null">
                            <span v-if="event.limits.plan === 'pro'" class="pill pill-pro"><i class="bi bi-lightning-charge-fill"></i> {{ event.limits.tier_name || 'Pro' }}</span>
                            <span v-else class="pill pill-completed"><i class="bi bi-box"></i> Free</span>
                        </template>
                        <span v-if="event.voting_mode === 'paid'" class="pill pill-amber"><i class="bi bi-cash-coin"></i> Paid voting</span>
                        <span v-if="event.my_role && event.my_role !== 'owner'" class="pill pill-completed" :title="ROLE_INFO[event.my_role].text"><i class="bi bi-people"></i> Shared with you · {{ roleLabel(event.my_role) }}</span>
                        <span class="small text-muted"><i class="bi bi-calendar3"></i> {{ formatWindow(event.starts_at, event.ends_at) }}</span>
                    </div>
                </template>
                <button class="btn btn-ghost" @click="tab = 'results'"><i class="bi bi-bar-chart-line"></i> Results</button>
                <button class="btn btn-primary" @click="copyText(voteUrl, 'Voting link copied')"><i class="bi bi-link-45deg"></i> Copy voting link</button>
            </PageHeader>

            <!-- Desktop: always fits (smaller, then icons only). Phones: swipe to scroll. -->
            <div class="tab-nav mb-4">
                <button v-show="canScroll.left" type="button" class="tab-arrow left" aria-label="Previous tabs" tabindex="-1" @click="scrollTabs(-1)">
                    <i class="bi bi-chevron-left"></i>
                </button>
                <div ref="tabBar" class="segmented" :class="`fit-${tabFit}`" role="tablist" @scroll.passive="updateArrows">
                    <button v-for="t in tabs" :key="t.key" type="button" role="tab" :aria-selected="tab === t.key" :data-tour="`tab-${t.key}`"
                        :class="{ active: tab === t.key }" :title="tabFit === 'icons' ? t.label : undefined" :aria-label="t.label" @click="tab = t.key">
                        <i class="bi" :class="t.icon"></i><span class="tab-label">{{ t.label }}</span>
                    </button>
                </div>
                <button v-show="canScroll.right" type="button" class="tab-arrow right" aria-label="More tabs" tabindex="-1" @click="scrollTabs(1)">
                    <i class="bi bi-chevron-right"></i>
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
            <EventNominationsPanel v-else-if="tab === 'nominations'" :event="event" @changed="load(true)" />

            <div v-else-if="tab === 'settings' && can('manage')" class="row g-4">
                <!-- Settings sections: a side menu on desktop, a scrollable bar on phones -->
                <div class="col-lg-3">
                    <nav class="settings-nav card-b" aria-label="Settings sections">
                        <button v-for="s in settingsSections" :key="s.key" type="button" class="settings-link" :class="{ active: settingsTab === s.key }"
                            :aria-current="settingsTab === s.key ? 'page' : undefined" @click="settingsTab = s.key">
                            <i class="bi" :class="s.icon"></i>
                            <span class="min-w-0"><span class="d-block fw-bold">{{ s.label }}</span><span class="d-none d-lg-block small text-muted">{{ s.hint }}</span></span>
                        </button>
                    </nav>
                </div>
                <div class="col-lg-9">
                    <div v-if="settingsTab === 'general'" class="card-b pad">
                        <h3 class="h6 fw-800 mb-4">General</h3>
                        <EventForm :key="event.id + '-' + formVersion" :event="event" :busy="saving" :server-errors="fieldErrors"
                            submit-label="Save changes" @submit="save" />
                    </div>
                    <EventVotingOptions v-else-if="settingsTab === 'options'" :event="event" @changed="load(true)" @upgrade="upgrade" @go="goTab($event)" />
                    <div v-else-if="settingsTab === 'branding'" style="max-width: 560px">
                        <EventBrandingCard :event="event" @upgrade="upgrade" @saved="load(true)" />
                    </div>
                    <div v-else class="advanced-grid">
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
                    <div v-if="canResetVotes" class="card-b pad danger-zone mb-4">
                        <h3 class="h6 fw-800 mb-2 text-danger">Reset votes and results</h3>
                        <p class="small text-muted mb-3">
                            After a test run: erases every vote and result so the real election starts clean. Voters keep their codes.
                            Recorded for good on the results, report and certificate.
                        </p>
                        <button class="btn btn-danger-soft w-100" @click="resetOpen = true"><i class="bi bi-arrow-counterclockwise"></i> Reset votes…</button>
                    </div>
                    <div v-if="can('own')" class="card-b pad danger-zone">
                        <h3 class="h6 fw-800 mb-2 text-danger">Delete event</h3>
                        <p class="small text-muted mb-3">Permanently removes the ballot, voter list, results and activity log.</p>
                        <button class="btn btn-danger-soft w-100" @click="remove"><i class="bi bi-trash"></i> Delete event</button>
                    </div>
                    </div>
                </div>
            </div>
            <EventTour v-if="showTour" @done="finishTour" />
        </template>
        <EventResetVotesModal v-if="event" :open="resetOpen" :event-id="event.id" :event-name="event.name" @close="resetOpen = false" @done="onVotesReset" />
    </div>
</template>

<script setup lang="ts">
import { adminApi, billingApi, type EventPayload } from '~/api/endpoints/admin';
import { useAuthStore } from '~/stores/auth';
import type { BallotEvent, EventAbility } from '~/types';

definePageMeta({ layout: 'dashboard', middleware: ['auth'] });

type TabKey = 'overview' | 'ballot' | 'nominations' | 'voters' | 'earnings' | 'results' | 'share' | 'team' | 'activity' | 'settings';
const ALL_TABS: { key: TabKey; label: string; icon: string; needs?: EventAbility; mode?: 'codes' | 'paid'; when?: (e: BallotEvent) => boolean }[] = [
    { key: 'overview', label: 'Overview', icon: 'bi-speedometer2' },
    { key: 'ballot', label: 'Ballot', icon: 'bi-ui-checks-grid' },
    { key: 'nominations', label: 'Nominations', icon: 'bi-megaphone', when: e => !!e.nominations_enabled },
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
const tabs = computed(() => ALL_TABS.filter(t => (!t.needs || can(t.needs))
    && (!t.mode || t.mode === (event.value?.voting_mode ?? 'codes'))
    && (!t.when || (event.value ? t.when(event.value) : false))));
const loading = ref(true);
const notFound = ref(false);
const loadError = ref('');
const saving = ref(false);
const fieldErrors = ref<Record<string, string>>({});
const formVersion = ref(0);

// ----- Tab bar fit: on screens from 768px it never scrolls; it steps down to
// smaller labels, then icons only (the active tab keeps its label). Phones scroll.
const tabBar = ref<HTMLElement | null>(null);
const tabFit = ref<'full' | 'compact' | 'icons'>('full');
let fitting = false;
async function fitTabs() {
    const el = tabBar.value;
    if (!el || fitting) return;
    fitting = true;
    try {
        const steps = ['full', 'compact', 'icons'] as const;
        if (window.innerWidth < 768) { tabFit.value = 'full'; return; }
        for (const step of steps) {
            tabFit.value = step;
            await nextTick();
            if (el.scrollWidth <= el.clientWidth + 1) return;
        }
    } finally {
        fitting = false;
        await nextTick();
        updateArrows();
        revealActiveTab();
    }
}

// ----- When the bar does scroll (phones, very narrow windows): arrows at the ends.
const canScroll = reactive({ left: false, right: false });
function updateArrows() {
    const el = tabBar.value;
    if (!el) return;
    canScroll.left = el.scrollLeft > 2;
    canScroll.right = el.scrollLeft + el.clientWidth < el.scrollWidth - 2;
}
function scrollTabs(dir: 1 | -1) {
    const el = tabBar.value;
    if (el) el.scrollBy({ left: dir * el.clientWidth * 0.7, behavior: 'smooth' });
}
/** Keep the active tab in view (e.g. opened from a link to ?tab=settings). */
function revealActiveTab() {
    // Scrolls the strip only (scrollIntoView could also move the page up or down).
    const bar = tabBar.value;
    const el = bar?.querySelector<HTMLElement>('button.active');
    if (!bar || !el || bar.scrollWidth <= bar.clientWidth) return;
    const pad = 40; // clear of the arrow
    const left = el.offsetLeft - bar.offsetLeft;
    if (left < bar.scrollLeft + pad) bar.scrollTo({ left: Math.max(0, left - pad), behavior: 'smooth' });
    else if (left + el.offsetWidth > bar.scrollLeft + bar.clientWidth - pad) {
        bar.scrollTo({ left: left + el.offsetWidth - bar.clientWidth + pad, behavior: 'smooth' });
    }
}
let tabBarObserver: ResizeObserver | null = null;
let lastWidth = 0;
watch(tabBar, (el, old) => {
    if (old) tabBarObserver?.unobserve(old.parentElement ?? old);
    if (!el) return;
    // Only width matters: content growing taller below the bar mustn't re-measure (it would flicker).
    tabBarObserver ??= new ResizeObserver((entries) => {
        const w = Math.round(entries[0]?.contentRect.width ?? 0);
        if (w !== lastWidth) { lastWidth = w; fitTabs(); }
    });
    // Watch the page column, not the bar: the bar's own size changes as it fits.
    tabBarObserver.observe(el.parentElement ?? el);
    fitTabs();
});
onBeforeUnmount(() => tabBarObserver?.disconnect());

const initialTab = String(route.query.tab ?? 'overview');
const tab = ref<TabKey>(ALL_TABS.some(t => t.key === initialTab) ? (initialTab as TabKey) : 'overview');
// A tab this user can't see (or wrong for the voting mode) falls back to the overview.
watch(tabs, (list) => { if (event.value && !list.some(t => t.key === tab.value)) tab.value = 'overview'; });
// More or fewer tabs (roles, voting mode), or a different active tab (it keeps its label): fit again.
// (Must come after `tab` is declared: watch reads it straight away.)
watch(() => [tabs.value.length, tab.value], () => fitTabs());
function goTab(key: string) { if (tabs.value.some(t => t.key === key)) tab.value = key as TabKey; }
// Links to another tab of the same event (e.g. ?tab=settings) reuse this page: follow the URL.
watch(() => route.query.tab, (q) => {
    const key = String(q ?? 'overview');
    if (key !== tab.value) {
        if (tabs.value.some(t => t.key === key)) tab.value = key as TabKey;
        else router.replace({ query: { ...route.query, tab: tab.value === 'overview' ? undefined : tab.value } });
    }
});

// ----- Settings sections
type SettingsKey = 'general' | 'options' | 'branding' | 'advanced';
const settingsSections: { key: SettingsKey; label: string; hint: string; icon: string }[] = [
    { key: 'general', label: 'General', hint: 'Name, dates, how people vote', icon: 'bi-sliders' },
    { key: 'options', label: 'Voting options', hint: 'Self-registration, USSD, nominations', icon: 'bi-toggles' },
    { key: 'branding', label: 'Branding', hint: 'Your logo and colour', icon: 'bi-palette' },
    { key: 'advanced', label: 'Advanced', hint: 'Duplicate, close, erase, delete', icon: 'bi-tools' },
];
const initialSection = String(route.query.section ?? 'general');
const settingsTab = ref<SettingsKey>(settingsSections.some(s => s.key === initialSection) ? (initialSection as SettingsKey) : 'general');
watch(settingsTab, (s) => router.replace({ query: { ...route.query, section: s === 'general' ? undefined : s } }));
// Phones: the sections bar scrolls sideways; keep the active one visible.
watch([tab, settingsTab, loading], async () => {
    if (tab.value !== 'settings') return;
    await nextTick();
    document.querySelector('.settings-link.active')?.scrollIntoView({ block: 'nearest', inline: 'center' });
}, { immediate: true });

const { openUpgrade } = useBilling();
function upgrade() {
    if (event.value) openUpgrade({ id: event.value.id, name: event.value.name, limits: event.value.limits, voters: event.value.voters_count ?? 0 });
}
const paymentReturn = usePaymentReturn();
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

/** Back from checkout (a Pro upgrade or SMS credits): confirm it, then refresh. */
async function confirmPaymentReturn() {
    if (await paymentReturn()) {
        await load(true);
        auth.fetchUser?.(); // SMS credit balance
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

// Reset votes (test runs): owner only; never paid voting or once voting has closed.
const resetOpen = ref(false);
const canResetVotes = computed(() => can('own') && event.value?.voting_mode !== 'paid' && event.value?.status !== 'completed');
async function onVotesReset() {
    resetOpen.value = false;
    await load(true);
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
/* Tab bar: arrows over the ends when it scrolls. */
.tab-nav { position: relative; display: flex; max-width: 100%; width: fit-content; }
.tab-nav .segmented { min-width: 0; }
.tab-arrow {
    position: absolute; top: 4px; bottom: 4px; z-index: 2; width: 36px; border: 0; padding: 0;
    display: grid; place-items: center; color: var(--ink); cursor: pointer; border-radius: 10px;
}
.tab-arrow.left { left: 4px; background: linear-gradient(90deg, var(--surface) 60%, transparent); justify-content: start; padding-left: 6px; }
.tab-arrow.right { right: 4px; background: linear-gradient(270deg, var(--surface) 60%, transparent); justify-content: end; padding-right: 6px; }
.tab-arrow:hover { color: var(--brand-600); }

/* Tab bar sizes (see fitTabs). */
.segmented.fit-compact button { font-size: .82rem; padding: .45rem .65rem; gap: .35rem; }
.segmented.fit-icons button { padding: .5rem .7rem; }
.segmented.fit-icons button:not(.active) .tab-label { display: none; }
.segmented.fit-icons button i { font-size: 1.05rem; }
.danger-zone { border-color: var(--danger-line); }
.settings-nav { display: flex; flex-direction: column; gap: .25rem; padding: .5rem; position: sticky; top: 1rem; }
.settings-link {
    display: flex; align-items: flex-start; gap: .7rem; text-align: left; border: 0; background: none;
    padding: .7rem .8rem; border-radius: 12px; color: var(--ink-2); transition: background-color .15s;
}
.settings-link > .bi { font-size: 1.05rem; margin-top: 1px; color: var(--muted); }
.settings-link:hover { background: var(--surface-2); }
.settings-link.active { background: var(--brand-50); color: var(--brand-700); }
.settings-link.active > .bi { color: var(--brand-600); }
.advanced-grid { display: grid; gap: 1.25rem; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); align-items: start; }
@media (max-width: 991.98px) {
    .settings-nav { flex-direction: row; overflow-x: auto; position: static; scrollbar-width: none; }
    .settings-nav::-webkit-scrollbar { display: none; }
    .settings-link { flex-shrink: 0; padding: .5rem .8rem; }
}
</style>
