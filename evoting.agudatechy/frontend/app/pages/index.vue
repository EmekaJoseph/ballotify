<template>
    <!-- The whole home page: one hero, with sign-in and sign-up. -->
    <section class="hero">
        <div class="hero-glow"></div>
        <div class="container position-relative">
            <div class="row align-items-center g-5">
                <div class="col-lg-7 text-center text-lg-start fade-up">
                    <span class="eyebrow dark mb-4"><i class="bi bi-shield-lock"></i> Secret ballot elections</span>
                    <h1 class="display-title text-white mb-4">
                        Fair, secret elections,<br />
                        <span class="text-gradient-light">run online.</span>
                    </h1>
                    <p class="hero-lead mx-auto mx-lg-0 mb-5">
                        Build your ballot, give every voter a one-time code, and watch the turnout as they vote from any phone or laptop.
                        For churches, schools, associations and cooperatives.
                    </p>

                    <div v-if="auth.isAuthenticated" class="d-flex justify-content-center justify-content-lg-start">
                        <NuxtLink to="/dashboard" class="btn btn-light-on-dark btn-lg">Go to your dashboard <i class="bi bi-arrow-right"></i></NuxtLink>
                    </div>
                    <div v-else class="d-flex flex-column flex-sm-row gap-3 justify-content-center justify-content-lg-start">
                        <NuxtLink to="/signup" class="btn btn-light-on-dark btn-lg">Create an account <i class="bi bi-arrow-right"></i></NuxtLink>
                        <NuxtLink to="/login" class="btn btn-outline-light-on-dark btn-lg"><i class="bi bi-box-arrow-in-right"></i> Sign in</NuxtLink>
                    </div>

                    <div class="d-flex flex-wrap gap-4 justify-content-center justify-content-lg-start mt-5 hero-points">
                        <span><i class="bi bi-shield-check"></i> One vote per code</span>
                        <span><i class="bi bi-incognito"></i> Votes stay secret</span>
                        <span><i class="bi bi-bar-chart-line"></i> Results when voting closes</span>
                    </div>
                </div>
                <div class="col-lg-5 text-center fade-up" style="animation-delay: .12s">
                    <img src="/brand/logo-on-dark.png" alt="Agudatechy" class="hero-logo" width="635" height="596" />
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
</script>

<style scoped>
.hero { position: relative; overflow: hidden; background: var(--night); padding: 4.5rem 0 5rem; min-height: calc(100vh - 140px); display: flex; align-items: center; }
@media (min-width: 992px) { .hero { padding: 6rem 0 7rem; } }
.hero > .container { width: 100%; }
.hero-glow {
    position: absolute; inset: 0; pointer-events: none;
    background:
        radial-gradient(600px 400px at 85% 25%, rgba(0, 212, 201, .28), transparent 60%),
        radial-gradient(500px 380px at 10% 90%, rgba(5, 133, 128, .4), transparent 60%);
}
.hero-lead { color: rgba(255, 255, 255, .74); font-size: 1.125rem; line-height: 1.7; max-width: 560px; }
.text-gradient-light { background: linear-gradient(100deg, #00d4c9, #5eead4); -webkit-background-clip: text; background-clip: text; color: transparent; }
.eyebrow.dark { background: rgba(255, 255, 255, .08); color: #99f6e4; border-color: rgba(255, 255, 255, .12); }
.hero-points span { color: rgba(255, 255, 255, .72); font-weight: 600; font-size: .92rem; display: inline-flex; gap: .45rem; align-items: center; }
.hero-points .bi { color: #00d4c9; }
.hero-logo { width: 100%; max-width: 340px; height: auto; filter: drop-shadow(0 24px 60px rgba(0, 212, 201, .18)); }
@media (max-width: 991.98px) { .hero-logo { max-width: 200px; } }
</style>
