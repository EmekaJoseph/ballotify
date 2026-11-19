<template>
    <BaseModal :open="limitOpen" :title="allowed ? 'You can create an event' : 'You’ve reached your free event limit'" size="md" @close="close">
        <!-- The allowance freed up since we last checked (event closed, upgraded or deleted). -->
        <div v-if="allowed" class="text-center py-2">
            <div class="icon-tile lg mx-auto mb-3"><i class="bi bi-check2-circle"></i></div>
            <p class="text-muted mb-0">Your account has room for a new event again.</p>
        </div>

        <template v-else>
            <div class="d-flex gap-3 align-items-start mb-4">
                <div class="icon-tile flex-shrink-0"><i class="bi bi-box"></i></div>
                <p class="text-muted mb-0">
                    Free accounts can run <b class="text-ink">{{ limitText }}</b> at a time.
                    To start another, upgrade {{ events.length === 1 ? 'your open event' : 'one of your open events' }} to Pro,
                    or close voting on it when you’re done.
                </p>
            </div>

            <div class="small-caps mb-2">{{ events.length === 1 ? 'Your open event' : 'Your open events' }}</div>
            <ul class="list-unstyled d-grid gap-2 mb-3">
                <li v-for="e in events" :key="e.id" class="open-event">
                    <div class="min-w-0 flex-grow-1">
                        <div class="fw-bold text-ink text-truncate">{{ e.name }}</div>
                        <div class="small text-muted">{{ e.ends_at ? `Voting ends ${formatDate(e.ends_at, true)}` : 'No end date set' }}</div>
                    </div>
                    <div class="d-flex gap-2 flex-shrink-0">
                        <button class="btn btn-ghost btn-sm" @click="go(e.id, 'settings')">Manage</button>
                        <button v-if="plans?.payments_enabled" class="btn btn-primary btn-sm" :disabled="upgradingId !== null" @click="upgrade(e.id)">
                            <span v-if="upgradingId === e.id" class="spinner-border spinner-border-sm"></span>
                            <i v-else class="bi bi-lightning-charge"></i> {{ upgradingId === e.id ? 'Please wait…' : 'Upgrade' }}
                        </button>
                        <button v-else class="btn btn-primary btn-sm" @click="go(e.id)">Upgrade</button>
                    </div>
                </li>
            </ul>

            <div class="tips small text-muted d-grid gap-2">
                <div v-if="plans"><i class="bi bi-gem text-brand"></i> Pro is a one-off <b class="text-ink">{{ priceLabel }}</b> per event and doesn’t count towards your free limit.</div>
                <div><i class="bi bi-stop-circle"></i> Closed events don’t count either. Close voting from the event’s <b class="text-ink">Settings</b> tab.</div>
            </div>
        </template>

        <template #footer>
            <button class="btn btn-ghost" @click="close">{{ allowed ? 'Cancel' : 'Not now' }}</button>
            <button v-if="allowed" class="btn btn-primary" @click="createNow"><i class="bi bi-plus-lg"></i> New event</button>
        </template>
    </BaseModal>
</template>

<script setup lang="ts">
const { quota, limitOpen } = useNewEvent();
const { plans, priceLabel, startUpgrade } = useBilling();

const events = computed(() => quota.value?.open_events ?? []);
const allowed = computed(() => quota.value?.can_create === true);
const limitText = computed(() => {
    const max = quota.value?.max ?? 1;
    return max === 1 ? 'one event' : `${max} events`;
});

const upgradingId = ref<number | null>(null);

function close() {
    limitOpen.value = false;
    upgradingId.value = null;
}

async function go(id: number, tab?: string) {
    close();
    await navigateTo({ path: `/dashboard/events/${id}`, query: tab ? { tab } : {} });
}

async function upgrade(id: number) {
    upgradingId.value = id;
    await startUpgrade(id); // leaves for Paystack; on failure it shows a toast
    upgradingId.value = null;
}

async function createNow() {
    close();
    await navigateTo('/dashboard/events/create');
}
</script>

<style scoped>
.open-event {
    display: flex; align-items: center; gap: .75rem; flex-wrap: wrap;
    padding: .75rem .9rem; border: 1px solid var(--line); border-radius: 14px; background: var(--surface-2);
}
.tips .bi { margin-right: .35rem; }
</style>
