<template>
    <div class="hm" role="img" aria-label="A Ballotify ballot on a phone next to a live results board">
        <div class="hm-stage" aria-hidden="true">
            <!-- Live results board (back) -->
            <div class="hm-board">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <span class="hm-live"><span class="hm-dot"></span> Live results</span>
                    <span class="hm-muted">Annual Elections 2026</span>
                </div>
                <div class="d-flex align-items-center gap-3 mb-3">
                    <svg class="hm-donut" viewBox="0 0 42 42">
                        <circle cx="21" cy="21" r="15.915" fill="none" class="hm-donut-track" stroke-width="5" />
                        <circle v-for="s in segments" :key="s.name" cx="21" cy="21" r="15.915" fill="none"
                            :stroke="s.color" stroke-width="5" stroke-linecap="butt"
                            :stroke-dasharray="`${s.pct} ${100 - s.pct}`" :stroke-dashoffset="25 - s.offset" />
                        <text x="21" y="21" text-anchor="middle" class="hm-donut-num">{{ total.toLocaleString() }}</text>
                        <text x="21" y="26.5" text-anchor="middle" class="hm-donut-lbl">votes</text>
                    </svg>
                    <div class="flex-grow-1 min-w-0">
                        <div class="hm-caps">President</div>
                        <div class="hm-leader text-truncate">{{ leader.name }}</div>
                        <div class="hm-muted">Turnout {{ turnout }}%</div>
                    </div>
                </div>
                <div v-for="c in ranked" :key="c.name" class="hm-row">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="hm-name">{{ c.name }}</span>
                        <span class="hm-pct">{{ pct(c) }}%</span>
                    </div>
                    <div class="hm-bar"><span :style="{ width: pct(c) + '%', background: c.color }"></span></div>
                </div>
            </div>

            <!-- Phone ballot (front) -->
            <div class="hm-phone">
                <div class="hm-screen">
                    <div class="hm-notch"></div>
                    <div class="hm-head">
                        <div class="hm-brand"><span class="hm-logo"><i class="bi bi-check2-square"></i></span> Ballotify</div>
                        <div class="hm-title">Annual Elections 2026</div>
                        <div class="hm-open"><span class="hm-dot sm"></span> Open · closes in 3h 12m</div>
                    </div>
                    <div class="hm-voter">Voting as <b>Tolu A.</b></div>
                    <div class="hm-caps px-1 mb-2">President <span class="hm-muted fw-normal">· pick one</span></div>
                    <div v-for="(c, i) in candidates" :key="c.name" class="hm-cand" :class="{ on: selected === i }">
                        <span class="hm-av" :style="{ background: c.color }">{{ initials(c.name) }}</span>
                        <span class="hm-cname">{{ c.name }}</span>
                        <span class="hm-radio"><i class="bi bi-check-lg"></i></span>
                    </div>
                    <div class="hm-btn" :class="{ ready: selected !== null }">Review my vote</div>
                </div>
            </div>

            <!-- Toast -->
            <div class="hm-toast" :class="{ show: toast }">
                <span class="hm-toast-ic"><i class="bi bi-check2-circle"></i></span>
                <div>
                    <div class="hm-toast-t">Vote recorded</div>
                    <div class="hm-muted">Secret ballot · code used</div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
interface Cand { name: string; color: string; votes: number; weight: number }

const candidates = reactive<Cand[]>([
    { name: 'Ada Obi', color: '#14918a', votes: 642, weight: 5 },
    { name: 'Bola Ade', color: '#7c5cff', votes: 418, weight: 3 },
    { name: 'Chidi Eze', color: '#f59e0b', votes: 224, weight: 2 },
]);
const electorate = 2100;

const selected = ref<number | null>(0);
const toast = ref(false);

const total = computed(() => candidates.reduce((n, c) => n + c.votes, 0));
const turnout = computed(() => Math.round((total.value / electorate) * 100));
const ranked = computed(() => [...candidates].sort((a, b) => b.votes - a.votes));
const leader = computed(() => ranked.value[0]!);
const pct = (c: Cand) => (total.value ? Math.round((c.votes / total.value) * 1000) / 10 : 0);

const segments = computed(() => {
    let offset = 0;
    return candidates.map((c) => {
        const p = total.value ? (c.votes / total.value) * 100 : 0;
        const seg = { name: c.name, color: c.color, pct: Math.max(p - 0.6, 0), offset };
        offset += p;
        return seg;
    });
});

const initials = (n: string) => n.split(' ').map((p) => p[0]).join('').slice(0, 2);

function pick(): number {
    const sum = candidates.reduce((n, c) => n + c.weight, 0);
    let r = Math.random() * sum;
    for (let i = 0; i < candidates.length; i++) {
        r -= candidates[i]!.weight;
        if (r <= 0) return i;
    }
    return 0;
}

// A gentle loop: a voter picks someone, the vote lands, the board updates.
const timers: ReturnType<typeof setTimeout>[] = [];
let loop: ReturnType<typeof setInterval> | null = null;

function cycle() {
    const i = pick();
    selected.value = null;
    timers.push(setTimeout(() => { selected.value = i; }, 450));
    timers.push(setTimeout(() => {
        candidates[i]!.votes += 1 + Math.floor(Math.random() * 3);
        toast.value = true;
    }, 1500));
    timers.push(setTimeout(() => { toast.value = false; }, 3100));
}

onMounted(() => {
    if (window.matchMedia?.('(prefers-reduced-motion: reduce)').matches) return;
    timers.push(setTimeout(cycle, 900));
    loop = setInterval(() => {
        if (document.hidden) return;
        timers.forEach(clearTimeout);
        timers.length = 0;
        cycle();
    }, 3800);
});

onBeforeUnmount(() => {
    if (loop) clearInterval(loop);
    timers.forEach(clearTimeout);
});
</script>

<style scoped>
/* Everything is sized in em, and the em follows the container's width,
   so the whole scene scales as one piece from phones to desktops. */
.hm { container-type: inline-size; max-width: 560px; margin: 0 auto; }
.hm-stage { position: relative; font-size: calc(100cqw / 36); aspect-ratio: 36 / 33; }

.hm-muted { color: var(--muted); font-size: .78em; }
.hm-caps { font-size: .7em; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--muted); }

/* Results board */
.hm-board {
    position: absolute; right: 0; top: 1.2em; width: 22em; padding: 1.4em 1.4em 1.1em 2.6em;
    background: var(--surface); color: var(--ink); border: 1px solid rgba(255, 255, 255, .08);
    border-radius: 1.4em; box-shadow: 0 40px 80px -30px rgba(0, 0, 0, .7);
    animation: hm-float 7s ease-in-out infinite;
}
.hm-live { display: inline-flex; align-items: center; gap: .45em; font-size: .78em; font-weight: 700; color: var(--success); background: var(--success-50); padding: .3em .7em; border-radius: 999px; }
.hm-dot { width: .6em; height: .6em; border-radius: 50%; background: var(--success); box-shadow: 0 0 0 0 rgba(22, 163, 74, .5); animation: hm-pulse 1.8s infinite; }
.hm-dot.sm { width: .5em; height: .5em; }
.hm-donut { width: 6.4em; height: 6.4em; flex-shrink: 0; }
.hm-donut circle { transition: stroke-dasharray .8s var(--ease), stroke-dashoffset .8s var(--ease); }
.hm-donut-track { stroke: var(--track); }
.hm-donut-num { font-size: 7px; font-weight: 800; fill: var(--ink); }
.hm-donut-lbl { font-size: 3.6px; font-weight: 600; fill: var(--muted); }
.hm-leader { font-weight: 800; font-size: 1.1em; margin: .1em 0; }
.hm-leader .bi { color: #f59e0b; font-size: .85em; }
.hm-row + .hm-row { margin-top: .75em; }
.hm-name { font-size: .82em; font-weight: 600; }
.hm-pct { font-size: .82em; font-weight: 800; font-variant-numeric: tabular-nums; }
.hm-bar { height: .45em; border-radius: 999px; background: var(--track); overflow: hidden; }
.hm-bar span { display: block; height: 100%; border-radius: inherit; transition: width .8s var(--ease); }

/* Phone */
.hm-phone {
    position: absolute; left: .4em; top: 4.2em; width: 15em; padding: .5em;
    background: #070d19; border-radius: 2.3em; border: 1px solid rgba(255, 255, 255, .14);
    box-shadow: 0 50px 90px -30px rgba(0, 0, 0, .85), 0 0 0 .25em rgba(255, 255, 255, .03);
    animation: hm-float 7s ease-in-out infinite; animation-delay: -3.5s;
}
.hm-screen {
    position: relative; background: var(--bg); color: var(--ink); border-radius: 1.85em;
    padding: 2em .85em 1em; overflow: hidden; min-height: 27em;
}
.hm-notch { position: absolute; top: .55em; left: 50%; transform: translateX(-50%); width: 4.2em; height: 1em; border-radius: 999px; background: #070d19; }
.hm-head {
    background: var(--brand-600); color: #fff;
    border-radius: 1em; padding: .8em .9em; margin-bottom: .7em;
}
.hm-brand { display: flex; align-items: center; gap: .35em; font-size: .68em; font-weight: 700; opacity: .9; }
.hm-logo { display: inline-flex; width: 1.5em; height: 1.5em; align-items: center; justify-content: center; border-radius: .4em; background: rgba(255, 255, 255, .2); }
.hm-title { font-weight: 800; font-size: .95em; margin: .35em 0 .25em; line-height: 1.2; }
.hm-open { display: flex; align-items: center; gap: .4em; font-size: .64em; opacity: .9; }
.hm-open .hm-dot { background: #86efac; }
.hm-voter {
    font-size: .68em; color: var(--ink-2); background: var(--surface); border: 1px solid var(--line);
    border-radius: 999px; padding: .4em .8em; margin-bottom: .9em; display: inline-flex; gap: .4em; align-items: center;
}
.hm-voter .bi { color: var(--brand-600); }
.hm-cand {
    display: flex; align-items: center; gap: .6em; padding: .55em .6em; margin-bottom: .45em;
    background: var(--surface); border: 1.5px solid var(--line); border-radius: .85em;
    transition: border-color .3s, background-color .3s, transform .3s var(--ease);
}
.hm-cand.on { border-color: var(--brand-500); background: var(--brand-50); transform: scale(1.02); }
.hm-av { width: 2.1em; height: 2.1em; border-radius: 50%; color: #fff; font-size: .72em; font-weight: 800; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }
.hm-cname { flex-grow: 1; font-size: .8em; font-weight: 700; }
.hm-radio {
    width: 1.25em; height: 1.25em; border-radius: 50%; border: 1.5px solid var(--line-strong);
    display: inline-flex; align-items: center; justify-content: center; font-size: .8em; color: transparent;
    transition: all .3s;
}
.hm-cand.on .hm-radio { background: var(--brand-600); border-color: var(--brand-600); color: #fff; }
.hm-btn {
    margin-top: .8em; text-align: center; font-size: .78em; font-weight: 700; padding: .75em;
    border-radius: .9em; background: var(--line); color: var(--muted); transition: all .3s;
}
.hm-btn.ready { background: var(--brand-600); color: #fff; box-shadow: 0 10px 20px -10px var(--brand-600); }

/* Toast */
.hm-toast {
    position: absolute; right: 1.2em; bottom: .6em; display: flex; gap: .7em; align-items: center;
    background: var(--surface); color: var(--ink); border: 1px solid var(--line); border-radius: 1em; padding: .75em 1em;
    box-shadow: var(--shadow-lg); opacity: 0; transform: translateY(.8em) scale(.96);
    transition: opacity .35s var(--ease), transform .35s var(--ease);
}
.hm-toast.show { opacity: 1; transform: none; }
.hm-toast-ic { width: 2.2em; height: 2.2em; border-radius: .7em; display: inline-flex; align-items: center; justify-content: center; background: var(--success-50); color: var(--success); font-size: 1.05em; }
.hm-toast-t { font-weight: 800; font-size: .85em; }

@keyframes hm-float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-.5em); } }
@keyframes hm-pulse { 0% { box-shadow: 0 0 0 0 rgba(22, 163, 74, .45); } 70% { box-shadow: 0 0 0 .5em rgba(22, 163, 74, 0); } 100% { box-shadow: 0 0 0 0 rgba(22, 163, 74, 0); } }

@media (prefers-reduced-motion: reduce) {
    .hm-board, .hm-phone, .hm-dot { animation: none; }
    .hm-toast { opacity: 1; transform: none; }
}
</style>
