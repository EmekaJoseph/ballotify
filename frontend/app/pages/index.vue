<template>
    <div>
        <!-- Hero -->
        <section class="hero">
            <div class="hero-glow"></div>
            <div class="container position-relative">
                <div class="row align-items-center g-5">
                    <div class="col-lg-6 text-center text-lg-start fade-up">
                        <span class="eyebrow dark mb-4"><i class="bi bi-stars"></i> Elections · Awards · Polls</span>
                        <h1 class="display-title text-white mb-4">
                            Run a fair vote<br />
                            <span class="text-gradient-light">in minutes.</span>
                        </h1>
                        <p class="hero-lead mx-auto mx-lg-0 mb-5">
                            Build your ballot, hand out one-time voter codes and watch results update live.
                            Voters can use any phone or laptop. No app to install.
                        </p>
                        <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center justify-content-lg-start">
                            <NuxtLink :to="ctaLink" class="btn btn-light-on-dark btn-lg">
                                {{ auth.isAuthenticated ? 'Go to dashboard' : 'Create your first event' }}
                                <i class="bi bi-arrow-right"></i>
                            </NuxtLink>
                            <NuxtLink to="/demo/vote" class="btn btn-outline-light-on-dark btn-lg">
                                <i class="bi bi-play-circle"></i> Try the demo ballot
                            </NuxtLink>
                        </div>
                        <div class="d-flex flex-wrap gap-4 justify-content-center justify-content-lg-start mt-5 hero-points">
                            <span><i class="bi bi-shield-check"></i> One vote per code</span>
                            <span><i class="bi bi-phone"></i> Mobile-first ballot</span>
                            <span><i class="bi bi-lightning-charge"></i> Live results</span>
                        </div>
                    </div>
                    <div class="col-lg-6 fade-up" style="animation-delay: .12s">
                        <div class="hero-art">
                            <img src="/hero_illustration.png" alt="Voters casting ballots into a secure Ballotify ballot box" width="640" height="640" />
                            <div class="float-card fc-1">
                                <span class="icon-tile green"><i class="bi bi-check2-circle"></i></span>
                                <div>
                                    <div class="fw-bold small text-ink">Vote recorded</div>
                                    <div class="small text-muted">Code K7M2·QX9P</div>
                                </div>
                            </div>
                            <div class="float-card fc-2">
                                <div class="small-caps mb-2">President</div>
                                <div class="d-flex align-items-center gap-2 mb-1"><span class="small fw-semibold flex-grow-1">Ada</span><span class="small fw-bold">62%</span></div>
                                <div class="bar slim mb-2"><span style="width: 62%"></span></div>
                                <div class="d-flex align-items-center gap-2 mb-1"><span class="small fw-semibold flex-grow-1">Bola</span><span class="small fw-bold">38%</span></div>
                                <div class="bar slim"><span style="width: 38%"></span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Use cases strip -->
        <section class="border-bottom bg-white">
            <div class="container py-4">
                <div class="d-flex flex-wrap justify-content-center gap-3 gap-md-5 use-cases">
                    <span><i class="bi bi-mortarboard"></i> Student unions</span>
                    <span><i class="bi bi-trophy"></i> Award nights</span>
                    <span><i class="bi bi-building"></i> Staff &amp; board votes</span>
                    <span><i class="bi bi-people"></i> Clubs &amp; associations</span>
                    <span><i class="bi bi-house-heart"></i> Community polls</span>
                </div>
            </div>
        </section>

        <!-- Features -->
        <section id="features" class="py-6">
            <div class="container">
                <div class="text-center mx-auto mb-5" style="max-width: 640px">
                    <span class="eyebrow mb-3">Features</span>
                    <h2 class="section-title mb-3">Everything you need to run a vote</h2>
                    <p class="lead-muted mb-0">From the first category to the final tally, everything lives in one dashboard.</p>
                </div>
                <div class="row g-4">
                    <div v-for="f in features" :key="f.title" class="col-md-6 col-lg-4">
                        <div class="card-b pad hoverable h-100">
                            <div class="icon-tile mb-3" :class="f.tone"><i class="bi" :class="f.icon"></i></div>
                            <h3 class="h5 fw-800 mb-2">{{ f.title }}</h3>
                            <p class="text-muted mb-0">{{ f.text }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- How it works -->
        <section id="how" class="py-6 bg-white border-top border-bottom">
            <div class="container">
                <div class="text-center mx-auto mb-5" style="max-width: 640px">
                    <span class="eyebrow mb-3">How it works</span>
                    <h2 class="section-title mb-3">Three steps to results</h2>
                </div>
                <div class="row g-4">
                    <div v-for="(s, i) in steps" :key="s.title" class="col-md-4">
                        <div class="step h-100">
                            <div class="step-num">{{ i + 1 }}</div>
                            <h3 class="h5 fw-800 mt-3 mb-2">{{ s.title }}</h3>
                            <p class="text-muted mb-0">{{ s.text }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Integrity -->
        <section class="py-6">
            <div class="container">
                <div class="row g-5 align-items-center">
                    <div class="col-lg-5">
                        <span class="eyebrow mb-3">Integrity built in</span>
                        <h2 class="section-title mb-3">Every code counts once</h2>
                        <p class="lead-muted mb-4">
                            The server checks every ballot, not the browser. Voting windows, one-vote-per-code and
                            category rules can't be bypassed.
                        </p>
                        <NuxtLink to="/demo/results" class="btn btn-soft">See demo results <i class="bi bi-arrow-right"></i></NuxtLink>
                    </div>
                    <div class="col-lg-7">
                        <div class="row g-3">
                            <div v-for="p in integrity" :key="p.title" class="col-sm-6">
                                <div class="card-b pad h-100 d-flex gap-3">
                                    <i class="bi fs-4 text-brand" :class="p.icon"></i>
                                    <div>
                                        <div class="fw-bold text-ink mb-1">{{ p.title }}</div>
                                        <div class="small text-muted">{{ p.text }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA -->
        <section class="pb-6">
            <div class="container">
                <div class="cta text-center">
                    <div class="hero-glow"></div>
                    <div class="position-relative">
                        <h2 class="section-title text-white mb-3">Ready to run your next vote?</h2>
                        <p class="hero-lead mx-auto mb-4">Set up your first event and share the voting link today.</p>
                        <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center">
                            <NuxtLink :to="ctaLink" class="btn btn-light-on-dark btn-lg">Get started free <i class="bi bi-arrow-right"></i></NuxtLink>
                            <NuxtLink to="/demo/vote" class="btn btn-outline-light-on-dark btn-lg">Try the demo</NuxtLink>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>

<script setup lang="ts">
import { useAuthStore } from '~/stores/auth';

definePageMeta({ layout: 'public' });
useHead({ title: 'Ballotify · Online voting made simple' });

const auth = useAuthStore();
const ctaLink = computed(() => (auth.isAuthenticated ? '/dashboard' : '/signup'));

const features = [
    { icon: 'bi-ui-checks-grid', title: 'Flexible ballots', text: 'Add as many categories and candidates as you need, with photos. Choose one-per-category or multiple choice.', tone: '' },
    { icon: 'bi-key', title: 'One-time voter codes', text: 'Generate thousands of codes at once or import a name list. Export them to CSV or print them as slips.', tone: 'violet' },
    { icon: 'bi-calendar-range', title: 'Scheduled voting', text: 'Set opening and closing times. The ballot opens and closes on its own.', tone: 'amber' },
    { icon: 'bi-bar-chart-line', title: 'Live results', text: 'Rankings, percentages and turnout update as votes arrive. Share a public results page.', tone: 'green' },
    { icon: 'bi-phone', title: 'Works on any device', text: 'A simple, accessible ballot that works on a cheap phone as well as on a laptop.', tone: '' },
    { icon: 'bi-person-check', title: 'Turnout tracking', text: 'See who has voted and who hasn\'t, and re-issue a code when someone needs help.', tone: 'violet' },
];

const steps = [
    { title: 'Build your ballot', text: 'Create an event, add categories and candidates, and set when voting opens and closes.' },
    { title: 'Share codes & link', text: 'Generate a unique code per voter and share one voting link. Each code works only once.' },
    { title: 'Watch results live', text: 'Follow turnout and rankings in real time, then announce the winners.' },
];

const integrity = [
    { icon: 'bi-shield-lock', title: 'Single-use codes', text: 'A code is locked the instant it votes, even if two people submit at the same moment.' },
    { icon: 'bi-clock-history', title: 'Enforced time window', text: 'Ballots outside the voting window are rejected by the server.' },
    { icon: 'bi-speedometer2', title: 'Brute-force limits', text: 'Code guessing is rate-limited per network and event.' },
    { icon: 'bi-incognito', title: 'Anonymous results', text: 'Public results show totals only, never who voted for whom.' },
];
</script>

<style scoped>
.py-6 { padding-top: 5rem; padding-bottom: 5rem; }
.pb-6 { padding-bottom: 5rem; }
.hero { position: relative; overflow: hidden; background: var(--night); padding: 4.5rem 0 5rem; }
@media (min-width: 992px) { .hero { padding: 6rem 0 7rem; } }
.hero-glow {
    position: absolute; inset: 0; pointer-events: none;
    background:
        radial-gradient(600px 400px at 85% 20%, rgba(124, 92, 255, .35), transparent 60%),
        radial-gradient(500px 380px at 10% 90%, rgba(20, 145, 138, .35), transparent 60%);
}
.hero-lead { color: rgba(255, 255, 255, .72); font-size: 1.125rem; line-height: 1.7; max-width: 540px; }
.text-gradient-light { background: linear-gradient(100deg, #5eead4, #a78bfa); -webkit-background-clip: text; background-clip: text; color: transparent; }
.eyebrow.dark { background: rgba(255, 255, 255, .08); color: #99f6e4; border-color: rgba(255, 255, 255, .12); }
.hero-points span { color: rgba(255, 255, 255, .7); font-weight: 600; font-size: .92rem; display: inline-flex; gap: .45rem; align-items: center; }
.hero-points .bi { color: #5eead4; }
.hero-art { position: relative; max-width: 540px; margin: 0 auto; }
.hero-art img { width: 100%; height: auto; border-radius: 28px; box-shadow: 0 40px 80px -30px rgba(0, 0, 0, .7); border: 1px solid rgba(255, 255, 255, .08); }
.float-card {
    position: absolute; background: rgba(255, 255, 255, .96); border-radius: 16px; padding: .85rem 1rem;
    box-shadow: var(--shadow-lg); display: flex; gap: .7rem; align-items: center; animation: bob 6s ease-in-out infinite;
}
.fc-1 { left: -4%; bottom: 10%; }
.fc-2 { right: -4%; top: 8%; width: 190px; display: block; animation-delay: -3s; }
@media (max-width: 575.98px) { .fc-1 { left: 2%; bottom: 4%; } .fc-2 { right: 2%; top: 4%; width: 160px; } }
@keyframes bob { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-8px); } }
.use-cases span { color: var(--muted); font-weight: 600; display: inline-flex; align-items: center; gap: .5rem; }
.use-cases .bi { color: var(--brand-600); }
.step { padding: 1.75rem; border-radius: var(--radius-lg); background: var(--surface-2); border: 1px solid var(--line); }
.step-num {
    width: 44px; height: 44px; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center;
    background: linear-gradient(135deg, var(--brand-500), var(--accent)); color: #fff; font-weight: 800; font-size: 1.1rem;
}
.cta { position: relative; overflow: hidden; background: var(--night); border-radius: var(--radius-xl); padding: 4rem 1.5rem; }
</style>
