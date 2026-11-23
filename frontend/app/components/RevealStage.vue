<template>
    <div class="reveal-stage">
        <div class="d-flex justify-content-between align-items-center mb-3 text-white-50 small">
            <span>Category {{ index + 1 }} of {{ categories.length }}</span>
            <span><kbd>Space</kbd> {{ revealed ? 'next' : 'reveal' }} · <kbd>←</kbd> back</span>
        </div>

        <Transition name="cat" mode="out-in">
            <div v-if="cat" :key="cat.id" class="cat">
                <div class="eyebrow-r">{{ motionCat ? (revealed ? (cat.motion?.passed ? 'The motion is passed' : 'The motion is not passed') : 'The motion') : revealed ? (tie ? 'It’s a tie!' : 'And the winner is…') : 'The nominees are' }}</div>
                <h2 class="cat-name">{{ cat.name }}</h2>

                <ul class="list-unstyled nominees">
                    <li v-for="(c, i) in ordered" :key="c.id" class="nominee" :class="{ winner: revealed && isWinner(c), dim: revealed && !isWinner(c) }"
                        :style="{ transitionDelay: revealed ? `${i * 90}ms` : '0ms' }">
                        <CandidateAvatar :name="c.name" :src="c.image_url" size="lg" />
                        <div class="flex-grow-1 min-w-0">
                            <div class="d-flex justify-content-between align-items-baseline gap-2">
                                <span class="name text-truncate">
                                    <i v-if="revealed && isWinner(c)" class="bi bi-trophy-fill trophy"></i>{{ c.motion_choice ? t(`motion.${c.motion_choice}`) : c.name }}
                                </span>
                                <span class="votes" :class="{ show: revealed }">{{ outIn(c) ? `Eliminated in round ${outIn(c)}` : `${formatNumber(votesOf(c))} · ${pct(votesOf(c))}%` }}</span>
                            </div>
                            <div class="track"><span :style="{ width: revealed ? pct(votesOf(c)) + '%' : '0%' }"></span></div>
                        </div>
                    </li>
                </ul>
            </div>
        </Transition>

        <div class="d-flex justify-content-center gap-2 mt-4">
            <button class="btn btn-outline-light-on-dark" :disabled="index === 0 && !revealed" @click="back"><i class="bi bi-arrow-left"></i> Back</button>
            <button class="btn btn-light-on-dark btn-lg px-4" @click="forward">
                <template v-if="!revealed"><i class="bi bi-stars"></i> Reveal</template>
                <template v-else-if="index < categories.length - 1">Next category <i class="bi bi-arrow-right"></i></template>
                <template v-else><i class="bi bi-flag"></i> Finish</template>
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import type { ResultCategory } from '~/types';

const props = defineProps<{ categories: ResultCategory[] }>();
const emit = defineEmits<{ finish: [] }>();

const index = ref(0);
const revealed = ref(false);
const cat = computed(() => props.categories[index.value] ?? null);

/** Nominees in a fixed, neutral order (by name) until the reveal. */
/** Ranked choice shows the deciding round; other categories their votes. */
const motionCat = computed(() => !!cat.value && isMotion(cat.value));
const votesOf = (c: { id: number; votes: number }) => (cat.value && isRanked(cat.value) ? finalVotes(cat.value, c.id) : c.votes);
/** Ranked choice: the round a candidate was eliminated in (0 = still in at the end). */
const outIn = (c: { id: number }) => {
    if (!cat.value || !isRanked(cat.value)) return 0;
    const i = (cat.value.rounds ?? []).findIndex(r => r.eliminated.includes(c.id));
    return i + 1;
};
const ordered = computed(() => {
    const list = [...(cat.value?.candidates ?? [])];
    // Revealed: the results order (by method); before: a neutral order by name.
    return revealed.value ? list : list.sort((a, b) => a.name.localeCompare(b.name));
});
const total = computed(() => {
    if (!cat.value) return 0;
    return isRanked(cat.value) ? (finalRound(cat.value)?.continuing ?? 0) : categoryBase(cat.value);
});
const winnerIds = computed(() => (cat.value ? categoryWinners(cat.value).map(c => c.id) : []));
const tie = computed(() => winnerIds.value.length > 1);
const isWinner = (c: { id: number; motion_choice?: string | null }) => (motionCat.value
    ? !!cat.value?.motion?.decided && c.motion_choice === (cat.value.motion.passed ? 'yes' : 'no')
    : winnerIds.value.includes(c.id));
const pct = (v: number) => (total.value ? Math.round((v / total.value) * 1000) / 10 : 0);

function forward() {
    if (!revealed.value) { revealed.value = true; return; }
    if (index.value < props.categories.length - 1) { index.value++; revealed.value = false; return; }
    emit('finish');
}
function back() {
    if (revealed.value) { revealed.value = false; return; }
    if (index.value > 0) { index.value--; revealed.value = true; }
}
function onKey(e: KeyboardEvent) {
    if (e.key === ' ' || e.key === 'ArrowRight' || e.key === 'Enter') { e.preventDefault(); forward(); }
    else if (e.key === 'ArrowLeft') { e.preventDefault(); back(); }
}
onMounted(() => window.addEventListener('keydown', onKey));
onBeforeUnmount(() => window.removeEventListener('keydown', onKey));
</script>

<style scoped>
.reveal-stage { color: #fff; max-width: 980px; margin: 0 auto; padding: 1rem 0 2rem; }
kbd { background: rgba(255, 255, 255, .12); border-radius: 6px; padding: .05rem .35rem; font-size: .75rem; }
.eyebrow-r { text-transform: uppercase; letter-spacing: .14em; font-weight: 800; font-size: .85rem; color: #5eead4; text-align: center; }
.cat-name { color: #fff; text-align: center; font-weight: 800; font-size: clamp(2rem, 5vw, 3.4rem); letter-spacing: -.02em; margin: .4rem 0 2rem; }
.nominees { display: grid; gap: .9rem; }
.nominee {
    display: flex; align-items: center; gap: 1rem; padding: 1rem 1.25rem; border-radius: 18px;
    background: rgba(255, 255, 255, .06); border: 1px solid rgba(255, 255, 255, .1);
    transition: background-color .5s var(--ease), opacity .5s var(--ease), transform .5s var(--ease), border-color .5s var(--ease);
}
.nominee.dim { opacity: .55; }
.nominee.winner { background: rgba(94, 234, 212, .14); border-color: #5eead4; transform: scale(1.02); }
.name { font-size: clamp(1.1rem, 2.4vw, 1.5rem); font-weight: 800; }
.trophy { color: #fbbf24; margin-right: .5rem; animation: pop .6s var(--ease); display: inline-block; }
@keyframes pop { 0% { transform: scale(0) rotate(-20deg); } 70% { transform: scale(1.3); } 100% { transform: scale(1); } }
.votes { font-weight: 700; color: rgba(255, 255, 255, .8); opacity: 0; transition: opacity .4s .8s; white-space: nowrap; }
.votes.show { opacity: 1; }
.track { height: 10px; border-radius: 6px; background: rgba(255, 255, 255, .08); margin-top: .5rem; overflow: hidden; }
.track span { display: block; height: 100%; border-radius: 6px; background: linear-gradient(90deg, #14918a, #5eead4); transition: width 1.2s cubic-bezier(.2, .8, .2, 1); }
.winner .track span { background: linear-gradient(90deg, #f59e0b, #fbbf24); }
.cat-enter-active, .cat-leave-active { transition: opacity .35s, transform .35s; }
.cat-enter-from { opacity: 0; transform: translateY(16px); }
.cat-leave-to { opacity: 0; transform: translateY(-16px); }
</style>
