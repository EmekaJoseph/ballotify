<template>
    <BaseModal :open="open" title="Remind voters who haven’t voted" size="md"
        :subtitle="`${formatNumber(reachable)} of ${formatNumber(pending)} voters who haven’t voted can be reached by email or SMS.`"
        @close="emit('close')">
        <div class="mb-3">
            <div class="segmented" role="tablist">
                <button type="button" :class="{ active: when === 'now' }" @click="when = 'now'"><i class="bi bi-send"></i>Send now</button>
                <button type="button" :class="{ active: when === 'later' }" @click="when = 'later'"><i class="bi bi-alarm"></i>Schedule</button>
            </div>
        </div>

        <div v-if="scheduled" class="scheduled mb-3">
            <i class="bi bi-alarm"></i>
            <span class="flex-grow-1">Reminder scheduled for <b>{{ formatDate(event.reminder_at!, true) }}</b> by {{ channelLabel(event.reminder_channel) }}.</span>
            <button class="btn btn-ghost btn-sm" :disabled="busy" @click="cancelSchedule">Cancel it</button>
        </div>

        <span class="form-label d-block">Send by</span>
        <div class="d-flex flex-wrap gap-2 mb-3">
            <label v-for="c in channels" :key="c.value" class="choice compact" :class="{ selected: channel === c.value }">
                <input v-model="channel" type="radio" class="visually-hidden" :value="c.value" />
                <i class="bi" :class="c.icon"></i> {{ c.label }}
            </label>
        </div>

        <div v-if="when === 'later'">
            <label for="remind-at" class="form-label">When</label>
            <DateTimePicker id="remind-at" v-model="at" label="Send the reminder" placeholder="Choose a time" default-time="10:00" />
            <div class="d-flex flex-wrap gap-1 mt-2">
                <button v-for="q in quick" :key="q.label" type="button" class="btn btn-ghost btn-sm" @click="at = q.value">{{ q.label }}</button>
            </div>
            <div class="form-text">Only people who still haven’t voted at that time get it.</div>
        </div>
        <p v-else class="small text-muted mb-0">Everyone who hasn’t voted yet gets a short reminder with their code and the voting link.</p>
        <div v-if="sms && !sms.unlimited && smsNeeded > 0" class="sms-line mt-3" :class="{ short: smsNeeded > smsAvailable }">
            <i class="bi bi-chat-dots"></i>
            <span class="flex-grow-1">
                Uses <b>{{ formatNumber(smsNeeded) }}</b> SMS · {{ formatNumber(sms.left) }} left with this event + {{ formatNumber(sms.credits) }} credits.
                <template v-if="smsNeeded > smsAvailable"> <b>{{ formatNumber(smsNeeded - smsAvailable) }} short.</b></template>
            </span>
            <button type="button" class="btn btn-ghost btn-sm flex-shrink-0" @click="buyCredits(smsNeeded - smsAvailable)"><i class="bi bi-cart-plus"></i> Buy</button>
        </div>
        <div v-if="error" class="field-error mt-2">{{ error }}</div>

        <template #footer>
            <button class="btn btn-ghost" @click="emit('close')">Cancel</button>
            <button class="btn btn-primary" :disabled="busy || (when === 'later' && !at) || !reachable" @click="submit">
                <span v-if="busy" class="spinner-border spinner-border-sm"></span>
                {{ when === 'now' ? 'Send reminder' : 'Schedule reminder' }}
            </button>
        </template>
    </BaseModal>
</template>

<script setup lang="ts">
import { adminApi } from '~/api/endpoints/admin';
import type { BallotEvent, InviteChannel } from '~/types';

const props = defineProps<{ open: boolean; event: BallotEvent; pending: number; reachable: number; phonePending?: number }>();
const { openBuySms } = useBilling();
const sms = computed(() => props.event.limits?.sms ?? null);
/** A reminder by SMS goes to everyone pending who has a phone (estimate: the voters still to vote). */
const smsNeeded = computed(() => (channel.value === 'email' || when.value === 'later' ? 0 : (props.phonePending ?? 0)));
const smsAvailable = computed(() => (sms.value ? sms.value.left + sms.value.credits : Infinity));
function buyCredits(short: number) {
    emit('close');
    openBuySms({ eventId: props.event.id, suggest: Math.max(0, short) });
}
const emit = defineEmits<{ close: []; done: [] }>();
const toast = useToast();

const when = ref<'now' | 'later'>('now');
const channel = ref<InviteChannel>('email');
const at = ref('');
const busy = ref(false);
const error = ref('');

const channels = [
    { value: 'email' as const, label: 'Email', icon: 'bi-envelope' },
    { value: 'sms' as const, label: 'SMS', icon: 'bi-chat-dots' },
    { value: 'both' as const, label: 'Both', icon: 'bi-send' },
];
const channelLabel = (c?: string | null) => ({ email: 'email', sms: 'SMS', both: 'email and SMS' } as Record<string, string>)[c ?? 'email'] ?? 'email';
const scheduled = computed(() => !!props.event.reminder_at && !props.event.reminder_sent_at);

const pad = (n: number) => String(n).padStart(2, '0');
const local = (d: Date) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
const quick = computed(() => {
    const out: { label: string; value: string }[] = [];
    const end = props.event.ends_at ? new Date(props.event.ends_at) : null;
    const now = Date.now();
    for (const [label, hours] of [['24 h before close', 24], ['3 h before close', 3], ['1 h before close', 1]] as const) {
        if (end && end.getTime() - hours * 3600e3 > now + 5 * 60e3) out.push({ label, value: local(new Date(end.getTime() - hours * 3600e3)) });
    }
    if (!out.length) out.push({ label: 'In 2 hours', value: local(new Date(now + 2 * 3600e3)) });
    return out;
});

watch(() => props.open, (o) => {
    if (!o) return;
    error.value = '';
    when.value = scheduled.value ? 'later' : 'now';
    channel.value = props.event.reminder_channel ?? 'email';
    at.value = props.event.reminder_at && !props.event.reminder_sent_at ? toLocalInput(props.event.reminder_at) : '';
});

async function submit() {
    busy.value = true;
    error.value = '';
    try {
        if (when.value === 'now') {
            const { data } = await adminApi.remindVoters(props.event.id, channel.value);
            toast.success(`Reminder on its way to ${formatNumber(data.queued)} voter${data.queued === 1 ? '' : 's'}`);
        } else {
            await adminApi.updateEvent(props.event.id, { reminder_at: fromLocalInput(at.value), reminder_channel: channel.value });
            toast.success(`Reminder scheduled for ${formatDate(fromLocalInput(at.value), true)}`);
        }
        emit('done');
    } catch (e) {
        if (planLimitReason(e) === 'sms') {
            const short = (e as { response?: { data?: { sms_short?: number } } }).response?.data?.sms_short ?? 0;
            toast.error(apiErrorMessage(e));
            buyCredits(short);
            return;
        }
        error.value = apiFieldErrors(e).reminder_at ?? apiErrorMessage(e);
    } finally {
        busy.value = false;
    }
}

async function cancelSchedule() {
    busy.value = true;
    try {
        await adminApi.updateEvent(props.event.id, { reminder_at: null });
        toast.success('Scheduled reminder cancelled');
        emit('done');
    } catch (e) {
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
.choice.compact { display: inline-flex; align-items: center; gap: .4rem; padding: .45rem .8rem; width: auto; font-weight: 600; }
.scheduled { display: flex; align-items: center; gap: .6rem; flex-wrap: wrap; padding: .7rem .9rem; border-radius: 12px; background: var(--brand-50); color: var(--brand-900); font-size: .9rem; }
</style>
