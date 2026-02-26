<template>
    <div class="main-wrapper page-bg py-5">
        <main class="container py-lg-5">
            <!-- Hero Section -->
            <section class="hero-section mb-5 animate-fade-in">
                <div class="row align-items-center gy-5">
                    <div class="col-lg-7 text-center text-lg-start">
                        <div
                            class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3 py-2 small d-inline-block mb-4 fw-bold tracking-widest uppercase border border-primary border-opacity-10">
                            <i class="bi bi-stars me-1"></i> The Future of Voting
                        </div>
                        <h1 class="display-3 fw-black text-dark lh-sm mb-4">
                            Modern Voting, <br />
                            <span class="text-primary-gradient">Perfectly Simplified.</span>
                        </h1>
                        <p class="lead text-muted mb-5 mx-auto mx-lg-0 fw-medium opacity-75" style="max-width: 600px;">
                            Create, manage, and scale your elections with Ballotify. Secure, transparent, and
                            vibrant voting experience for organizations that value democracy.
                        </p>
                        <div class="d-flex flex-wrap justify-content-center justify-content-lg-start gap-4">
                            <button
                                class="btn btn-primary btn-lg px-5 py-3 fw-black rounded-pill shadow-lg transition-hover">
                                Create Your Event <i class="bi bi-plus-lg ms-2"></i>
                            </button>
                            <NuxtLink to="/demo/vote"
                                class="btn btn-outline-dark btn-lg px-5 py-3 fw-bold rounded-pill transition-hover">
                                View Demo <i class="bi bi-play-circle ms-2"></i>
                            </NuxtLink>
                        </div>
                        <div
                            class="mt-5 d-flex align-items-center justify-content-center justify-content-lg-start gap-3">
                            <div class="avatar-group d-flex">
                                <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=Felix"
                                    class="avatar border border-white border-2 rounded-circle" alt="User">
                                <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=Anita"
                                    class="avatar border border-white border-2 rounded-circle ms-n3" alt="User">
                                <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=Brian"
                                    class="avatar border border-white border-2 rounded-circle ms-n3" alt="User">
                            </div>
                            <p class="small mb-0 fw-bold opacity-75">
                                <span class="text-primary fw-black">5,000+</span> trust Ballotify daily
                            </p>
                        </div>
                    </div>
                    <div class="col-lg-5 animate-slide-up" style="animation-delay: 0.2s">
                        <div class="hero-img-container position-relative">
                            <div class="glass-card p-2 overflow-hidden position-relative z-1">
                                <img src="/hero_illustration.png" alt="Ballotify Hero"
                                    class="img-fluid rounded-4 animate-float">
                            </div>
                            <div class="hero-blob bg-primary opacity-20 rounded-circle blur-3xl position-absolute"
                                style="top: -10%; right: -10%; width: 300px; height: 300px;"></div>
                            <div class="hero-blob bg-info opacity-20 rounded-circle blur-3xl position-absolute"
                                style="bottom: -10%; left: -10%; width: 250px; height: 250px;"></div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Features Grid -->
            <section class="features-section py-5">
                <div class="text-center mb-5 animate-slide-up">
                    <h2 class="display-5 fw-black text-dark mb-3">Enterprise Features</h2>
                    <p class="text-muted mx-auto fw-medium opacity-75" style="max-width: 600px;">
                        Premium tools designed for community polls and organizational elections.
                    </p>
                </div>
                <div class="row g-4">
                    <!-- Feature 1 -->
                    <div class="col-md-6 col-lg-4 animate-slide-up" style="animation-delay: 0.1s">
                        <div class="glass-card p-4 h-100 transition-hover border-0 shadow-sm">
                            <div class="feature-icon bg-primary bg-opacity-10 text-primary rounded-4 mb-4 d-flex align-items-center justify-content-center"
                                style="width: 64px; height: 64px;">
                                <i class="bi bi-shield-lock-fill fs-3"></i>
                            </div>
                            <h4 class="fw-black text-dark mb-3">Secure Voting</h4>
                            <p class="text-muted fw-medium small opacity-75">End-to-end encryption and blockchain
                                verification ensure every vote is tamper-proof and authentic.</p>
                        </div>
                    </div>
                    <!-- Feature 2 -->
                    <div class="col-md-6 col-lg-4 animate-slide-up" style="animation-delay: 0.2s">
                        <div class="glass-card p-4 h-100 transition-hover border-0 shadow-sm">
                            <div class="feature-icon bg-success bg-opacity-10 text-success rounded-4 mb-4 d-flex align-items-center justify-content-center"
                                style="width: 64px; height: 64px;">
                                <i class="bi bi-link-45deg fs-3"></i>
                            </div>
                            <h4 class="fw-black text-dark mb-3">Unique Links</h4>
                            <p class="text-muted fw-medium small opacity-75">Generate one-time use tokens for your
                                voters, ensuring each person participates exactly once.</p>
                        </div>
                    </div>
                    <!-- Feature 3 -->
                    <div class="col-md-6 col-lg-4 animate-slide-up" style="animation-delay: 0.3s">
                        <div class="glass-card p-4 h-100 transition-hover border-0 shadow-sm">
                            <div class="feature-icon bg-info bg-opacity-10 text-info rounded-4 mb-4 d-flex align-items-center justify-content-center"
                                style="width: 64px; height: 64px;">
                                <i class="bi bi-bar-chart-fill fs-3"></i>
                            </div>
                            <h4 class="fw-black text-dark mb-3">Live Analytics</h4>
                            <p class="text-muted fw-medium small opacity-75">Monitor voter turnout and results in
                                real-time with beautiful, interactive dashboards.</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- CTA Section -->
            <section class="cta-section py-5 animate-slide-up" style="animation-delay: 0.4s">
                <div class="glass-card p-5 text-center overflow-hidden position-relative">
                    <div class="position-relative z-1">
                        <h2 class="display-4 fw-black text-dark mb-4">Host Your Next Election</h2>
                        <p class="lead text-muted mb-5 mx-auto fw-medium opacity-75" style="max-width: 600px;">
                            Join thousands of organizations using Ballotify to make fair decisions with confidence.
                        </p>
                        <button class="btn btn-dark btn-lg rounded-pill px-5 py-3 fw-bold shadow-lg transition-hover">
                            Get Started Now <i class="bi bi-arrow-right ms-2"></i>
                        </button>
                    </div>
                    <div class="hero-blob bg-primary opacity-10 rounded-circle blur-3xl position-absolute"
                        style="top: -20%; left: -10%; width: 400px; height: 400px;"></div>
                </div>
            </section>
        </main>
    </div>
</template>

<script setup lang="ts">
definePageMeta({
    layout: 'public'
});
</script>

<style scoped>
.avatar {
    width: 44px;
    height: 44px;
    object-fit: cover;
}

.ms-n3 {
    margin-left: -1rem !important;
}

.hero-img-container {
    perspective: 1000px;
}

.animate-float {
    animation: float 6s ease-in-out infinite;
}

@keyframes float {

    0%,
    100% {
        transform: translateY(0px) rotate(0deg);
    }

    50% {
        transform: translateY(-20px) rotate(2deg);
    }
}

.blur-3xl {
    filter: blur(64px);
}

.hero-blob {
    z-index: 0;
    pointer-events: none;
}

.uppercase {
    text-transform: uppercase;
}

.tracking-widest {
    letter-spacing: 0.2em;
}

.tracking-tight {
    letter-spacing: -0.025em;
}

/* Responsive adjustments */
@media (max-width: 991.98px) {
    .display-3 {
        font-size: 3rem;
    }

    .hero-section {
        text-align: center;
        margin-top: 2rem;
    }
}
</style>
