<template>
    <footer class="py-5 bg-white border-top mt-auto">
        <div class="container text-center">
            <div class="mb-4">
                <NuxtLink to="/"
                    class="navbar-brand d-flex align-items-center justify-content-center fw-bold text-primary">
                    <div
                        class="brand-icon me-2 d-flex align-items-center justify-content-center bg-primary text-white rounded small-icon">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                    Ballotify
                </NuxtLink>
            </div>
            <p class="text-muted mb-3">Secure, transparent, and easy digital voting platform.</p>
            <div class="d-flex justify-content-center gap-3 mb-4">
                <a href="#" class="text-muted hover-primary"><i class="bi bi-twitter-x"></i></a>
                <a href="#" class="text-muted hover-primary"><i class="bi bi-linkedin"></i></a>
                <a href="#" class="text-muted hover-primary"><i class="bi bi-github"></i></a>
            </div>
            <p class="small text-muted mb-0">&copy; 2026 Ballotify. All rights reserved.</p>
        </div>
    </footer>
</template>

<style scoped>
.small-icon {
    width: 24px;
    height: 24px;
    font-size: 0.8rem;
}

.hover-primary:hover {
    color: #0d6efd !important;
}

.navbar-brand {
    font-size: 1.25rem;
}
</style>
