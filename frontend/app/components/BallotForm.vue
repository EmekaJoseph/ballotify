<template>
    <div>
        <!-- Progress -->
        <div class="ballot-progress card-b px-3 py-2 mb-3 d-flex align-items-center gap-3">
            <div class="small fw-bold text-ink text-nowrap">{{ answered }} / {{ categories.length }}</div>
            <div class="bar slim flex-grow-1"><span :style="{ width: progress + '%' }"></span></div>
            <button v-if="nextUnanswered" type="button" class="btn btn-soft btn-sm text-nowrap" @click="jumpTo(nextUnanswered.id)">
                {{ t('ballot.next') }} <i class="bi bi-arrow-down"></i>
            </button>
            <span v-else class="pill pill-brand"><i class="bi bi-check2"></i> {{ t('ballot.allDone') }}</span>
        </div>

        <section v-for="(cat, idx) in categories" :id="`ballot-cat-${cat.id}`" :key="cat.id" class="card-b mb-3 overflow-hidden fade-up ballot-cat"
            :style="{ animationDelay: `${Math.min(idx, 6) * 60}ms` }">
            <header class="d-flex align-items-center justify-content-between gap-2 px-3 px-sm-4 pt-3 pb-2">
                <div class="min-w-0">
                    <div class="small-caps">{{ t('ballot.category', { n: idx + 1 }) }}</div>
                    <h3 class="h5 fw-800 mb-0 text-break">{{ cat.name }}</h3>
                    <div class="small mt-1" :class="isFull(cat) && !single(cat) ? 'text-brand fw-semibold' : 'text-muted'">
                        <template v-if="motion(cat)">{{ t('motion.hint') }}</template>
                        <template v-else-if="ranked(cat)">
                            {{ limitFor(cat) ? t('ballot.rankUpTo', { n: limitFor(cat)!, picked: countFor(cat.id) }) : t('ballot.rankHint') }}<template v-if="!limitFor(cat) && countFor(cat.id)"> · {{ t('ballot.ranked', { n: countFor(cat.id) }) }}</template>
                        </template>
                        <template v-else-if="single(cat)">{{ t('ballot.chooseOne') }}</template>
                        <template v-else-if="limitFor(cat)">{{ t('ballot.pickUpTo', { n: limitFor(cat)!, picked: countFor(cat.id) }) }}</template>
                        <template v-else>{{ t('ballot.pickAny') }}<template v-if="countFor(cat.id)"> · {{ t('ballot.picked', { n: countFor(cat.id) }) }}</template></template>
                    </div>
                </div>
                <i v-if="countFor(cat.id) > 0" class="bi bi-check-circle-fill text-brand fs-5" :aria-label="t('ballot.answered')"></i>
            </header>

            <p v-if="motion(cat) && cat.description" class="motion-text px-3 px-sm-4 mb-2">{{ cat.description }}</p>
            <div class="px-2 px-sm-3 pb-3">
                <div v-if="!(byCategory[cat.id]?.length)" class="text-muted small px-2 py-3">{{ t('ballot.noCandidates') }}</div>
                <div class="row g-2" role="group" :aria-label="cat.name">
                    <div v-for="cand in byCategory[cat.id]" :key="cand.id" class="col-12 col-sm-6">
                        <button type="button" class="option" :class="{ selected: isSelected(cat.id, cand.id) }"
                            :aria-pressed="isSelected(cat.id, cand.id)" :disabled="disabled || (!single(cat) && isFull(cat) && !isSelected(cat.id, cand.id))"
                            :title="!single(cat) && isFull(cat) && !isSelected(cat.id, cand.id) ? t('ballot.limitTitle', { n: limitFor(cat) ?? 0 }) : undefined"
                            @click="toggle(cat.id, cand.id)">
                            <span v-if="cand.motion_choice" class="motion-ic" :class="cand.motion_choice" aria-hidden="true"><i class="bi" :class="MOTION_ICON[cand.motion_choice]"></i></span>
                            <CandidateAvatar v-else :name="cand.name" :src="cand.image_url" />
                            <span class="flex-grow-1 text-start min-w-0">
                                <span class="d-block fw-semibold text-ink text-break">{{ optionLabel(cand) }}</span>
                                <span v-if="cand.description" class="d-block small text-muted text-break opt-desc" :class="{ clamped: !expanded.has(cand.id) }">{{ cand.description }}</span>
                                <span v-if="cand.description && cand.description.length > 110" role="button" tabindex="0" class="more-link"
                                    @click.stop="toggleMore(cand.id)" @keydown.enter.stop.prevent="toggleMore(cand.id)" @keydown.space.stop.prevent="toggleMore(cand.id)">
                                    {{ expanded.has(cand.id) ? t('ballot.less') : t('ballot.more') }}
                                </span>
                            </span>
                            <span v-if="ranked(cat)" class="sel-mark round rank" :aria-label="rankOf(cat.id, cand.id) ? `#${rankOf(cat.id, cand.id)}` : undefined">
                                {{ rankOf(cat.id, cand.id) || '' }}
                            </span>
                            <span v-else class="sel-mark" :class="single(cat) ? 'round' : 'square'">
                                <i class="bi bi-check-lg"></i>
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </section>


        <div class="submit-bar">
            <button type="button" class="btn btn-primary btn-lg w-100" :disabled="disabled || answered === 0"
                @click="reviewOpen = true">
                {{ t('ballot.review') }} <i class="bi bi-arrow-right"></i>
            </button>
            <p v-if="answered < categories.length && answered > 0" class="small text-muted text-center mt-2 mb-0">
                {{ tn('ballot.blank', categories.length - answered) }}
            </p>
        </div>

        <!-- Review -->
        <BaseModal :open="reviewOpen" :title="t('ballot.reviewTitle')" :subtitle="t('ballot.reviewSub')" @close="reviewOpen = false">
            <ul class="list-unstyled mb-0">
                <li v-for="cat in categories" :key="cat.id" class="py-2 border-bottom">
                    <div class="small-caps mb-1">{{ cat.name }}</div>
                    <div v-if="selectedIn(cat.id).length" class="d-flex flex-wrap gap-2">
                        <span v-for="(c, ci) in selectedIn(cat.id)" :key="c.id" class="d-inline-flex align-items-center gap-2 min-w-0">
                            <span v-if="ranked(cat)" class="rank-no">{{ ci + 1 }}</span>
                            <CandidateAvatar v-if="!c.motion_choice" :name="c.name" :src="c.image_url" size="sm" />
                            <span class="min-w-0">
                                <span class="d-block fw-semibold text-break">{{ optionLabel(c) }}</span>
                                <span v-if="c.description" class="d-block small text-muted text-break review-desc">{{ c.description }}</span>
                            </span>
                        </span>
                    </div>
                    <div v-else class="text-muted small fst-italic">{{ t('ballot.noSelection') }}</div>
                </li>
            </ul>
            <template #footer>
                <button type="button" class="btn btn-ghost" :disabled="submitting" @click="reviewOpen = false">{{ t('ballot.change') }}</button>
                <button type="button" class="btn btn-primary" :disabled="submitting" @click="submit">
                    <span v-if="submitting" class="spinner-border spinner-border-sm"></span>
                    {{ submitting ? t('ballot.submitting') : t('ballot.cast') }}
                </button>
            </template>
        </BaseModal>
    </div>
</template>

<script setup lang="ts">
import type { Choice } from '~/types';

interface BallotCategory { id: number; name: string; max_choices?: number | null; method?: string; description?: string | null }
interface BallotCandidate { id: number; category_id: number; name: string; description?: string | null; image_url: string | null; motion_choice?: 'yes' | 'no' | 'abstain' | null }

const props = withDefaults(defineProps<{
    categories: BallotCategory[];
    candidates: BallotCandidate[];
    submitting?: boolean;
    disabled?: boolean;
    error?: string;
}>(), { submitting: false, disabled: false, error: '' });

const emit = defineEmits<{ submit: [choices: Choice[]] }>();

const reviewOpen = ref(false);
/** Candidates whose long description is shown in full ("More"). */
const expanded = ref(new Set<number>());
function toggleMore(id: number) {
    const next = new Set(expanded.value);
    next.has(id) ? next.delete(id) : next.add(id);
    expanded.value = next;
}
const picks = ref<Record<number, number[]>>({});

const byCategory = computed(() => {
    const map: Record<number, BallotCandidate[]> = {};
    for (const c of props.candidates) (map[c.category_id] ??= []).push(c);
    return map;
});

const countFor = (catId: number) => picks.value[catId]?.length ?? 0;
/** Choose-one category (the default). */
const single = (cat: BallotCategory) => !ranked(cat) && (cat.max_choices === 1 || cat.max_choices === undefined);
const motion = (cat: BallotCategory) => cat.method === 'motion';
const MOTION_ICON = { yes: 'bi-check-lg', no: 'bi-x-lg', abstain: 'bi-dash-lg' } as const;
/** Motion options read in the voter's language. */
const optionLabel = (c: BallotCandidate) => (c.motion_choice ? t(`motion.${c.motion_choice}`) : c.name);
/** Ranked choice: picks are kept in order of preference (tap order). */
const ranked = (cat: BallotCategory) => cat.method === 'ranked';
const rankOf = (catId: number, candId: number) => (picks.value[catId]?.indexOf(candId) ?? -1) + 1;
/** Most picks allowed, or null for no limit. */
const limitFor = (cat: BallotCategory) => (cat.max_choices ?? null);
const isFull = (cat: BallotCategory) => {
    const limit = limitFor(cat);
    return limit !== null && countFor(cat.id) >= limit;
};
const isSelected = (catId: number, candId: number) => !!picks.value[catId]?.includes(candId);
const answered = computed(() => props.categories.filter(c => countFor(c.id) > 0).length);
const progress = computed(() => (props.categories.length ? (answered.value / props.categories.length) * 100 : 0));
const nextUnanswered = computed(() => props.categories.find(c => countFor(c.id) === 0 && (byCategory.value[c.id]?.length ?? 0) > 0) ?? null);

function jumpTo(catId: number) {
    document.getElementById(`ballot-cat-${catId}`)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function toggle(catId: number, candId: number) {
    const current = picks.value[catId] ?? [];
    let next: number[];
    const category = props.categories.find(c => c.id === catId);
    if (category && !single(category)) {
        if (current.includes(candId)) {
            next = current.filter(id => id !== candId);
        } else {
            const limit = limitFor(category);
            if (limit !== null && current.length >= limit) return; // at the limit
            next = [...current, candId];
        }
    } else {
        next = current.includes(candId) ? [] : [candId];
    }
    picks.value = { ...picks.value, [catId]: next };
}

function selectedIn(catId: number) {
    const ids = picks.value[catId] ?? [];
    // In pick order, so ranked categories review as 1st, 2nd, 3rd…
    return ids.map(id => (byCategory.value[catId] ?? []).find(c => c.id === id)).filter((c): c is BallotCandidate => !!c);
}

function submit() {
    const choices: Choice[] = [];
    for (const [catId, ids] of Object.entries(picks.value)) {
        ids.forEach((id, i) => choices.push({ category_id: Number(catId), candidate_id: id, rank: i + 1 }));
    }
    emit('submit', choices);
}

/** Lets the parent close the review sheet after a failed submit so the error is visible. */
defineExpose({ closeReview: () => { reviewOpen.value = false; } });
</script>

<style scoped>
.motion-text { white-space: pre-line; color: var(--ink-2, var(--ink)); font-size: .92rem; line-height: 1.55; }
.motion-ic { width: 40px; height: 40px; border-radius: 50%; display: inline-grid; place-items: center; flex-shrink: 0; font-size: 1.2rem; }
.motion-ic.yes { background: #ecfdf3; color: #15803d; }
.motion-ic.no { background: #fef3f2; color: #b42318; }
.motion-ic.abstain { background: var(--surface-2); color: var(--muted); }
.sel-mark.rank { font-size: .85rem; font-weight: 800; font-variant-numeric: tabular-nums; }
.option.selected .sel-mark.rank { color: #fff; }
.rank-no { display: inline-grid; place-items: center; width: 22px; height: 22px; border-radius: 50%; background: var(--brand-600); color: #fff; font-size: .75rem; font-weight: 800; flex-shrink: 0; }
.option {
    width: 100%; height: 100%; display: flex; align-items: center; gap: .85rem; padding: .7rem .85rem;
    border-radius: 14px; border: 1.5px solid var(--line); background: var(--surface);
    transition: border-color .15s var(--ease), background-color .15s var(--ease), transform .1s var(--ease);
}
.option:hover:not(:disabled) { border-color: var(--brand-200); background: var(--surface-2); }
.option:active:not(:disabled) { transform: scale(.99); }
.option:disabled:not(.selected) { opacity: .5; cursor: not-allowed; }
.option.selected { border-color: var(--brand-500); background: var(--brand-50); }
.opt-desc { line-height: 1.4; margin-top: .15rem; font-weight: 400; }
.opt-desc.clamped { display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
.more-link { display: inline-block; margin-top: .15rem; font-size: .78rem; font-weight: 700; color: var(--brand-600); cursor: pointer; }
.more-link:hover { text-decoration: underline; }
.review-desc { line-height: 1.35; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.sel-mark {
    width: 26px; height: 26px; flex-shrink: 0; display: inline-flex; align-items: center; justify-content: center;
    border: 2px solid var(--line-strong); color: transparent; font-size: .95rem; transition: all .15s var(--ease);
}
.sel-mark.round { border-radius: 50%; }
.sel-mark.square { border-radius: 8px; }
.option.selected .sel-mark { background: var(--brand-600); border-color: var(--brand-600); color: #fff; }
.ballot-progress { position: sticky; top: 72px; z-index: 5; }
.ballot-cat { scroll-margin-top: 140px; }
.submit-bar { position: sticky; bottom: 0; padding: 1rem 0 calc(1rem + env(safe-area-inset-bottom)); background: linear-gradient(to top, var(--bg) 70%, transparent); }
</style>
