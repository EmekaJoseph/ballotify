<template>
    <footer class="site-footer">
        <div class="container py-5">
            <div class="row g-4 align-items-start">
                <div class="col-lg-5">
                    <AppLogo light />
                    <p class="mt-3 mb-0 footer-muted" style="max-width: 360px">
                        Online elections, awards and polls with one-time voter codes and live results.
                    </p>
                </div>
                <div class="col-6 col-lg-2 offset-lg-1">
                    <div class="footer-h">Product</div>
                    <NuxtLink to="/#features">Features</NuxtLink>
                    <NuxtLink to="/#how">How it works</NuxtLink>
                    <NuxtLink to="/demo/vote">Demo ballot</NuxtLink>
                    <NuxtLink to="/demo/results">Demo results</NuxtLink>
                </div>
                <div class="col-6 col-lg-2">
                    <div class="footer-h">Account</div>
                    <NuxtLink to="/signup">Create account</NuxtLink>
                    <NuxtLink to="/login">Sign in</NuxtLink>
                    <NuxtLink to="/dashboard">Dashboard</NuxtLink>
                </div>
            </div>
            <div class="footer-bottom mt-5 pt-4 d-flex flex-column flex-sm-row justify-content-between gap-2">
                <span>© {{ year }} Ballotify. All rights reserved.</span>
                <span>Made for fair, simple voting.</span>
            </div>
        </div>
    </footer>
</template>

<script setup lang="ts">
const year = new Date().getFullYear();
</script>

<style scoped>
.site-footer { background: var(--night); color: #fff; }
.footer-muted { color: rgba(255, 255, 255, .6); }
.footer-h { font-size: .75rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: rgba(255, 255, 255, .45); margin-bottom: .85rem; }
.site-footer a:not(.app-logo) { display: block; color: rgba(255, 255, 255, .75); padding: .25rem 0; font-weight: 500; }
.site-footer a:not(.app-logo):hover { color: #5eead4; }
.footer-bottom { border-top: 1px solid rgba(255, 255, 255, .1); color: rgba(255, 255, 255, .45); font-size: .875rem; }
</style>
