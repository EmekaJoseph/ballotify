<template>
    <div>
        <!-- Progress -->
        <div class="ballot-progress card-b px-3 py-2 mb-3 d-flex align-items-center gap-3">
            <div class="small fw-bold text-ink text-nowrap">{{ answered }} / {{ categories.length }}</div>
            <div class="bar slim flex-grow-1"><span :style="{ width: progress + '%' }"></span></div>
            <span class="pill" :class="mode === 'multiple' ? 'pill-violet' : 'pill-brand'">
                {{ mode === 'multiple' ? 'Pick any' : 'Pick one' }}
            </span>
        </div>

        <section v-for="(cat, idx) in categories" :key="cat.id" class="card-b mb-3 overflow-hidden fade-up"
            :style="{ animationDelay: `${Math.min(idx, 6) * 60}ms` }">
            <header class="d-flex align-items-center justify-content-between gap-2 px-3 px-sm-4 pt-3 pb-2">
                <div class="min-w-0">
                    <div class="small-caps">Category {{ idx + 1 }}</div>
                    <h3 class="h5 fw-800 mb-0 text-break">{{ cat.name }}</h3>
                </div>
                <i v-if="countFor(cat.id) > 0" class="bi bi-check-circle-fill text-brand fs-5" aria-label="Answered"></i>
            </header>

            <div class="px-2 px-sm-3 pb-3">
                <div v-if="!(byCategory[cat.id]?.length)" class="text-muted small px-2 py-3">No candidates in this category.</div>
                <div class="row g-2" role="group" :aria-label="cat.name">
                    <div v-for="cand in byCategory[cat.id]" :key="cand.id" class="col-12 col-sm-6">
                        <button type="button" class="option" :class="{ selected: isSelected(cat.id, cand.id) }"
                            :aria-pressed="isSelected(cat.id, cand.id)" :disabled="disabled"
                            @click="toggle(cat.id, cand.id)">
                            <CandidateAvatar :name="cand.name" :src="cand.image_url" />
                            <span class="flex-grow-1 text-start fw-semibold text-ink min-w-0 text-break">{{ cand.name }}</span>
                            <span class="sel-mark" :class="mode === 'multiple' ? 'square' : 'round'">
                                <i class="bi bi-check-lg"></i>
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <div v-if="error" class="alert alert-danger d-flex gap-2 align-items-start rounded-4 border-0" role="alert">
            <i class="bi bi-exclamation-circle-fill mt-1"></i><span>{{ error }}</span>
        </div>

        <div class="submit-bar">
            <button type="button" class="btn btn-primary btn-lg w-100" :disabled="disabled || answered === 0"
                @click="reviewOpen = true">
                Review &amp; submit <i class="bi bi-arrow-right"></i>
            </button>
            <p v-if="answered < categories.length && answered > 0" class="small text-muted text-center mt-2 mb-0">
                {{ categories.length - answered }} categor{{ categories.length - answered === 1 ? 'y' : 'ies' }} left blank. That's allowed.
            </p>
        </div>

        <!-- Review -->
        <BaseModal :open="reviewOpen" title="Review your ballot" subtitle="Votes are final once submitted." @close="reviewOpen = false">
            <ul class="list-unstyled mb-0">
                <li v-for="cat in categories" :key="cat.id" class="py-2 border-bottom">
                    <div class="small-caps mb-1">{{ cat.name }}</div>
                    <div v-if="selectedIn(cat.id).length" class="d-flex flex-wrap gap-2">
                        <span v-for="c in selectedIn(cat.id)" :key="c.id" class="d-inline-flex align-items-center gap-2 fw-semibold">
                            <CandidateAvatar :name="c.name" :src="c.image_url" size="sm" /> {{ c.name }}
                        </span>
                    </div>
                    <div v-else class="text-muted small fst-italic">No selection</div>
                </li>
            </ul>
            <template #footer>
                <button type="button" class="btn btn-ghost" :disabled="submitting" @click="reviewOpen = false">Change</button>
                <button type="button" class="btn btn-primary" :disabled="submitting" @click="submit">
                    <span v-if="submitting" class="spinner-border spinner-border-sm"></span>
                    {{ submitting ? 'Submitting…' : 'Cast my vote' }}
                </button>
            </template>
        </BaseModal>
    </div>
</template>

<script setup lang="ts">
import type { Choice, SelectionMode } from '~/types';

interface BallotCategory { id: number; name: string }
interface BallotCandidate { id: number; category_id: number; name: string; image_url: string | null }

const props = withDefaults(defineProps<{
    categories: BallotCategory[];
    candidates: BallotCandidate[];
    mode: SelectionMode;
    submitting?: boolean;
    disabled?: boolean;
    error?: string;
}>(), { submitting: false, disabled: false, error: '' });

const emit = defineEmits<{ submit: [choices: Choice[]] }>();

const reviewOpen = ref(false);
const picks = ref<Record<number, number[]>>({});

const byCategory = computed(() => {
    const map: Record<number, BallotCandidate[]> = {};
    for (const c of props.candidates) (map[c.category_id] ??= []).push(c);
    return map;
});

const countFor = (catId: number) => picks.value[catId]?.length ?? 0;
const isSelected = (catId: number, candId: number) => !!picks.value[catId]?.includes(candId);
const answered = computed(() => props.categories.filter(c => countFor(c.id) > 0).length);
const progress = computed(() => (props.categories.length ? (answered.value / props.categories.length) * 100 : 0));

function toggle(catId: number, candId: number) {
    const current = picks.value[catId] ?? [];
    let next: number[];
    if (props.mode === 'multiple') {
        next = current.includes(candId) ? current.filter(id => id !== candId) : [...current, candId];
    } else {
        next = current.includes(candId) ? [] : [candId];
    }
    picks.value = { ...picks.value, [catId]: next };
}

function selectedIn(catId: number) {
    const ids = picks.value[catId] ?? [];
    return (byCategory.value[catId] ?? []).filter(c => ids.includes(c.id));
}

function submit() {
    const choices: Choice[] = [];
    for (const [catId, ids] of Object.entries(picks.value)) {
        for (const id of ids) choices.push({ category_id: Number(catId), candidate_id: id });
    }
    emit('submit', choices);
}

/** Lets the parent close the review sheet after a failed submit so the error is visible. */
defineExpose({ closeReview: () => { reviewOpen.value = false; } });
</script>

<style scoped>
.option {
    width: 100%; display: flex; align-items: center; gap: .85rem; padding: .7rem .85rem;
    border-radius: 14px; border: 1.5px solid var(--line); background: var(--surface);
    transition: border-color .15s var(--ease), background-color .15s var(--ease), transform .1s var(--ease);
}
.option:hover:not(:disabled) { border-color: var(--brand-200); background: var(--surface-2); }
.option:active:not(:disabled) { transform: scale(.99); }
.option.selected { border-color: var(--brand-500); background: var(--brand-50); }
.sel-mark {
    width: 26px; height: 26px; flex-shrink: 0; display: inline-flex; align-items: center; justify-content: center;
    border: 2px solid var(--line-strong); color: transparent; font-size: .95rem; transition: all .15s var(--ease);
}
.sel-mark.round { border-radius: 50%; }
.sel-mark.square { border-radius: 8px; }
.option.selected .sel-mark { background: var(--brand-600); border-color: var(--brand-600); color: #fff; }
.ballot-progress { position: sticky; top: 72px; z-index: 5; }
.submit-bar { position: sticky; bottom: 0; padding: 1rem 0 calc(1rem + env(safe-area-inset-bottom)); background: linear-gradient(to top, var(--bg) 70%, transparent); }
</style>
