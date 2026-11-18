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
            <span class="form-label d-block">Voting style</span>
            <div class="row g-2">
                <div v-for="m in modes" :key="m.value" class="col-sm-6">
                    <label class="choice" :class="{ selected: form.selection_mode === m.value }">
                        <input v-model="form.selection_mode" type="radio" class="visually-hidden" :value="m.value" :disabled="busy" />
                        <span class="icon-tile" :class="m.tone"><i class="bi" :class="m.icon"></i></span>
                        <span>
                            <span class="d-block fw-bold text-ink">{{ m.title }}</span>
                            <span class="d-block small text-muted">{{ m.text }}</span>
                        </span>
                    </label>
                </div>
            </div>
            <div v-if="hasVotes && form.selection_mode !== initialMode" class="small text-warning mt-2">
                <i class="bi bi-exclamation-triangle"></i> Votes have already been cast. Changing the style affects new ballots only.
            </div>
        </div>

        <div class="row g-3 mb-2">
            <div class="col-md-6">
                <label for="ev-start" class="form-label">Voting opens</label>
                <input id="ev-start" v-model="form.starts_at" type="datetime-local" class="form-control" :disabled="busy" />
                <div class="form-text">Leave empty to open immediately.</div>
            </div>
            <div class="col-md-6">
                <label for="ev-end" class="form-label">Voting closes</label>
                <input id="ev-end" v-model="form.ends_at" type="datetime-local" class="form-control"
                    :class="{ 'is-invalid': errors.ends_at }" :min="form.starts_at || undefined" :disabled="busy" />
                <div v-if="errors.ends_at" class="field-error">{{ errors.ends_at }}</div>
                <div v-else class="form-text">Leave empty to keep it open until you close it.</div>
            </div>
        </div>
        <div class="small text-muted mb-4"><i class="bi bi-globe2"></i> Times are in your timezone ({{ tz }}).</div>

        <div class="mb-4" style="max-width: 260px">
            <label for="ev-expected" class="form-label">Expected voters <span class="text-muted fw-normal">(optional)</span></label>
            <input id="ev-expected" v-model.number="form.expected_voters" type="number" min="0" step="1" class="form-control"
                :disabled="busy" />
        </div>

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
import type { BallotEvent, SelectionMode } from '~/types';

const props = withDefaults(defineProps<{
    event?: BallotEvent | null;
    busy?: boolean;
    submitLabel?: string;
    serverErrors?: Record<string, string>;
}>(), { event: null, busy: false, submitLabel: 'Save', serverErrors: () => ({}) });

const emit = defineEmits<{ submit: [payload: EventPayload] }>();

const tz = Intl.DateTimeFormat().resolvedOptions().timeZone;
const initialMode: SelectionMode = props.event?.selection_mode ?? 'single';
const hasVotes = computed(() => (props.event?.voted_count ?? 0) > 0);

const modes = [
    { value: 'single' as const, title: 'One per category', text: 'Voters pick a single candidate in each category.', icon: 'bi-record-circle', tone: '' },
    { value: 'multiple' as const, title: 'Multiple choice', text: 'Voters may pick several candidates per category.', icon: 'bi-ui-checks', tone: 'violet' },
];

const form = reactive({
    name: props.event?.name ?? '',
    description: props.event?.description ?? '',
    selection_mode: initialMode,
    starts_at: toLocalInput(props.event?.starts_at),
    ends_at: toLocalInput(props.event?.ends_at),
    expected_voters: props.event?.expected_voters ?? 0,
});

const localErrors = ref<Record<string, string>>({});
const errors = computed(() => ({ ...props.serverErrors, ...localErrors.value }));

function submit() {
    const errs: Record<string, string> = {};
    if (!form.name) errs.name = 'Give your event a name.';
    if (form.starts_at && form.ends_at && new Date(form.ends_at) <= new Date(form.starts_at)) {
        errs.ends_at = 'Closing time must be after the opening time.';
    }
    localErrors.value = errs;
    if (Object.keys(errs).length) return;

    emit('submit', {
        name: form.name,
        description: form.description.trim() || null,
        selection_mode: form.selection_mode,
        starts_at: fromLocalInput(form.starts_at),
        ends_at: fromLocalInput(form.ends_at),
        expected_voters: Math.max(0, Math.floor(Number(form.expected_voters) || 0)),
    });
}
</script>
