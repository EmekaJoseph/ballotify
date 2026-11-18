<template>
    <div>
        <PageHeader title="Platform admin" subtitle="Everyone using this Ballotify installation. Ballot contents, voter lists and codes are never shown here." />

        <div class="row g-3 mb-4">
            <div class="col-6 col-xl-3">
                <StatCard label="Organisers" :value="ov ? formatNumber(ov.users) : '–'" icon="bi-person-badge"
                    :hint="ov ? `+${formatNumber(ov.users_last_30_days)} in 30 days` : ''" />
            </div>
            <div class="col-6 col-xl-3">
                <StatCard label="Events" :value="ov ? formatNumber(ov.events) : '–'" icon="bi-calendar2-check" tone="violet"
                    :hint="ov ? `${formatNumber(ov.events_live)} open now` : ''" />
            </div>
            <div class="col-6 col-xl-3">
                <StatCard label="Ballots cast" :value="ov ? formatNumber(ov.ballots_cast) : '–'" icon="bi-check2-square" tone="green"
                    :hint="ov ? `of ${formatNumber(ov.voters)} codes issued` : ''" />
            </div>
            <div class="col-6 col-xl-3">
                <StatCard label="Suspended" :value="ov ? formatNumber(ov.suspended_users) : '–'" icon="bi-slash-circle" tone="amber" />
            </div>
        </div>

        <div class="segmented mb-3" role="tablist">
            <button type="button" :class="{ active: tab === 'users' }" @click="tab = 'users'"><i class="bi bi-people"></i> Organisers</button>
            <button type="button" :class="{ active: tab === 'events' }" @click="tab = 'events'"><i class="bi bi-calendar2-week"></i> Events</button>
        </div>

        <div class="d-flex flex-column flex-md-row gap-2 mb-3">
            <div class="input-icon flex-grow-1" style="max-width: 420px">
                <i class="bi bi-search"></i>
                <input v-model="search" type="search" class="form-control"
                    :placeholder="tab === 'users' ? 'Search name or email' : 'Search event or organiser'" aria-label="Search" />
            </div>
            <div class="segmented">
                <button v-for="f in filters" :key="f.value" type="button" :class="{ active: filter === f.value }" @click="filter = f.value">
                    {{ f.label }}
                </button>
            </div>
        </div>

        <div class="card-b overflow-hidden">
            <div v-if="loading && !page" class="p-4 d-grid gap-2"><div v-for="i in 5" :key="i" class="skeleton" style="height: 44px"></div></div>
            <EmptyState v-else-if="loadError" icon="bi-wifi-off" title="Couldn't load this list" :text="loadError">
                <button class="btn btn-soft" @click="load()">Try again</button>
            </EmptyState>
            <EmptyState v-else-if="page && !page.data.length" icon="bi-search" title="Nothing found" text="Try another search or filter." />

            <!-- Organisers -->
            <div v-else-if="tab === 'users' && page" class="table-responsive" :class="{ 'opacity-50': loading }">
                <table class="table-b">
                    <thead><tr><th>Organiser</th><th>Joined</th><th>Events</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        <tr v-for="u in (page.data as PlatformUser[])" :key="u.id">
                            <td>
                                <div class="fw-semibold text-ink text-break">{{ u.name }} <span v-if="u.is_admin" class="pill pill-violet ms-1">Admin</span></div>
                                <div class="small text-muted text-break">{{ u.email }}</div>
                            </td>
                            <td class="text-nowrap small">{{ formatDate(u.created_at) }}</td>
                            <td>{{ formatNumber(u.events_count) }}</td>
                            <td class="text-nowrap">
                                <span v-if="u.suspended_at" class="pill pill-danger"><span class="dot"></span>Suspended</span>
                                <span v-else-if="!u.email_verified_at" class="pill pill-upcoming"><span class="dot"></span>Unconfirmed</span>
                                <span v-else class="pill pill-active"><span class="dot"></span>Active</span>
                                <i v-if="u.two_factor_enabled" class="bi bi-shield-check text-brand ms-1" title="Two-step login on"></i>
                            </td>
                            <td class="text-end text-nowrap">
                                <template v-if="u.id !== auth.user?.id && !u.is_admin">
                                    <button v-if="u.suspended_at" class="btn btn-soft btn-sm" :disabled="acting === u.id" @click="unsuspend(u)">
                                        <i class="bi bi-arrow-counterclockwise"></i> Restore
                                    </button>
                                    <button v-else class="btn btn-danger-soft btn-sm" :disabled="acting === u.id" @click="suspend(u)">
                                        <i class="bi bi-slash-circle"></i> Suspend
                                    </button>
                                </template>
                                <span v-else-if="u.id === auth.user?.id" class="small text-muted">You</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Events -->
            <div v-else-if="tab === 'events' && page" class="table-responsive" :class="{ 'opacity-50': loading }">
                <table class="table-b">
                    <thead><tr><th>Event</th><th>Organiser</th><th>Status</th><th>Turnout</th><th>Created</th></tr></thead>
                    <tbody>
                        <tr v-for="ev in (page.data as PlatformEvent[])" :key="ev.id">
                            <td>
                                <div class="fw-semibold text-ink text-break">{{ ev.name }}</div>
                                <div class="small text-muted">{{ ev.selection_mode === 'multiple' ? 'Multiple choice' : 'One per category' }} · {{ visibilityLabel[ev.results_visibility] }}</div>
                            </td>
                            <td>
                                <div class="small text-ink text-break">{{ ev.user?.name ?? '—' }}</div>
                                <div class="small text-muted text-break">
                                    {{ ev.user?.email }}
                                    <span v-if="ev.user?.suspended_at" class="text-danger fw-semibold">· suspended</span>
                                </div>
                            </td>
                            <td><StatusBadge :status="ev.status" /></td>
                            <td class="text-nowrap small">
                                {{ formatNumber(ev.voted_count) }} / {{ formatNumber(ev.voters_count) }}
                                <span v-if="ev.voters_count" class="text-muted">({{ percent(ev.voted_count, ev.voters_count) }}%)</span>
                            </td>
                            <td class="text-nowrap small">{{ formatDate(ev.created_at) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="page && page.last_page > 1" class="d-flex justify-content-between align-items-center px-3 py-2 border-top small">
                <span class="text-muted">Page {{ page.current_page }} of {{ page.last_page }} · {{ formatNumber(page.total) }} total</span>
                <div class="d-flex gap-1">
                    <button class="btn btn-ghost btn-sm" :disabled="loading || page.current_page <= 1" @click="go(page.current_page - 1)"><i class="bi bi-chevron-left"></i> Prev</button>
                    <button class="btn btn-ghost btn-sm" :disabled="loading || page.current_page >= page.last_page" @click="go(page.current_page + 1)">Next <i class="bi bi-chevron-right"></i></button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { platformApi } from '~/api/endpoints/admin';
import { useAuthStore } from '~/stores/auth';
import type { Paginated, PlatformEvent, PlatformOverview, PlatformUser, ResultsVisibility } from '~/types';

definePageMeta({ layout: 'dashboard', middleware: ['auth', 'admin'] });
useHead({ title: 'Platform admin · Ballotify' });

const auth = useAuthStore();
const toast = useToast();
const { confirm } = useConfirm();

const visibilityLabel: Record<ResultsVisibility, string> = { live: 'Live results', after_close: 'Results after close', private: 'Private results' };

const ov = ref<PlatformOverview | null>(null);
const tab = ref<'users' | 'events'>('users');
const search = ref('');
const filter = ref('all');
const page = ref<Paginated<PlatformUser | PlatformEvent> | null>(null);
const loading = ref(false);
const loadError = ref('');
const acting = ref<number | null>(null);

const filters = computed(() => tab.value === 'users'
    ? [{ value: 'all', label: 'All' }, { value: 'suspended', label: 'Suspended' }, { value: 'unverified', label: 'Unconfirmed' }, { value: 'admins', label: 'Admins' }]
    : [{ value: 'all', label: 'All' }, { value: 'active', label: 'Open' }, { value: 'upcoming', label: 'Scheduled' }, { value: 'completed', label: 'Closed' }]);

let requestId = 0;
async function load(pageNo = 1) {
    const mine = ++requestId;
    loading.value = true;
    loadError.value = '';
    try {
        const term = search.value.trim() || undefined;
        const f = filter.value === 'all' ? undefined : filter.value;
        const { data } = tab.value === 'users'
            ? await platformApi.users({ search: term, filter: f, page: pageNo })
            : await platformApi.events({ search: term, status: f, page: pageNo });
        if (mine === requestId) page.value = data;
    } catch (e) {
        if (mine === requestId) loadError.value = apiErrorMessage(e);
    } finally {
        if (mine === requestId) loading.value = false;
    }
}
const go = (n: number) => load(n);

async function loadOverview() {
    try { ov.value = (await platformApi.overview()).data; } catch { /* the stats are optional */ }
}

watch(tab, () => { filter.value = 'all'; search.value = ''; page.value = null; load(); });
watch(filter, () => load());
let debounce: ReturnType<typeof setTimeout> | undefined;
watch(search, () => { clearTimeout(debounce); debounce = setTimeout(() => load(), 300); });
onBeforeUnmount(() => clearTimeout(debounce));

onMounted(() => { loadOverview(); load(); });

function replaceUser(updated: PlatformUser) {
    if (!page.value) return;
    page.value = { ...page.value, data: page.value.data.map(u => (u.id === updated.id ? updated : u)) };
}

/** What suspending does to the organiser's events, worded for 0, 1 or many. */
function suspendImpact(count: number): string {
    if (count === 0) return 'They have no events yet.';
    if (count === 1) return 'Their event will go offline: its voting link and results page will stop working.';
    return `All ${count} of their events will go offline: voting links and results pages will stop working.`;
}

async function suspend(u: PlatformUser) {
    const ok = await confirm({
        title: `Suspend ${u.name}?`,
        message: `They’ll be signed out everywhere and won’t be able to sign in. ${suspendImpact(u.events_count)} You can restore the account at any time.`,
        confirmText: 'Suspend account',
        danger: true,
    });
    if (!ok) return;
    acting.value = u.id;
    try {
        replaceUser((await platformApi.suspend(u.id)).data);
        toast.success(`${u.name} suspended`);
        loadOverview();
    } catch (e) {
        toast.error(apiErrorMessage(e));
    } finally {
        acting.value = null;
    }
}

async function unsuspend(u: PlatformUser) {
    acting.value = u.id;
    try {
        replaceUser((await platformApi.unsuspend(u.id)).data);
        toast.success(`${u.name} restored`);
        loadOverview();
    } catch (e) {
        toast.error(apiErrorMessage(e));
    } finally {
        acting.value = null;
    }
}
</script>
