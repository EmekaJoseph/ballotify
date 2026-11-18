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
                        <StatusBadge :status="event.status" />
                        <span class="small text-muted"><i class="bi bi-calendar3"></i> {{ formatWindow(event.starts_at, event.ends_at) }}</span>
                    </div>
                </template>
                <button class="btn btn-ghost" @click="tab = 'results'"><i class="bi bi-bar-chart-line"></i> Results</button>
                <button class="btn btn-primary" @click="copyText(voteUrl, 'Voting link copied')"><i class="bi bi-link-45deg"></i> Copy voting link</button>
            </PageHeader>

            <div class="segmented mb-4" role="tablist">
                <button v-for="t in tabs" :key="t.key" type="button" role="tab" :aria-selected="tab === t.key"
                    :class="{ active: tab === t.key }" @click="tab = t.key">
                    <i class="bi" :class="t.icon"></i>{{ t.label }}
                </button>
            </div>

            <EventOverview v-if="tab === 'overview'" :event="event" @go="tab = $event as TabKey" />
            <EventBallotManager v-else-if="tab === 'ballot'" :event="event" @changed="load(true)" />
            <EventVoterManager v-else-if="tab === 'voters'" :event="event" @changed="load(true)" />
            <EventAdminResults v-else-if="tab === 'results'" :event="event" />
            <EventSharePanel v-else-if="tab === 'share'" :event="event" />
            <EventActivityLog v-else-if="tab === 'activity'" :event-id="event.id" />

            <div v-else-if="tab === 'settings'" class="row g-4">
                <div class="col-lg-8">
                    <div class="card-b pad">
                        <h3 class="h6 fw-800 mb-4">Event settings</h3>
                        <EventForm :key="event.id + '-' + formVersion" :event="event" :busy="saving" :server-errors="fieldErrors"
                            submit-label="Save changes" @submit="save" />
                    </div>
                </div>
                <div class="col-lg-4 d-grid gap-4 align-content-start">
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
                    <div class="card-b pad danger-zone">
                        <h3 class="h6 fw-800 mb-2 text-danger">Delete event</h3>
                        <p class="small text-muted mb-3">Permanently removes the ballot, voter list, results and activity log.</p>
                        <button class="btn btn-danger-soft w-100" @click="remove"><i class="bi bi-trash"></i> Delete event</button>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>

<script setup lang="ts">
import { adminApi, type EventPayload } from '~/api/endpoints/admin';
import type { BallotEvent } from '~/types';

definePageMeta({ layout: 'dashboard', middleware: ['auth'] });

type TabKey = 'overview' | 'ballot' | 'voters' | 'results' | 'share' | 'activity' | 'settings';
const tabs: { key: TabKey; label: string; icon: string }[] = [
    { key: 'overview', label: 'Overview', icon: 'bi-speedometer2' },
    { key: 'ballot', label: 'Ballot', icon: 'bi-ui-checks-grid' },
    { key: 'voters', label: 'Voters', icon: 'bi-people' },
    { key: 'results', label: 'Results', icon: 'bi-bar-chart-line' },
    { key: 'share', label: 'Share', icon: 'bi-send' },
    { key: 'activity', label: 'Activity', icon: 'bi-clock-history' },
    { key: 'settings', label: 'Settings', icon: 'bi-sliders' },
];

const route = useRoute();
const router = useRouter();
const toast = useToast();
const { confirm } = useConfirm();

const eventId = computed(() => String(route.params.id));
const event = ref<BallotEvent | null>(null);
const loading = ref(true);
const notFound = ref(false);
const loadError = ref('');
const saving = ref(false);
const fieldErrors = ref<Record<string, string>>({});
const formVersion = ref(0);

const initialTab = String(route.query.tab ?? 'overview');
const tab = ref<TabKey>(tabs.some(t => t.key === initialTab) ? (initialTab as TabKey) : 'overview');
watch(tab, (t) => router.replace({ query: { ...route.query, tab: t === 'overview' ? undefined : t } }));

const { voteUrl } = useEventLinks(() => event.value?.link_token);

const duplicating = ref(false);
async function duplicate() {
    duplicating.value = true;
    try {
        const { data } = await adminApi.duplicateEvent(eventId.value);
        toast.success(`Created “${data.name}”. Set its dates and add voters when you’re ready.`);
        await navigateTo({ path: `/dashboard/events/${data.id}`, query: { tab: 'settings' } });
    } catch (e) {
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
onMounted(() => load());
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
