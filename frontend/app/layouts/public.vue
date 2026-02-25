<template>
    <div class="public-layout d-flex flex-column min-vh-100">
        <!-- Main Navbar -->
        <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm py-3 sticky-top">
            <div class="container">
                <NuxtLink to="/" class="navbar-brand d-flex align-items-center fw-bold text-primary">
                    <div
                        class="brand-icon me-2 d-flex align-items-center justify-content-center bg-primary text-white rounded">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                    Ballotify
                </NuxtLink>

                <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse"
                    data-bs-target="#navbarContent">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarContent">
                    <ul class="navbar-nav ms-auto align-items-center">
                        <li class="nav-item">
                            <NuxtLink to="/" class="nav-link px-3 fw-medium">Home</NuxtLink>
                        </li>
                        <li class="nav-item ms-lg-2">
                            <NuxtLink to="/login" class="btn btn-outline-primary rounded-pill px-4 fw-bold">Sign In
                            </NuxtLink>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="flex-grow-1">
            <slot />
        </main>

        <!-- Footer -->
        <TheFooter />
    </div>
</template>

<style scoped>
.brand-icon {
    width: 32px;
    height: 32px;
}

.navbar {
    z-index: 1030;
}
</style>
