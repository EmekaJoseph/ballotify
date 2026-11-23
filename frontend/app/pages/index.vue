<template>
    <div>
        <!-- Hero -->
        <section class="hero">
            <div class="hero-glow"></div>
            <div class="container position-relative">
                <div class="row align-items-center g-4 g-lg-5">
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
                        <HeroMockup />
                    </div>
                </div>
            </div>
        </section>

        <!-- Use cases strip -->
        <section class="border-bottom bg-surface">
            <div class="container py-4">
                <div class="d-flex flex-wrap justify-content-center gap-3 gap-md-5 use-cases">
                    <span><i class="bi bi-people"></i> Associations &amp; cooperatives</span>
                    <span><i class="bi bi-house-heart"></i> Churches &amp; ministries</span>
                    <span><i class="bi bi-building"></i> Staff &amp; board votes</span>
                    <span><i class="bi bi-trophy"></i> Award nights</span>
                    <span><i class="bi bi-mortarboard"></i> Schools &amp; unions</span>
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
        <section id="how" class="py-6 bg-surface border-top border-bottom">
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
                <div class="row g-4 g-lg-5 align-items-center">
                    <div class="col-lg-5">
                        <span class="eyebrow mb-3">Integrity built in</span>
                        <h2 class="section-title mb-3">Every code counts once</h2>
                        <p class="lead-muted mb-4">
                            The server checks every ballot, not the browser. Voting windows, one-vote-per-code and
                            category rules can't be bypassed, and code guessing is rate-limited.
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

        <!-- Award nights: paid public voting -->
        <section id="awards" class="py-6 bg-surface border-top border-bottom">
            <div class="container">
                <div class="row g-4 g-lg-5 align-items-center">
                    <div class="col-lg-6 order-lg-2">
                        <span class="eyebrow mb-3"><i class="bi bi-trophy"></i> Award nights & pageants</span>
                        <h2 class="section-title mb-3">Let fans vote, and raise money while they do</h2>
                        <p class="lead-muted mb-4">
                            Switch an event to <b>paid public voting</b>: anyone with the link picks a nominee and pays per vote with
                            card, transfer or USSD. Votes count the moment Paystack confirms, and you watch a live leaderboard.
                        </p>
                        <ul class="list-unstyled d-grid gap-2 mb-4">
                            <li v-for="p in awardPoints" :key="p" class="d-flex gap-2"><i class="bi bi-check2-circle text-brand"></i><span>{{ p }}</span></li>
                        </ul>
                        <NuxtLink :to="auth.isAuthenticated ? '/dashboard/events/create?template=award_night' : '/signup'" class="btn btn-primary">
                            Start an award night <i class="bi bi-arrow-right"></i>
                        </NuxtLink>
                    </div>
                    <div class="col-lg-6 order-lg-1">
                        <div class="award-mock card-b">
                            <div class="small-caps mb-3">Most Popular · live leaderboard</div>
                            <div v-for="(n, i) in awardMock" :key="n.name" class="mock-row">
                                <span class="rank" :class="{ gold: i === 0 }">{{ i + 1 }}</span>
                                <span class="avatar sm" :style="{ background: n.color }">{{ initials(n.name) }}</span>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="d-flex justify-content-between small"><b class="text-ink">{{ n.name }}</b><span class="text-muted">{{ formatNumber(n.votes) }} votes</span></div>
                                    <div class="bar slim mt-1"><span :style="{ width: (n.votes / awardMock[0]!.votes) * 100 + '%' }"></span></div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top small">
                                <span class="text-muted">₦50 per vote</span>
                                <span class="fw-800 text-ink">₦{{ formatNumber(awardMock.reduce((s, n) => s + n.votes, 0) * 50) }} raised</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <PricingPlans />

        <!-- FAQ -->
        <section id="faq" class="py-6">
            <div class="container" style="max-width: 820px">
                <div class="text-center mb-5">
                    <span class="eyebrow mb-3">FAQ</span>
                    <h2 class="section-title mb-2">Questions organisers ask</h2>
                </div>
                <div class="d-grid gap-2">
                    <details v-for="(f, i) in faqs" :key="f.q" class="faq card-b" :open="i === 0">
                        <summary>{{ f.q }}<i class="bi bi-chevron-down"></i></summary>
                        <p class="text-muted mb-0">{{ f.a }}</p>
                    </details>
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
    { icon: 'bi-ui-checks-grid', title: 'Flexible ballots', text: 'Categories, candidates with photos, and a pick limit per category. Shuffle the order so nobody gets the top spot.', tone: '' },
    { icon: 'bi-key', title: 'One-time voter codes', text: 'Import your list or generate codes. Send them by email, SMS or WhatsApp, or print slips.', tone: 'violet' },
    { icon: 'bi-calendar-range', title: 'Scheduled voting', text: 'Set opening and closing times. The ballot opens and closes on its own.', tone: 'amber' },
    { icon: 'bi-bar-chart-line', title: 'Live results', text: 'Rankings, percentages and turnout update as votes arrive. Share a public results page.', tone: 'green' },
    { icon: 'bi-phone', title: 'Works on any device', text: 'A simple, accessible ballot that works on a cheap phone as well as on a laptop.', tone: '' },
    { icon: 'bi-people', title: 'Your whole committee', text: 'Invite co-organisers as editors, voter managers or viewers. Remind non-voters with one click.', tone: 'violet' },
];

const steps = [
    { title: 'Build your ballot', text: 'Create an event, add categories and candidates, and set when voting opens and closes.' },
    { title: 'Share codes & link', text: 'Generate a unique code per voter and share one voting link. Each code works only once.' },
    { title: 'Watch results live', text: 'Follow turnout and rankings in real time, then announce the winners.' },
];

const integrity = [
    { icon: 'bi-incognito', title: 'Truly secret ballot', text: 'Only totals are stored. Nobody, not even the organiser, can see how anyone voted.' },
    { icon: 'bi-shield-lock', title: 'Single-use codes', text: 'A code is locked the instant it votes, even if two people submit at the same moment.' },
    { icon: 'bi-lock', title: 'Locked ballot + audit log', text: 'The ballot freezes at the first vote, and every organiser change is logged and disclosed.' },
    { icon: 'bi-patch-check', title: 'Voter receipts', text: 'Every voter gets a receipt to check their ballot was counted, without revealing their choices.' },
];

const awardPoints = [
    'No codes to hand out: share one link on WhatsApp and social media',
    'Fans buy as many votes as they like; receipts by email',
    'Live leaderboard, then an on-stage reveal of each winner',
    'Your share is paid out to your bank account',
];
const awardMock = [
    { name: 'Amaka Obi', votes: 1840, color: '#0e7c77' },
    { name: 'Tunde Bello', votes: 1525, color: '#7c5cff' },
    { name: 'Zainab Musa', votes: 960, color: '#e08a1e' },
];

const faqs = [
    { q: 'Can the organiser see how I voted?', a: 'No. With code voting Ballotify stores only the running totals. There is no record linking a code (or a person) to their choices, so nobody can look it up: not the organiser, not us.' },
    { q: 'How do voters get their codes?', a: 'Import your list and send codes by email or SMS (Pro), share each code on WhatsApp from your own phone (free), or print slips to hand out. Every code works once.' },
    { q: 'What stops someone voting twice?', a: 'Each code is locked the moment it is used, even if two people submit it at the same instant. Code guessing is rate-limited, and the ballot freezes at the first vote.' },
    { q: 'How do voters know their vote counted?', a: 'After voting they get a receipt code. They can enter it on the results page to confirm their ballot was received and counted. The receipt says nothing about who they chose.' },
    { q: 'How does paid voting work for award nights?', a: 'Fans pick a nominee and pay per vote through Paystack (card, transfer or USSD). Votes are added once the payment is confirmed. Ballotify keeps a small percentage and pays the rest to your bank account.' },
    { q: 'Do I have to pay to try it?', a: 'No. Build as many draft events as you like for free and run one live event with up to 50 voters. Upgrade a single event to Pro only when you need more.' },
    { q: 'Can my committee help run the election?', a: 'Yes. On Pro events, invite co-organisers as editors, voter managers or viewers. Every change anyone makes is recorded in the activity log.' },
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
.award-mock { padding: 1.5rem; box-shadow: var(--shadow-lg); }
.mock-row { display: flex; align-items: center; gap: .75rem; padding: .55rem 0; }
.rank { width: 24px; height: 24px; border-radius: 8px; display: grid; place-items: center; font-weight: 800; font-size: .8rem; background: var(--surface-2); color: var(--muted); }
.rank.gold { background: #fef3c7; color: #b45309; }
.faq { padding: 0; }
.faq summary { list-style: none; cursor: pointer; padding: 1.1rem 1.25rem; font-weight: 700; color: var(--ink); display: flex; justify-content: space-between; gap: 1rem; align-items: center; }
.faq summary::-webkit-details-marker { display: none; }
.faq summary .bi { transition: transform .2s var(--ease); color: var(--muted); }
.faq[open] summary .bi { transform: rotate(180deg); }
.faq p { padding: 0 1.25rem 1.1rem; }
.hero-points span { color: rgba(255, 255, 255, .7); font-weight: 600; font-size: .92rem; display: inline-flex; gap: .45rem; align-items: center; }
.hero-points .bi { color: #5eead4; }
.use-cases span { color: var(--muted); font-weight: 600; display: inline-flex; align-items: center; gap: .5rem; }
.use-cases .bi { color: var(--brand-600); }
.step { padding: 1.75rem; border-radius: var(--radius-lg); background: var(--surface-2); border: 1px solid var(--line); }
.step-num {
    width: 44px; height: 44px; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center;
    background: linear-gradient(135deg, var(--brand-500), var(--accent)); color: #fff; font-weight: 800; font-size: 1.1rem;
}
.cta { position: relative; overflow: hidden; background: var(--night); border-radius: var(--radius-xl); padding: 4rem 1.5rem; }
</style>
