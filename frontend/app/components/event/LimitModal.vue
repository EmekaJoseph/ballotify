<template>
    <BaseModal :open="limitOpen" :title="allowed ? 'You can go live now' : 'You already have a live free event'" size="md" @close="close">
        <!-- The allowance freed up since we last checked (event closed or upgraded). -->
        <div v-if="allowed" class="text-center py-2">
            <div class="icon-tile lg mx-auto mb-3"><i class="bi bi-check2-circle"></i></div>
            <p class="text-muted mb-0">Your account has room for another live event. Try adding the voters again.</p>
        </div>

        <template v-else>
            <div class="d-flex gap-3 align-items-start mb-4">
                <div class="icon-tile flex-shrink-0"><i class="bi bi-broadcast"></i></div>
                <p class="text-muted mb-0">
                    Free accounts can run <b class="text-ink">{{ limitText }}</b> at a time (drafts are unlimited).
                    An event goes live when it gets its first voters. To add voters
                    <template v-if="limitEvent">to <b class="text-ink">{{ limitEvent.name }}</b></template>, upgrade it to Pro,
                    or upgrade or close the event that’s live.
                </p>
            </div>

            <div v-if="limitEvent" class="open-event mb-3 this-event">
                <div class="min-w-0 flex-grow-1">
                    <div class="small-caps">This event</div>
                    <div class="fw-bold text-ink text-truncate">{{ limitEvent.name }}</div>
                </div>
                <button class="btn btn-primary btn-sm flex-shrink-0" :disabled="upgradingId !== null" @click="upgrade(limitEvent.id)">
                    <span v-if="upgradingId === limitEvent.id" class="spinner-border spinner-border-sm"></span>
                    <i v-else class="bi bi-lightning-charge"></i> {{ upgradingId === limitEvent.id ? 'Please wait…' : 'Upgrade this event' }}
                </button>
            </div>

            <div class="small-caps mb-2">{{ events.length === 1 ? 'Your live event' : 'Your live events' }}</div>
            <ul class="list-unstyled d-grid gap-2 mb-3">
                <li v-for="e in events" :key="e.id" class="open-event">
                    <div class="min-w-0 flex-grow-1">
                        <div class="fw-bold text-ink text-truncate">{{ e.name }}</div>
                        <div class="small text-muted">{{ e.ends_at ? `Voting ends ${formatDate(e.ends_at, true)}` : 'No end date set' }}</div>
                    </div>
                    <div class="d-flex gap-2 flex-shrink-0">
                        <button class="btn btn-ghost btn-sm" @click="go(e.id, 'settings')">Close voting</button>
                        <button class="btn btn-soft btn-sm" :disabled="upgradingId !== null" @click="upgrade(e.id)">
                            <span v-if="upgradingId === e.id" class="spinner-border spinner-border-sm"></span>
                            <i v-else class="bi bi-lightning-charge"></i> {{ upgradingId === e.id ? 'Please wait…' : 'Upgrade' }}
                        </button>
                    </div>
                </li>
            </ul>

            <div class="tips small text-muted d-grid gap-2">
                <div v-if="plans"><span class="pill pill-pro me-1"><i class="bi bi-lightning-charge-fill"></i> Pro</span> Pro is a one-off <b class="text-ink">{{ priceLabel }}</b> per event and doesn’t count towards your free limit.</div>
                <div><i class="bi bi-stop-circle"></i> Closed events don’t count either. Close voting from the event’s <b class="text-ink">Settings</b> tab.</div>
            </div>
        </template>

        <template #footer>
            <button class="btn btn-ghost" @click="close">{{ allowed ? 'OK' : 'Not now' }}</button>
        </template>
    </BaseModal>
</template>

<script setup lang="ts">
const { quota, limitOpen, limitEvent } = useNewEvent();
const { plans, priceLabel, startUpgrade } = useBilling();
const toast = useToast();

const events = computed(() => (quota.value?.open_events ?? []).filter(e => e.id !== limitEvent.value?.id));
const allowed = computed(() => quota.value?.can_go_live === true);
const limitText = computed(() => {
    const max = quota.value?.max ?? 1;
    return max === 1 ? 'one live event' : `${max} live events`;
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
    if (!plans.value?.payments_enabled) {
        toast.info('Online payment isn’t available yet. Please contact us to upgrade.');
        return;
    }
    upgradingId.value = id;
    await startUpgrade(id); // leaves for Paystack; on failure it shows a toast
    upgradingId.value = null;
}
</script>

<style scoped>
.open-event {
    display: flex; align-items: center; gap: .75rem; flex-wrap: wrap;
    padding: .75rem .9rem; border: 1px solid var(--line); border-radius: 14px; background: var(--surface-2);
}
.this-event { border-color: var(--brand-200); background: var(--brand-50); }
.tips .bi { margin-right: .35rem; }
</style>
