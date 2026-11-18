<template>
    <BaseModal :open="open" :title="single ? `Send code to ${single.name || 'this voter'}` : 'Send voting codes'"
        subtitle="Each voter receives the voting link and their own personal code." @close="emit('close')">
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

        <span class="form-label d-block">Send by</span>
        <div class="row g-2 mb-4">
            <div v-for="c in channels" :key="c.value" class="col-4">
                <label class="choice flex-column align-items-start gap-2 h-100" :class="{ selected: channel === c.value, disabled: c.count === 0 }">
                    <input v-model="channel" type="radio" class="visually-hidden" :value="c.value" :disabled="c.count === 0" />
                    <span class="icon-tile" :class="c.tone"><i class="bi" :class="c.icon"></i></span>
                    <span>
                        <span class="d-block fw-bold text-ink">{{ c.label }}</span>
                        <span class="d-block small text-muted">{{ c.count }} reachable</span>
                    </span>
                </label>
            </div>
        </div>

        <template v-if="!single">
            <span class="form-label d-block">Who</span>
            <div class="d-grid gap-2">
                <label class="choice align-items-center" :class="{ selected: audience === 'not_invited' }">
                    <input v-model="audience" type="radio" class="form-check-input mt-0" value="not_invited" />
                    <span class="flex-grow-1"><span class="fw-bold text-ink">Not invited yet</span>
                        <span class="d-block small text-muted">Voters who haven't been sent their code</span></span>
                    <span class="pill pill-brand">{{ targetCount('not_invited') }}</span>
                </label>
                <label class="choice align-items-center" :class="{ selected: audience === 'pending' }">
                    <input v-model="audience" type="radio" class="form-check-input mt-0" value="pending" />
                    <span class="flex-grow-1"><span class="fw-bold text-ink">Everyone who hasn't voted</span>
                        <span class="d-block small text-muted">Includes a reminder for people already invited</span></span>
                    <span class="pill pill-brand">{{ targetCount('pending') }}</span>
                </label>
            </div>
            <p class="small text-muted mt-3 mb-0"><i class="bi bi-shield-check"></i> People who have already voted are never messaged.</p>
        </template>

        <template #footer>
            <button class="btn btn-ghost" @click="emit('close')">Cancel</button>
            <button class="btn btn-primary" :disabled="busy || total === 0" @click="send">
                <span v-if="busy" class="spinner-border spinner-border-sm"></span>
                <i v-else class="bi bi-send"></i>
                {{ total === 0 ? 'Nobody to send to' : `Send to ${total} voter${total === 1 ? '' : 's'}` }}
            </button>
        </template>
    </BaseModal>
</template>

<script setup lang="ts">
import { adminApi } from '~/api/endpoints/admin';
import type { InviteChannel, MessagingStatus, Voter } from '~/types';

const props = defineProps<{ open: boolean; eventId: number; voters: Voter[]; single: Voter | null }>();
const emit = defineEmits<{ close: []; sent: [] }>();

const toast = useToast();
const status = ref<MessagingStatus | null>(null);
const channel = ref<InviteChannel>('email');
const audience = ref<'not_invited' | 'pending'>('not_invited');
const busy = ref(false);

/** Voters this send could reach, before the channel filter. */
const pool = computed(() => (props.single ? [props.single] : props.voters).filter(v => !v.used_at));
const reach = (v: Voter, ch: InviteChannel) => (ch === 'email' ? !!v.email : ch === 'sms' ? !!v.phone : !!(v.email || v.phone));

const channels = computed(() => [
    { value: 'email' as const, label: 'Email', icon: 'bi-envelope', tone: '', count: pool.value.filter(v => reach(v, 'email')).length },
    { value: 'sms' as const, label: 'SMS', icon: 'bi-chat-dots', tone: 'violet', count: pool.value.filter(v => reach(v, 'sms')).length },
    { value: 'both' as const, label: 'Both', icon: 'bi-send', tone: 'green', count: pool.value.filter(v => reach(v, 'both')).length },
]);

const targetCount = (a: 'not_invited' | 'pending') =>
    pool.value.filter(v => reach(v, channel.value) && (a === 'pending' || !v.invited_at)).length;
const total = computed(() => (props.single ? (reach(props.single, channel.value) && !props.single.used_at ? 1 : 0) : targetCount(audience.value)));

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
    // Default to a channel that can reach someone.
    channel.value = channels.value.find(c => c.value === 'email' && c.count)?.value
        ?? channels.value.find(c => c.count)?.value ?? 'email';
    audience.value = targetCount('not_invited') > 0 ? 'not_invited' : 'pending';
    try { status.value = (await adminApi.messagingStatus()).data; } catch { status.value = null; }
});

async function send() {
    busy.value = true;
    try {
        const { data } = await adminApi.inviteVoters(props.eventId, props.single
            ? { channel: channel.value, audience: 'selected', voter_ids: [props.single.id] }
            : { channel: channel.value, audience: audience.value });
        const parts = [data.email && `${data.email} email${data.email === 1 ? '' : 's'}`, data.sms && `${data.sms} SMS`].filter(Boolean);
        toast.success(data.queued ? `Sending ${parts.join(' and ')}…` : 'Nobody matched. No messages were sent.');
        emit('sent');
    } catch (e) {
        toast.error(apiErrorMessage(e));
    } finally {
        busy.value = false;
    }
}
</script>

<style scoped>
.notice { display: flex; gap: .65rem; padding: .8rem 1rem; border-radius: 14px; background: var(--warning-50); color: #92400e; font-size: .875rem; line-height: 1.5; }
.notice code { color: inherit; background: rgba(146, 64, 14, .08); padding: 0 .3rem; border-radius: 5px; }
.choice.disabled { opacity: .5; cursor: not-allowed; }
</style>
