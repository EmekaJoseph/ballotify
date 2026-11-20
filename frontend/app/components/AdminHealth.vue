<template>
    <div>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <p class="small text-muted mb-0">Is email and SMS delivery working? Checked {{ checkedAt ? formatDate(checkedAt.toISOString(), true) : '…' }}.</p>
            <button class="btn btn-ghost btn-sm" :disabled="loading" @click="load"><i class="bi bi-arrow-clockwise" :class="{ spin: loading }"></i> Refresh</button>
        </div>

        <div v-if="h" class="row g-3 mb-4">
            <div v-for="c in checks" :key="c.label" class="col-sm-6 col-xl-3">
                <div class="card-b pad h-100 check" :class="c.state">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <i class="bi" :class="c.state === 'good' ? 'bi-check-circle-fill' : c.state === 'warn' ? 'bi-exclamation-triangle-fill' : 'bi-x-octagon-fill'"></i>
                        <span class="fw-800 text-ink">{{ c.label }}</span>
                    </div>
                    <div class="small text-muted">{{ c.text }}</div>
                </div>
            </div>
        </div>

        <div v-if="h" class="row g-4">
            <div class="col-lg-7">
                <div class="card-b overflow-hidden">
                    <div class="card-header-b">
                        <h3 class="h6 fw-800 mb-0">Failed jobs <span class="text-muted fw-normal">({{ h.queue.failed }})</span></h3>
                        <div v-if="h.failed_jobs.length" class="d-flex gap-2">
                            <button class="btn btn-soft btn-sm" :disabled="busy" @click="retry('all')"><i class="bi bi-arrow-repeat"></i> Retry all</button>
                            <button class="btn btn-ghost btn-sm" :disabled="busy" @click="forget('all')">Clear</button>
                        </div>
                    </div>
                    <EmptyState v-if="!h.failed_jobs.length" icon="bi-check2-all" title="No failed jobs" text="Every queued email and SMS was processed." />
                    <ul v-else class="list-unstyled mb-0">
                        <li v-for="j in h.failed_jobs" :key="j.uuid" class="row-i">
                            <div class="min-w-0 flex-grow-1">
                                <div class="fw-semibold text-ink">{{ j.job }} <span class="small text-muted fw-normal">· {{ formatDate(j.failed_at, true) }}</span></div>
                                <div class="small text-danger text-break">{{ j.error }}</div>
                            </div>
                            <button class="btn btn-ghost btn-icon btn-sm" title="Retry" aria-label="Retry job" :disabled="busy" @click="retry(j.uuid)"><i class="bi bi-arrow-repeat"></i></button>
                            <button class="btn btn-ghost btn-icon btn-sm" title="Remove" aria-label="Remove job" :disabled="busy" @click="forget(j.uuid)"><i class="bi bi-x-lg"></i></button>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card-b overflow-hidden">
                    <div class="card-header-b"><h3 class="h6 fw-800 mb-0">Delivery errors, last 7 days <span class="text-muted fw-normal">({{ h.delivery_errors.last_7_days }})</span></h3></div>
                    <EmptyState v-if="!h.delivery_errors.recent.length" icon="bi-envelope-check" title="No delivery errors" />
                    <ul v-else class="list-unstyled mb-0">
                        <li v-for="(d, i) in h.delivery_errors.recent" :key="i" class="row-i d-block">
                            <div class="small fw-semibold text-ink">{{ d.event || 'Deleted event' }} <span class="text-muted fw-normal">· {{ formatDate(d.at, true) }}</span></div>
                            <div class="small text-muted text-break">{{ d.error }}</div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <div v-else-if="loading" class="row g-3"><div v-for="i in 4" :key="i" class="col-sm-6 col-xl-3"><div class="skeleton" style="height: 96px"></div></div></div>
    </div>
</template>

<script setup lang="ts">
import { platformApi } from '~/api/endpoints/admin';
import type { SystemHealth } from '~/types';

const toast = useToast();
const h = ref<SystemHealth | null>(null);
const loading = ref(true);
const busy = ref(false);
const checkedAt = ref<Date | null>(null);

async function load() {
    loading.value = true;
    try { h.value = (await platformApi.health()).data; checkedAt.value = new Date(); }
    catch (e) { toast.error(apiErrorMessage(e)); }
    finally { loading.value = false; }
}
onMounted(load);

type State = 'good' | 'warn' | 'bad';
const checks = computed(() => {
    const x = h.value!;
    const queue: { state: State; text: string } = x.queue.failed > 0
        ? { state: 'bad', text: `${x.queue.failed} failed job(s). Retry them below.` }
        : x.queue.needs_worker
            ? { state: 'good', text: `Driver “${x.queue.driver}”${x.queue.pending !== null ? `, ${x.queue.pending} waiting` : ''}. Keep “php artisan queue:work” running.` }
            : { state: 'good', text: 'Messages are sent straight after each request (no worker needed).' };
    return [
        { label: 'Queue', ...queue },
        x.scheduler.running
            ? { label: 'Scheduler', state: 'good' as State, text: 'Running: reminders and clean-up happen on time.' }
            : { label: 'Scheduler', state: 'warn' as State, text: x.scheduler.last_run ? `Last ran ${formatDate(x.scheduler.last_run, true)}. Check the cron entry.` : 'Never ran. Add the cron entry for “php artisan schedule:run”.' },
        ['log', 'array'].includes(x.mail.driver)
            ? { label: 'Email', state: 'warn' as State, text: `Driver “${x.mail.driver}”: emails are written to the log, not sent.` }
            : { label: 'Email', state: 'good' as State, text: `Sending with “${x.mail.driver}” from ${x.mail.from ?? '(no from address)'}.` },
        x.paystack
            ? x.paystack_down
                ? { label: 'Paystack', state: 'bad' as State, text: x.flutterwave ? 'Failing right now: payers are being sent to Flutterwave.' : 'Failing right now, and no backup provider is set up.' }
                : { label: 'Paystack', state: 'good' as State, text: 'Connected: upgrades and paid voting work.' }
            : { label: 'Paystack', state: 'warn' as State, text: x.flutterwave ? 'Not set up: Flutterwave takes all payments.' : 'Not set up: no online payments yet.' },
        x.flutterwave
            ? { label: 'Flutterwave', state: 'good' as State, text: 'Connected as the backup: used when Paystack is down.' }
            : { label: 'Flutterwave', state: 'warn' as State, text: 'No backup provider: paid voting stops if Paystack is down.' },
    ];
});

async function retry(uuid: string) {
    busy.value = true;
    try { await platformApi.retryFailedJob(uuid); toast.success(uuid === 'all' ? 'All failed jobs queued again' : 'Job queued again'); await load(); }
    catch (e) { toast.error(apiErrorMessage(e)); }
    finally { busy.value = false; }
}
async function forget(uuid: string) {
    busy.value = true;
    try { await platformApi.forgetFailedJob(uuid); toast.success(uuid === 'all' ? 'Failed jobs cleared' : 'Job removed'); await load(); }
    catch (e) { toast.error(apiErrorMessage(e)); }
    finally { busy.value = false; }
}
</script>

<style scoped>
.check.good .bi { color: var(--success); }
.check.warn .bi { color: var(--warning); }
.check.bad .bi { color: var(--danger); }
.check.bad { border-color: var(--danger-line, var(--danger)); }
.row-i { display: flex; align-items: center; gap: .6rem; padding: .7rem 1.25rem; border-bottom: 1px solid var(--line); }
.row-i:last-child { border-bottom: 0; }
.spin { animation: spin 1s linear infinite; display: inline-block; }
@keyframes spin { to { transform: rotate(360deg); } }
</style>
