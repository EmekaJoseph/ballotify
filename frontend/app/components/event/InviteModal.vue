<template>
    <BaseModal :open="open" :title="title" subtitle="Each voter receives the voting link and their own personal code." @close="emit('close')">
        <!-- Delivery mode notices -->
        <div v-if="status && (testModeFor.length || needsWorker)" class="notice mb-3">
            <i class="bi bi-info-circle-fill"></i>
            <div>
                <div v-if="testModeFor.length">
                    <b>{{ testModeFor.join(' & ') }} {{ testModeFor.length > 1 ? 'are' : 'is' }} in test mode.</b>
                    Messages are written to the server log, not delivered, until credentials are added to the backend <code>.env</code>.
                </div>
                <div v-if="needsWorker" :class="{ 'mt-1': testModeFor.length }">
                    Messages are sent in the background. Keep the queue worker running (<code>composer dev</code> or <code>php artisan queue:work</code>).
                </div>
            </div>
        </div>

        <!-- One person: show who and where it goes -->
        <div v-if="targets && targets.length === 1 && targets[0]" class="who mb-4">
            <span class="avatar sm" :style="{ background: colorFor(targets[0].name || targets[0].code) }">{{ initials(targets[0].name || '?') }}</span>
            <div class="min-w-0">
                <div class="fw-bold text-ink truncate">{{ targets[0].name || 'Anonymous voter' }}</div>
                <div class="small text-muted d-flex flex-wrap column-gap-3">
                    <span v-if="targets[0].email"><i class="bi bi-envelope me-1"></i>{{ targets[0].email }}</span>
                    <span v-if="targets[0].phone"><i class="bi bi-phone me-1"></i>{{ targets[0].phone }}</span>
                </div>
            </div>
            <span v-if="targets[0].invited_at" class="pill pill-violet ms-auto flex-shrink-0" :title="formatDate(targets[0].invited_at, true)">Sent before</span>
        </div>

        <span class="form-label d-block">Send by</span>
        <div class="row g-2 mb-4">
            <div v-for="c in channels" :key="c.value" class="col-4">
                <label class="choice flex-column align-items-start gap-2 h-100" :class="{ selected: channel === c.value, disabled: c.count === 0 }"
                    :title="c.count === 0 ? c.missing : ''">
                    <input v-model="channel" type="radio" class="visually-hidden" :value="c.value" :disabled="c.count === 0" />
                    <span class="icon-tile" :class="c.tone"><i class="bi" :class="c.icon"></i></span>
                    <span>
                        <span class="d-block fw-bold text-ink">{{ c.label }}</span>
                        <span class="d-block small text-muted">{{ isSingle ? (c.count ? 'Available' : c.missing) : `${c.count} reachable` }}</span>
                    </span>
                </label>
            </div>
        </div>

        <!-- Whole list: choose the audience -->
        <template v-if="!targets">
            <span class="form-label d-block">Who</span>
            <div class="d-grid gap-2">
                <label class="choice align-items-center" :class="{ selected: audience === 'not_invited' }">
                    <input v-model="audience" type="radio" class="form-check-input mt-0" value="not_invited" />
                    <span class="flex-grow-1"><span class="fw-bold text-ink">Not invited yet</span>
                        <span class="d-block small text-muted">Voters who haven't been sent their code</span></span>
                    <span class="pill pill-brand">{{ audienceCount('not_invited') }}</span>
                </label>
                <label class="choice align-items-center" :class="{ selected: audience === 'pending' }">
                    <input v-model="audience" type="radio" class="form-check-input mt-0" value="pending" />
                    <span class="flex-grow-1"><span class="fw-bold text-ink">Everyone who hasn't voted</span>
                        <span class="d-block small text-muted">Includes a reminder for people already invited</span></span>
                    <span class="pill pill-brand">{{ audienceCount('pending') }}</span>
                </label>
            </div>
        </template>

        <div v-if="sms && !sms.unlimited && smsNeeded > 0" class="sms-line mt-3" :class="{ short: smsNeeded > smsAvailable }">
            <i class="bi bi-chat-dots"></i>
            <span class="flex-grow-1">
                Uses <b>{{ formatNumber(smsNeeded) }}</b> SMS · {{ formatNumber(sms.left) }} left with this event + {{ formatNumber(sms.credits) }} credits.
                <template v-if="smsNeeded > smsAvailable"> <b>{{ formatNumber(smsNeeded - smsAvailable) }} short.</b></template>
            </span>
            <button type="button" class="btn btn-ghost btn-sm flex-shrink-0" @click="buyCredits(smsNeeded - smsAvailable)"><i class="bi bi-cart-plus"></i> Buy</button>
        </div>
        <p v-if="skipped > 0" class="small text-muted mt-3 mb-0">
            <i class="bi bi-skip-forward"></i> {{ skipped }} selected voter{{ skipped === 1 ? '' : 's' }} will be skipped
            (already voted, or no {{ channel === 'sms' ? 'phone number' : channel === 'email' ? 'email' : 'contact' }}).
        </p>
        <p v-else-if="!isSingle" class="small text-muted mt-3 mb-0"><i class="bi bi-shield-check"></i> People who have already voted are never messaged.</p>

        <template #footer>
            <button class="btn btn-ghost" @click="emit('close')">Cancel</button>
            <button class="btn btn-primary" :disabled="busy || total === 0" @click="send">
                <span v-if="busy" class="spinner-border spinner-border-sm"></span>
                <i v-else class="bi bi-send"></i>
                {{ buttonLabel }}
            </button>
        </template>
    </BaseModal>
</template>

<script setup lang="ts">
import { adminApi } from '~/api/endpoints/admin';
import type { InviteChannel, MessagingStatus, SmsStatus, Voter } from '~/types';

/**
 * targets = null  → whole list, organiser picks an audience
 * targets = [...] → exactly these voters (one person, or a checkbox selection)
 */
const props = defineProps<{ open: boolean; eventId: number; voters: Voter[]; targets: Voter[] | null; sms?: SmsStatus | null }>();
const { openBuySms } = useBilling();
/** SMS this send would use (only voters with a phone) vs what's available. */
const smsNeeded = computed(() => (channel.value === 'email' ? 0
    : (props.targets ? pool.value : pool.value.filter(v => audience.value === 'pending' || !v.invited_at)).filter(v => !!v.phone).length));
const smsAvailable = computed(() => (props.sms ? props.sms.left + props.sms.credits : Infinity));
function buyCredits(short: number) {
    emit('close');
    openBuySms({ eventId: props.eventId, suggest: Math.max(0, short) });
}
const emit = defineEmits<{ close: []; sent: [] }>();

const toast = useToast();
const status = ref<MessagingStatus | null>(null);
const channel = ref<InviteChannel>('email');
const audience = ref<'not_invited' | 'pending'>('not_invited');
const busy = ref(false);

const isSingle = computed(() => props.targets?.length === 1);
const title = computed(() => {
    if (!props.targets) return 'Send voting codes';
    if (props.targets.length === 1) return 'Send voting code';
    return `Send codes to ${props.targets.length} selected voters`;
});

const reach = (v: Voter, ch: InviteChannel) => (ch === 'email' ? !!v.email : ch === 'sms' ? !!v.phone : !!(v.email || v.phone));
/** Voters this send could reach, before the channel filter. */
const pool = computed(() => (props.targets ?? props.voters).filter(v => !v.used_at));

const channels = computed(() => [
    { value: 'email' as const, label: 'Email', icon: 'bi-envelope', tone: '', missing: 'No email', count: pool.value.filter(v => reach(v, 'email')).length },
    { value: 'sms' as const, label: 'SMS', icon: 'bi-chat-dots', tone: 'violet', missing: 'No phone', count: pool.value.filter(v => reach(v, 'sms')).length },
    { value: 'both' as const, label: 'Both', icon: 'bi-send', tone: 'green', missing: 'No contact', count: pool.value.filter(v => reach(v, 'both')).length },
]);

const audienceCount = (a: 'not_invited' | 'pending') =>
    pool.value.filter(v => reach(v, channel.value) && (a === 'pending' || !v.invited_at)).length;

const total = computed(() => (props.targets
    ? pool.value.filter(v => reach(v, channel.value)).length
    : audienceCount(audience.value)));

const skipped = computed(() => (props.targets && props.targets.length > 1 ? props.targets.length - total.value : 0));

const buttonLabel = computed(() => {
    if (total.value === 0) return 'Nobody to send to';
    if (isSingle.value) return props.targets?.[0]?.invited_at ? 'Resend code' : 'Send code';
    return `Send to ${total.value} voter${total.value === 1 ? '' : 's'}`;
});

const testModeFor = computed(() => {
    if (!status.value) return [];
    const out: string[] = [];
    if (channel.value !== 'sms' && !status.value.email.live) out.push('Email');
    if (channel.value !== 'email' && !status.value.sms.live) out.push('SMS');
    return out;
});
const needsWorker = computed(() => !!status.value && status.value.queue !== 'sync');

watch(() => props.open, async (open) => {
    if (!open) return;
    // Default to a channel that can reach someone (email first).
    channel.value = channels.value.find(c => c.value === 'email' && c.count)?.value
        ?? channels.value.find(c => c.count)?.value ?? 'email';
    audience.value = audienceCount('not_invited') > 0 ? 'not_invited' : 'pending';
    try { status.value = (await adminApi.messagingStatus()).data; } catch { status.value = null; }
});

async function send() {
    busy.value = true;
    try {
        const { data } = await adminApi.inviteVoters(props.eventId, props.targets
            ? { channel: channel.value, audience: 'selected', voter_ids: props.targets.map(v => v.id) }
            : { channel: channel.value, audience: audience.value });
        const parts = [data.email && `${data.email} email${data.email === 1 ? '' : 's'}`, data.sms && `${data.sms} SMS`].filter(Boolean);
        toast.success(data.queued ? `Sending ${parts.join(' and ')}…` : 'Nobody matched. No messages were sent.');
        emit('sent');
    } catch (e) {
        // Not enough SMS: offer to buy the difference.
        if (planLimitReason(e) === 'sms') {
            const short = (e as { response?: { data?: { sms_short?: number } } }).response?.data?.sms_short ?? 0;
            toast.error(apiErrorMessage(e));
            buyCredits(short);
            return;
        }
        toast.error(apiErrorMessage(e));
    } finally {
        busy.value = false;
    }
}
</script>

<style scoped>
.sms-line { display: flex; gap: .6rem; align-items: center; padding: .6rem .8rem; border-radius: 12px; background: var(--pro-bg); color: var(--ink); font-size: .85rem; box-shadow: inset 0 0 0 1px var(--pro-ring); }
.sms-line > .bi { color: var(--pro-icon); }
.sms-line.short { background: var(--warning-50); box-shadow: none; }
.notice { display: flex; gap: .65rem; padding: .8rem 1rem; border-radius: 14px; background: var(--warning-50); color: var(--warning-text); font-size: .875rem; line-height: 1.5; }
.notice code { color: inherit; background: rgba(146, 64, 14, .08); padding: 0 .3rem; border-radius: 5px; }
.choice.disabled { opacity: .5; cursor: not-allowed; }
.who { display: flex; align-items: center; gap: .75rem; padding: .75rem 1rem; border: 1px solid var(--line); border-radius: 14px; background: var(--surface-2); }
</style>
