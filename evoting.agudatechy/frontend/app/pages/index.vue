<template>
    <!-- The whole home page: one hero, with sign-in and sign-up. -->
    <section class="hero">
        <div class="container position-relative">
            <div class="row align-items-center g-5">
                <div class="col-lg-7 text-center text-lg-start fade-up">
                    <span class="eyebrow dark mb-4">Secret ballot elections</span>
                    <h1 class="display-title text-white mb-4">
                        Fair, secret elections,<br />
                        <span class="text-gradient-light">run online.</span>
                    </h1>
                    <p class="hero-lead mx-auto mx-lg-0 mb-5">
                        Build your ballot, give every voter a one-time code, and watch the turnout as they vote from any phone or laptop.
                        For churches, schools, associations and cooperatives.
                    </p>

                    <div v-if="auth.isAuthenticated" class="d-flex justify-content-center justify-content-lg-start">
                        <NuxtLink to="/dashboard" class="btn btn-light-on-dark btn-lg">Go to your dashboard</NuxtLink>
                    </div>
                    <div v-else class="d-flex flex-column flex-sm-row gap-3 justify-content-center justify-content-lg-start">
                        <NuxtLink to="/signup" class="btn btn-light-on-dark btn-lg">Create an account</NuxtLink>
                        <NuxtLink to="/login" class="btn btn-outline-light-on-dark btn-lg">Sign in</NuxtLink>
                    </div>

                    <div class="d-flex flex-wrap gap-4 justify-content-center justify-content-lg-start mt-5 hero-points">
                        <span>One vote per code</span>
                        <span>Votes stay secret</span>
                        <span>Results when voting closes</span>
                    </div>
                </div>
                <div class="col-lg-5 text-center fade-up" style="animation-delay: .12s">
                    <!-- A small mock ballot: shows what voters see, without being a real form. -->
                    <div class="mock" aria-hidden="true">
                        <div class="mock-top">
                            <span class="mock-chip">Secret ballot</span>
                            <span class="mock-code">Code · K7Q-29X</span>
                        </div>
                        <div class="mock-title">President</div>
                        <div class="mock-sub">Choose one</div>
                        <div v-for="(c, i) in mockCandidates" :key="c" class="mock-row" :class="{ on: i === 1 }">
                            <span class="mock-av">{{ c.split(' ').map(w => w[0]).join('') }}</span>
                            <span class="flex-grow-1 text-start">{{ c }}</span>
                            <span class="mock-radio"><i v-if="i === 1" class="bi bi-check-lg"></i></span>
                        </div>
                        <div class="mock-btn">Review and submit</div>
                        <div class="mock-turnout">
                            <div class="d-flex justify-content-between"><span>Turnout</span><b>68%</b></div>
                            <div class="mock-bar"><span style="width: 68%"></span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup lang="ts">
import { useAuthStore } from '~/stores/auth';

definePageMeta({ layout: 'public' });
useHead({ title: 'E-voting by Agudatechy · Secret ballot elections online' });

const auth = useAuthStore();
const mockCandidates = ['Ada Okafor', 'Bayo Adeyemi', 'Hauwa Musa'];
</script>

<style scoped>
.hero { position: relative; overflow: hidden; background: var(--night); padding: 4.5rem 0 5rem; min-height: calc(100vh - 140px); display: flex; align-items: center; }
@media (min-width: 992px) { .hero { padding: 6rem 0 7rem; } }
.hero > .container { width: 100%; }
.hero-lead { color: rgba(255, 255, 255, .74); font-size: 1.125rem; line-height: 1.7; max-width: 560px; }
.text-gradient-light { color: #5eead4; }
.eyebrow.dark { background: rgba(255, 255, 255, .08); color: #99f6e4; border-color: rgba(255, 255, 255, .12); }
.hero-points span { color: rgba(255, 255, 255, .72); font-weight: 600; font-size: .92rem; display: inline-flex; gap: .45rem; align-items: center; }
.hero-points .bi { color: #00d4c9; }
.mock {
    max-width: 360px; margin: 0 auto; padding: 1.25rem; border-radius: 20px; text-align: left;
    background: rgba(255, 255, 255, .06); border: 1px solid rgba(255, 255, 255, .12);
    color: #fff;
}
.mock-top { display: flex; justify-content: space-between; align-items: center; gap: .5rem; margin-bottom: 1rem; }
.mock-chip { font-size: .72rem; font-weight: 700; color: #99f6e4; background: rgba(0, 212, 201, .14); padding: .25rem .6rem; border-radius: 999px; display: inline-flex; gap: .3rem; }
.mock-code { font-size: .72rem; color: rgba(255, 255, 255, .55); font-family: ui-monospace, monospace; }
.mock-title { font-weight: 800; font-size: 1.15rem; }
.mock-sub { font-size: .8rem; color: rgba(255, 255, 255, .55); margin-bottom: .75rem; }
.mock-row { display: flex; align-items: center; gap: .65rem; padding: .6rem .7rem; border-radius: 12px; margin-bottom: .45rem; font-weight: 600; font-size: .92rem; border: 1px solid rgba(255, 255, 255, .1); background: rgba(255, 255, 255, .04); }
.mock-row.on { border-color: #00d4c9; background: rgba(0, 212, 201, .12); }
.mock-av { width: 32px; height: 32px; border-radius: 50%; display: grid; place-items: center; font-size: .72rem; font-weight: 800; background: rgba(255, 255, 255, .12); }
.mock-row.on .mock-av { background: #058580; }
.mock-radio { width: 20px; height: 20px; border-radius: 50%; border: 2px solid rgba(255, 255, 255, .3); display: grid; place-items: center; font-size: .75rem; }
.mock-row.on .mock-radio { background: #00d4c9; border-color: #00d4c9; color: #032b29; }
.mock-btn { margin-top: .9rem; text-align: center; padding: .65rem; border-radius: 12px; background: #058580; font-weight: 700; font-size: .9rem; }
.mock-turnout { margin-top: 1rem; font-size: .78rem; color: rgba(255, 255, 255, .65); }
.mock-turnout b { color: #fff; }
.mock-bar { height: 6px; border-radius: 999px; background: rgba(255, 255, 255, .12); margin-top: .35rem; overflow: hidden; }
.mock-bar span { display: block; height: 100%; border-radius: 999px; background: #058580; }
</style>
