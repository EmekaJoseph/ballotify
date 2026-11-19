<template>
    <form novalidate @submit.prevent="submit">
        <div class="mb-4">
            <label for="ev-name" class="form-label">Event name</label>
            <input id="ev-name" v-model.trim="form.name" type="text" class="form-control form-control-lg"
                :class="{ 'is-invalid': errors.name }" placeholder="e.g. Student Union Elections 2026" maxlength="255"
                :disabled="busy" />
            <div v-if="errors.name" class="field-error">{{ errors.name }}</div>
        </div>

        <div class="mb-4">
            <label for="ev-desc" class="form-label">Description <span class="text-muted fw-normal">(optional)</span></label>
            <textarea id="ev-desc" v-model="form.description" class="form-control" rows="3" maxlength="5000"
                placeholder="What is this vote about? Voters see this on the ballot." :disabled="busy"></textarea>
        </div>

        <div class="mb-4">
            <span class="form-label d-block">How people vote</span>
            <div class="row g-2">
                <div v-for="m in modes" :key="m.value" class="col-sm-6">
                    <label class="choice" :class="{ selected: form.voting_mode === m.value, locked: modeLocked }">
                        <input v-model="form.voting_mode" type="radio" class="visually-hidden" :value="m.value" :disabled="busy || modeLocked" />
                        <span class="icon-tile" :class="m.tone"><i class="bi" :class="m.icon"></i></span>
                        <span>
                            <span class="d-block fw-bold text-ink">{{ m.title }}</span>
                            <span class="d-block small text-muted">{{ m.text }}</span>
                        </span>
                    </label>
                </div>
            </div>
            <div v-if="modeLocked" class="small text-muted mt-2">
                <i class="bi bi-lock"></i> Locked: the voting mode can’t change once voting has started.
            </div>
            <div v-if="form.voting_mode === 'paid'" class="paid-box mt-3">
                <div class="row g-3 align-items-end">
                    <div class="col-sm-5">
                        <label for="ev-price" class="form-label">Price per vote</label>
                        <div class="input-group">
                            <span class="input-group-text">₦</span>
                            <input id="ev-price" v-model.number="form.vote_price_naira" type="number" min="10" max="100000" step="10" class="form-control"
                                :class="{ 'is-invalid': errors.vote_price_naira }" :disabled="busy" />
                        </div>
                    </div>
                    <div class="col-sm-7 small text-muted">
                        Anyone with the link can vote as many times as they pay for. Payments go through Paystack;
                        <template v-if="feePercent !== null">Ballotify keeps {{ feePercent }}% and</template> the rest is paid out to you.
                    </div>
                </div>
                <div v-if="errors.vote_price_naira" class="field-error">{{ errors.vote_price_naira }}</div>
            </div>
        </div>

        <div class="mb-4">
            <span class="form-label d-block">Who can see the results</span>
            <div class="row g-2">
                <div v-for="v in visibilityOptions" :key="v.value" class="col-12">
                    <label class="choice" :class="{ selected: form.results_visibility === v.value }">
                        <input v-model="form.results_visibility" type="radio" class="visually-hidden" :value="v.value" :disabled="busy" />
                        <span class="icon-tile" :class="v.tone"><i class="bi" :class="v.icon"></i></span>
                        <span>
                            <span class="d-block fw-bold text-ink">{{ v.title }}</span>
                            <span class="d-block small text-muted">{{ v.text }}</span>
                        </span>
                    </label>
                </div>
            </div>
            <div class="form-text mt-2">You can always see the full results in your dashboard.</div>
        </div>

        <div class="row g-3 mb-2">
            <div class="col-md-6">
                <label for="ev-start" class="form-label">Voting opens</label>
                <DateTimePicker id="ev-start" v-model="form.starts_at" label="Voting opens" placeholder="Opens immediately"
                    default-time="09:00" :disabled="busy" />
                <div class="form-text">Leave empty to open immediately.</div>
            </div>
            <div class="col-md-6">
                <label for="ev-end" class="form-label">Voting closes</label>
                <DateTimePicker id="ev-end" v-model="form.ends_at" label="Voting closes" placeholder="Open until you close it"
                    default-time="18:00" :min="form.starts_at" :invalid="!!errors.ends_at" :disabled="busy" />
                <div v-if="errors.ends_at" class="field-error">{{ errors.ends_at }}</div>
                <div v-else class="form-text">Leave empty to keep it open until you close it.</div>
            </div>
        </div>
        <div class="small text-muted mb-4"><i class="bi bi-globe2"></i> Times are in your timezone ({{ tz }}).</div>

        <div class="d-flex flex-wrap gap-2 justify-content-end">
            <slot name="actions" />
            <button type="submit" class="btn btn-primary" :disabled="busy">
                <span v-if="busy" class="spinner-border spinner-border-sm"></span>
                {{ submitLabel }}
            </button>
        </div>
    </form>
</template>

<script setup lang="ts">
import type { EventPayload } from '~/api/endpoints/admin';
import type { BallotEvent, ResultsVisibility, VotingMode } from '~/types';

const props = withDefaults(defineProps<{
    event?: BallotEvent | null;
    busy?: boolean;
    submitLabel?: string;
    serverErrors?: Record<string, string>;
    /** Starting values for a new event (e.g. from a template). */
    defaults?: { voting_mode?: VotingMode; vote_price_naira?: number | null; results_visibility?: ResultsVisibility } | null;
}>(), { event: null, busy: false, submitLabel: 'Save', serverErrors: () => ({}), defaults: null });

const emit = defineEmits<{ submit: [payload: EventPayload] }>();

const tz = Intl.DateTimeFormat().resolvedOptions().timeZone;
const initialMode: VotingMode = props.event?.voting_mode ?? props.defaults?.voting_mode ?? 'codes';
const { plans, loadPlans } = useBilling();
onMounted(() => loadPlans());
const feePercent = computed(() => plans.value?.paid_votes?.fee_percent ?? null);
const modeLocked = computed(() => !!props.event?.ballot_locked);

const modes = [
    { value: 'codes' as const, title: 'Secret ballot', text: 'One-time codes. One person, one vote. For elections.', icon: 'bi-shield-lock', tone: '' },
    { value: 'paid' as const, title: 'Paid public voting', text: 'Fans pay per vote. For award nights and pageants.', icon: 'bi-trophy', tone: 'violet' },
];

const visibilityOptions: { value: ResultsVisibility; title: string; text: string; icon: string; tone: string }[] = [
    { value: 'after_close', title: 'After voting closes', text: 'Recommended. Running totals can’t sway later voters.', icon: 'bi-hourglass-bottom', tone: '' },
    { value: 'live', title: 'Live', text: 'Anyone with the results link sees totals as votes come in.', icon: 'bi-broadcast', tone: 'green' },
    { value: 'private', title: 'Only me', text: 'Never published. Announce the results yourself.', icon: 'bi-eye-slash', tone: 'violet' },
];

const form = reactive({
    name: props.event?.name ?? '',
    description: props.event?.description ?? '',
    voting_mode: initialMode,
    vote_price_naira: props.event?.vote_price_naira ?? props.defaults?.vote_price_naira ?? 50,
    starts_at: toLocalInput(props.event?.starts_at),
    ends_at: toLocalInput(props.event?.ends_at),
    results_visibility: (props.event?.results_visibility ?? props.defaults?.results_visibility ?? 'after_close') as ResultsVisibility,
});

const localErrors = ref<Record<string, string>>({});
const errors = computed(() => ({ ...props.serverErrors, ...localErrors.value }));

function submit() {
    const errs: Record<string, string> = {};
    if (!form.name) errs.name = 'Give your event a name.';
    if (form.voting_mode === 'paid' && !(form.vote_price_naira >= 10)) errs.vote_price_naira = 'Set a price of at least ₦10 per vote.';
    if (form.starts_at && form.ends_at && new Date(form.ends_at) <= new Date(form.starts_at)) {
        errs.ends_at = 'Closing time must be after the opening time.';
    }
    localErrors.value = errs;
    if (Object.keys(errs).length) return;

    emit('submit', {
        name: form.name,
        description: form.description.trim() || null,
        // Not sent once locked, so saving other settings never trips the lock.
        ...(modeLocked.value ? {} : { voting_mode: form.voting_mode }),
        ...(form.voting_mode === 'paid' ? { vote_price_naira: form.vote_price_naira } : {}),
        starts_at: fromLocalInput(form.starts_at),
        ends_at: fromLocalInput(form.ends_at),
        results_visibility: form.results_visibility,
    });
}
</script>

<style scoped>
.choice.locked { cursor: not-allowed; opacity: .65; }
.paid-box { padding: 1rem; border-radius: 14px; background: var(--surface-2); border: 1px solid var(--line); }
</style>
