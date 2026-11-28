<template>
    <!-- One screen for a projector: every category at once, nothing to scroll. Text scales with each tile. -->
    <div class="pb">
        <header class="pb-head">
            <div class="pb-title">
                <img v-if="results.event.brand?.logo_url" :src="results.event.brand.logo_url" alt="" class="pb-logo" />
                <div class="min-w-0">
                    <div class="pb-kicker" :class="results.event.status">
                        <span v-if="results.event.status === 'active'" class="pb-dot"></span>{{ statusLabel }}
                    </div>
                    <h1 class="pb-name">{{ results.event.name }}</h1>
                </div>
            </div>
            <div class="pb-stats">
                <template v-if="paid">
                    <div><b>{{ formatNumber(results.stats.total_votes) }}</b><span>votes</span></div>
                </template>
                <template v-else>
                    <div><b>{{ formatNumber(results.stats.voted) }}</b><span>ballots cast</span></div>
                    <div><b>{{ turnout }}</b><span>turnout</span></div>
                </template>
                <div v-if="results.event.status === 'active' && closesIn.label"><b class="mono">{{ closesIn.label }}</b><span>until voting closes</span></div>
            </div>
            <div class="pb-side"><slot /></div>
        </header>
        <div v-if="!paid" class="pb-turnout" :title="`Turnout ${turnout}`"><span :style="{ width: turnoutPct + '%' }"></span></div>

        <Transition name="pb-page" mode="out-in">
        <main :key="page" class="pb-grid" :style="{ '--cols': cols, '--rows': rows }">
            <section v-for="cat in pageCategories" :key="cat.id" class="pb-tile">
                <div class="pb-cat">
                    <span class="pb-cat-name">{{ cat.name }}</span>
                    <span class="pb-cat-meta" :class="motionClass(cat)">{{ catMeta(cat) }}</span>
                </div>
                <div v-if="!cat.candidates.length" class="pb-empty">No candidates</div>
                <TransitionGroup v-else tag="ol" name="pb-move" class="pb-list" :style="{ '--n': Math.max(shown(cat).length, 3) }">
                    <li v-for="(c, i) in shown(cat)" :key="c.id" class="pb-row" :class="{ lead: isWinner(cat, c.id) }">
                        <span class="pb-rank">{{ i + 1 }}</span>
                        <span class="pb-cname">{{ c.name }}</span>
                        <span class="pb-votes">{{ formatNumber(c.votes) }}<small>{{ categoryShare(c.votes, cat) }}%</small></span>
                        <span class="pb-bar"><span :style="{ width: barWidth(cat, c.votes) + '%' }"></span></span>
                    </li>
                </TransitionGroup>
                <div v-if="cat.candidates.length > shown(cat).length" class="pb-more">+{{ cat.candidates.length - shown(cat).length }} more</div>
            </section>
        </main>
        </Transition>

        <footer class="pb-foot">
            <span v-if="paid"><i class="bi bi-cash-coin"></i> Paid public voting</span>
            <span v-else><i class="bi bi-incognito"></i> Secret ballot</span>
            <span v-if="results.event.status === 'active'"><i class="bi bi-broadcast"></i> {{ live ? 'Updating live' : 'Updating every few seconds' }}<template v-if="updatedAt"> · {{ updatedAt }}</template></span>
            <span v-else-if="results.event.status === 'completed'"><i class="bi bi-check2-circle"></i> Voting closed</span>
            <!-- More than one screen of categories: pages rotate by themselves; arrows or dots to switch. -->
            <span v-if="pageCount > 1" class="pb-pages">
                <button type="button" class="pb-pg-btn" aria-label="Previous page" @click="go(page - 1)"><i class="bi bi-chevron-left"></i></button>
                <button v-for="i in pageCount" :key="i" type="button" class="pb-pg-dot" :class="{ on: i - 1 === page }"
                    :aria-label="`Page ${i} of ${pageCount}`" @click="go(i - 1)"></button>
                <button type="button" class="pb-pg-btn" aria-label="Next page" @click="go(page + 1)"><i class="bi bi-chevron-right"></i></button>
                <span class="ms-1">{{ page + 1 }} / {{ pageCount }}</span>
            </span>
        </footer>
    </div>
</template>

<script setup lang="ts">
import type { EventResults, ResultCategory } from '~/types';

const props = defineProps<{ results: EventResults; live?: boolean; updatedAt?: string }>();

const closesIn = useCountdown(() => props.results.event.ends_at);
const statusLabel = computed(() => ({ active: 'Live results', completed: 'Final results', upcoming: 'Voting hasn’t started' }[props.results.event.status]));
const turnoutPct = computed(() => {
    const { voted, total_voters } = props.results.stats;
    return total_voters ? percent(voted, total_voters) : 0;
});
const turnout = computed(() => (props.results.stats.total_voters ? `${turnoutPct.value}%` : '—'));

/**
 * At most PAGE_SIZE categories on screen at once (still readable from the back of a
 * room). More than that: pages that rotate every ROTATE_MS, all the same grid size.
 */
const PAGE_SIZE = 12;
const ROTATE_MS = 15000;
const pageCount = computed(() => Math.max(1, Math.ceil(props.results.categories.length / PAGE_SIZE)));
/** Spread evenly: 13 categories become 7 + 6, not 12 + 1. */
const perPage = computed(() => Math.ceil(props.results.categories.length / pageCount.value) || 1);
const page = ref(0);
const pageCategories = computed(() => props.results.categories.slice(page.value * perPage.value, (page.value + 1) * perPage.value));
watch(pageCount, (c) => { if (page.value >= c) page.value = 0; });

let rotateTimer: ReturnType<typeof setInterval> | undefined;
function startRotation() {
    clearInterval(rotateTimer);
    if (pageCount.value > 1) rotateTimer = setInterval(() => { page.value = (page.value + 1) % pageCount.value; }, ROTATE_MS);
}
/** Manual switch: go there, and give it a full turn before rotating on. */
function go(p: number) {
    page.value = (p + pageCount.value) % pageCount.value;
    startRotation();
}
function onKey(e: KeyboardEvent) {
    if (pageCount.value < 2) return;
    if (e.key === 'ArrowRight' || e.key === 'PageDown') { e.preventDefault(); go(page.value + 1); }
    else if (e.key === 'ArrowLeft' || e.key === 'PageUp') { e.preventDefault(); go(page.value - 1); }
}
watch(pageCount, startRotation);
onMounted(() => { startRotation(); window.addEventListener('keydown', onKey); });
onBeforeUnmount(() => { clearInterval(rotateTimer); window.removeEventListener('keydown', onKey); });

/** Columns and rows for a 16:9 screen: as square as possible, filling the width first. */
const n = computed(() => Math.max(1, perPage.value));
const cols = computed(() => {
    const c = [1, 1, 2, 3, 2, 3, 3, 4, 4, 3, 4, 4, 4][n.value];
    return c ?? Math.ceil(Math.sqrt(n.value * 1.8));
});
const rows = computed(() => Math.ceil(n.value / cols.value));

/** How many candidates a tile has room for; the rest show as "+N more". */
const perTile = computed(() => (rows.value === 1 ? 10 : rows.value === 2 ? 6 : rows.value === 3 ? 4 : 3));
const shown = (cat: ResultCategory) => cat.candidates.slice(0, perTile.value);

const paid = computed(() => props.results.event.voting_mode === 'paid');

/** Motions: the option that carried (Yes if passed, No if not); otherwise the leader(s). */
const winnerIds = (cat: ResultCategory) => {
    if (isMotion(cat)) {
        const m = cat.motion;
        if (!m?.decided) return [];
        const choice = m.passed ? 'yes' : 'no';
        return cat.candidates.filter(c => c.motion_choice === choice).map(c => c.id);
    }
    return categoryWinners(cat).map(c => c.id);
};
const isWinner = (cat: ResultCategory, id: number) => winnerIds(cat).includes(id);

/** The line beside the category name. */
function catMeta(cat: ResultCategory): string {
    if (isMotion(cat)) {
        const m = cat.motion;
        if (!m?.decided) return 'No Yes or No votes yet';
        return `${m.passed ? 'Passed' : 'Not passed'} · Yes ${percent(m.yes, m.yes + m.no)}%`;
    }
    const total = categoryTotal(cat);
    if (isRanked(cat)) return `${formatNumber(total)} first choice${total === 1 ? '' : 's'}`;
    return `${formatNumber(total)} vote${total === 1 ? '' : 's'}`;
}
const motionClass = (cat: ResultCategory) => (isMotion(cat) && cat.motion?.decided ? (cat.motion.passed ? 'pass' : 'fail') : '');
/** Bars are relative to the leader, so differences are easy to see from the back of the room. */
const barWidth = (cat: ResultCategory, votes: number) => {
    const top = Math.max(0, ...cat.candidates.map(c => c.votes));
    return top ? Math.max(1.5, (votes / top) * 100) : 0;
};
</script>

<style scoped>
.pb {
    height: 100vh; height: 100dvh; display: flex; flex-direction: column; gap: 1.1vh; padding: 2.2vh 2vw;
    color: #fff; overflow: hidden;
    background: #07111e;
}

/* Header */
.pb-head { display: flex; align-items: center; gap: 2vw; flex-shrink: 0; }
.pb-title { display: flex; align-items: center; gap: 1.2vw; flex: 1 1 auto; min-width: 0; }
.pb-logo { height: 6.5vh; max-width: 12vw; object-fit: contain; border-radius: 10px; background: rgba(255, 255, 255, .95); padding: .5vh .6vw; }
.pb-kicker { display: inline-flex; align-items: center; gap: .5vw; font-size: clamp(11px, 1.5vh, 20px); font-weight: 800; letter-spacing: .14em; text-transform: uppercase; color: #5eead4; }
.pb-kicker.completed { color: #fcd34d; }
.pb-kicker.upcoming { color: rgba(255, 255, 255, .6); }
.pb-dot { width: 1.1vh; height: 1.1vh; min-width: 8px; min-height: 8px; border-radius: 50%; background: #22c55e; box-shadow: 0 0 0 0 rgba(34, 197, 94, .6);  }
.pb-name { margin: 0; font-size: clamp(18px, 4vh, 54px); font-weight: 800; letter-spacing: -.02em; line-height: 1.1; color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.pb-stats { display: flex; gap: 2.4vw; flex-shrink: 0; }
.pb-stats div { display: flex; flex-direction: column; align-items: flex-end; }
.pb-stats b { font-size: clamp(18px, 4.2vh, 56px); font-weight: 800; line-height: 1; font-variant-numeric: tabular-nums; }
.pb-stats span { font-size: clamp(10px, 1.3vh, 17px); color: rgba(255, 255, 255, .6); font-weight: 600; margin-top: .4vh; }
.pb-side { flex-shrink: 0; }
.pb-turnout { height: .7vh; min-height: 4px; border-radius: 99px; background: rgba(255, 255, 255, .1); overflow: hidden; flex-shrink: 0; }
.pb-turnout span { display: block; height: 100%; border-radius: 99px; background: #0e7c77; transition: width .8s ease; }

/* Grid of categories */
.pb-grid {
    flex: 1 1 auto; min-height: 0; display: grid; gap: 1.4vh 1.2vw;
    grid-template-columns: repeat(var(--cols), minmax(0, 1fr));
    grid-template-rows: repeat(var(--rows), minmax(0, 1fr));
}
.pb-tile {
    container-type: size; min-height: 0; display: flex; flex-direction: column; overflow: hidden;
    padding: 2.2cqh 2.4cqw; border-radius: 1.4vh; background: rgba(255, 255, 255, .055); border: 1px solid rgba(255, 255, 255, .09);
}
.pb-cat { display: flex; align-items: baseline; justify-content: space-between; gap: 1rem; margin-bottom: 1.5cqh; flex-shrink: 0; }
.pb-cat-name { font-size: clamp(13px, min(9cqh, 5.2cqw), 44px); font-weight: 800; line-height: 1.15; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.pb-cat-meta { font-size: clamp(10px, min(5cqh, 2.8cqw), 22px); color: rgba(255, 255, 255, .55); font-weight: 600; white-space: nowrap; flex-shrink: 0; }
.pb-cat-meta.pass { color: #86efac; font-weight: 800; }
.pb-cat-meta.fail { color: #fca5a5; font-weight: 800; }

.pb-list { list-style: none; margin: 0; padding: 0; flex: 1 1 auto; min-height: 0; display: flex; flex-direction: column; gap: 1.2cqh; position: relative; }
.pb-row {
    flex: 0 1 calc((100% - (var(--n) - 1) * 1.2cqh) / var(--n)); min-height: 0;
    display: grid; grid-template-columns: auto minmax(0, 1fr) auto; grid-template-rows: 1fr auto; column-gap: 1.4cqw; align-items: end;
    --fs: clamp(11px, min(calc(42cqh / var(--n)), 4.6cqw), 40px);
    font-size: var(--fs);
}
.pb-rank { grid-row: 1 / 3; align-self: center; width: 1.7em; height: 1.7em; border-radius: .45em; display: grid; place-items: center; font-weight: 800; font-size: .75em; background: rgba(255, 255, 255, .08); color: rgba(255, 255, 255, .7); }
.pb-cname { font-weight: 700; line-height: 1.15; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: rgba(255, 255, 255, .92); }
.pb-votes { font-weight: 800; font-variant-numeric: tabular-nums; white-space: nowrap; }
.pb-votes small { font-size: .62em; font-weight: 700; color: rgba(255, 255, 255, .55); margin-left: .45em; }
.pb-bar { grid-column: 2 / 4; height: max(5px, .32em); margin-top: .3em; border-radius: 99px; background: rgba(255, 255, 255, .08); overflow: hidden; }
.pb-bar span { display: block; height: 100%; border-radius: 99px; background: rgba(255, 255, 255, .38); transition: width .8s cubic-bezier(.2, .8, .2, 1); }
.pb-row.lead .pb-rank { background: #2dd4bf; color: #04231f; }
.pb-row.lead .pb-cname { color: #fff; }
.pb-row.lead .pb-bar span { background: #0e7c77; }
.pb-more, .pb-empty { font-size: clamp(10px, 4.5cqh, 18px); color: rgba(255, 255, 255, .5); margin-top: .8cqh; flex-shrink: 0; }

/* Page change */
.pb-page-enter-active, .pb-page-leave-active { transition: opacity .35s ease, transform .35s ease; }
.pb-page-enter-from { opacity: 0; transform: translateX(2vw); }
.pb-page-leave-to { opacity: 0; transform: translateX(-2vw); }
.pb-pages { display: inline-flex; align-items: center; gap: .5vw; }
.pb-pg-btn { border: 0; background: rgba(255, 255, 255, .08); color: rgba(255, 255, 255, .75); border-radius: 8px; width: 2.4vh; height: 2.4vh; min-width: 22px; min-height: 22px; display: grid; place-items: center; font-size: .85em; }
.pb-pg-btn:hover { background: rgba(255, 255, 255, .16); color: #fff; }
.pb-pg-dot { border: 0; padding: 0; width: 1vh; height: 1vh; min-width: 8px; min-height: 8px; border-radius: 50%; background: rgba(255, 255, 255, .25); }
.pb-pg-dot.on { background: #2dd4bf; }

/* Live re-ordering: rows glide to their new place. */
.pb-move-move { transition: transform .6s cubic-bezier(.2, .8, .2, 1); }

.pb-foot { display: flex; justify-content: space-between; gap: 1rem; flex-shrink: 0; font-size: clamp(10px, 1.4vh, 17px); color: rgba(255, 255, 255, .5); font-weight: 600; }
.pb-foot .bi { margin-right: .3em; }

/* Phones / narrow windows: stack the tiles and allow scrolling. */
@media (max-width: 767.98px) {
    .pb { height: auto; min-height: 100vh; overflow: visible; padding: 1rem; gap: .75rem; }
    .pb-head { flex-wrap: wrap; }
    .pb-stats { width: 100%; justify-content: space-between; }
    .pb-stats div { align-items: flex-start; }
    .pb-grid { grid-template-columns: 1fr; grid-template-rows: none; }
    .pb-tile { container-type: inline-size; padding: 1rem; }
    .pb-list { gap: .6rem; }
    .pb-row { --fs: 15px; flex: none; }
    .pb-cat-name { font-size: 1.1rem; }
    .pb-cat-meta, .pb-more { font-size: .8rem; }
}
</style>
