<template>
    <!-- Platform account log: sign-ins, account security changes and admin actions. -->
    <div class="card-b overflow-hidden">
        <div class="list-tools">
            <div class="input-icon flex-grow-1">
                <i class="bi bi-search"></i>
                <input v-model="search" type="search" class="form-control" placeholder="Search email, name or text" @keydown.enter="load(1)" />
            </div>
            <AppSelect v-model="group" :options="GROUPS" :block="false" class="filter-select" aria-label="Show entries" @change="load(1)" />
            <button class="btn btn-ghost btn-icon" :disabled="loading" title="Refresh" aria-label="Refresh" @click="load(page?.current_page ?? 1)">
                <i class="bi bi-arrow-clockwise" :class="{ spin: loading }"></i>
            </button>
        </div>

        <div v-if="loading && !page" class="p-4 d-grid gap-2"><div v-for="i in 6" :key="i" class="skeleton" style="height: 38px"></div></div>
        <EmptyState v-else-if="page && !page.data.length" icon="bi-journal-text" title="Nothing logged yet"
            text="Sign-ins, account security changes and admin actions on accounts appear here." />

        <div v-else-if="page" class="table-responsive" :class="{ 'opacity-50': loading }">
            <table class="table-b">
                <thead><tr><th>When</th><th>What happened</th><th>Account</th><th>By</th><th>IP address</th></tr></thead>
                <tbody>
                    <tr v-for="e in page.data" :key="e.id">
                        <td class="small text-nowrap" :title="formatDate(e.created_at, true)">{{ when(e.created_at) }}</td>
                        <td style="min-width: 240px">
                            <span class="kind" :class="tone(e.action)"><i class="bi" :class="icon(e.action)"></i></span>
                            <span class="text-ink">{{ e.description }}</span>
                        </td>
                        <td class="small text-break">
                            <template v-if="e.user">
                                <div class="fw-semibold text-ink">{{ e.user.name }}</div>
                                <div class="text-muted">{{ e.user.email }}</div>
                            </template>
                            <span v-else-if="e.user_email" class="text-muted">{{ e.user_email }} <span class="fst-italic">(deleted)</span></span>
                            <span v-else class="text-muted">—</span>
                        </td>
                        <td class="small text-break">
                            <template v-if="e.actor && e.actor.id === e.user?.id"><span class="text-muted">Themselves</span></template>
                            <template v-else-if="e.actor"><span class="fw-semibold text-ink">{{ e.actor.name }}</span></template>
                            <span v-else class="text-muted">{{ e.action.startsWith('auth.') ? '—' : 'System' }}</span>
                        </td>
                        <td class="small mono text-nowrap text-muted">{{ e.ip || '—' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="page && page.last_page > 1" class="d-flex justify-content-between align-items-center px-3 py-2 border-top small">
            <span class="text-muted">Page {{ page.current_page }} of {{ page.last_page }} · {{ formatNumber(page.total) }} entries</span>
            <div class="d-flex gap-1">
                <button class="btn btn-ghost btn-sm" :disabled="page.current_page <= 1 || loading" @click="load(page.current_page - 1)">Newer</button>
                <button class="btn btn-ghost btn-sm" :disabled="page.current_page >= page.last_page || loading" @click="load(page.current_page + 1)">Older</button>
            </div>
        </div>
        <p class="small text-muted px-3 py-2 mb-0 border-top">Kept for a year. Passwords, codes and votes are never recorded.</p>
    </div>
</template>

<script setup lang="ts">
import { platformApi } from '~/api/endpoints/admin';
import type { AccountLogEntry, Paginated } from '~/types';

const toast = useToast();
const GROUPS = [
    { value: '', label: 'Everything' },
    { value: 'admin', label: 'Admin actions' },
    { value: 'signin', label: 'Sign-ins' },
    { value: 'failed', label: 'Failed sign-ins' },
    { value: 'security', label: 'Account changes' },
];

const page = ref<Paginated<AccountLogEntry> | null>(null);
const loading = ref(false);
const search = ref('');
const group = ref('');

async function load(p = 1) {
    loading.value = true;
    try {
        const failed = group.value === 'failed';
        page.value = (await platformApi.logs({
            search: search.value || undefined,
            group: failed ? 'signin' : group.value || undefined,
            failed: failed || undefined,
            page: p,
        })).data;
    } catch (e) {
        toast.error(apiErrorMessage(e));
    } finally {
        loading.value = false;
    }
}
onMounted(() => load(1));

/** "5 min ago" for today, otherwise the date and time. */
function when(at: string): string {
    const d = new Date(at);
    const mins = Math.round((Date.now() - d.getTime()) / 60000);
    if (mins < 1) return 'just now';
    if (mins < 60) return `${mins} min ago`;
    if (mins < 60 * 12) return `${Math.round(mins / 60)} h ago`;
    return formatDate(at, true);
}
function icon(action: string): string {
    if (action === 'auth.login') return 'bi-box-arrow-in-right';
    if (action === 'auth.login_failed') return 'bi-shield-exclamation';
    if (action.startsWith('account.2fa')) return 'bi-shield-lock';
    if (action === 'account.password') return 'bi-key';
    if (action === 'account.created') return 'bi-person-plus';
    if (action === 'account.deleted') return 'bi-person-x';
    if (action === 'admin.suspended') return 'bi-slash-circle';
    if (action === 'admin.approved' || action === 'admin.restored') return 'bi-check2-circle';
    return 'bi-person-gear';
}
function tone(action: string): string {
    if (action === 'auth.login_failed' || action === 'admin.suspended' || action === 'account.deleted' || action === 'admin.unapproved') return 'bad';
    if (action.startsWith('admin.')) return 'admin';
    return '';
}
</script>

<style scoped>
.list-tools { display: flex; gap: .6rem; flex-wrap: wrap; padding: .85rem 1rem; border-bottom: 1px solid var(--line); }
.filter-select { min-width: 190px; }
@media (max-width: 575.98px) { .filter-select { min-width: 0; flex: 1 1 auto; } }
.kind { display: inline-grid; place-items: center; width: 26px; height: 26px; border-radius: 8px; margin-right: .5rem; font-size: .85rem; background: var(--surface-2); color: var(--ink-2); vertical-align: middle; }
.kind.admin { background: var(--brand-50); color: var(--brand-700); }
.kind.bad { background: var(--danger-50); color: var(--danger); }
.spin { animation: spin 1s linear infinite; display: inline-block; }
@keyframes spin { to { transform: rotate(360deg); } }
</style>
