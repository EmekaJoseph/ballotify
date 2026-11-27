<template>
    <footer class="site-footer">
        <div class="container py-5">
            <div class="row g-4 align-items-start">
                <div class="col-lg-5">
                    <AppLogo light />
                    <p class="mt-3 mb-0 footer-muted" style="max-width: 360px">
                        Secret ballot elections online, with one-time voter codes and certified results.
                    </p>
                </div>
                <div class="col-6 col-lg-2 offset-lg-1">
                    <div class="footer-h">Verify</div>
                    <NuxtLink to="/verify">Check a results certificate</NuxtLink>
                    <a href="https://www.agudatechy.com" target="_blank" rel="noopener">Agudatechy</a>
                </div>
                <div class="col-6 col-lg-2">
                    <div class="footer-h">Account</div>
                    <NuxtLink to="/signup">Create account</NuxtLink>
                    <NuxtLink to="/login">Sign in</NuxtLink>
                    <NuxtLink to="/dashboard">Dashboard</NuxtLink>
                </div>
            </div>
            <div class="footer-bottom mt-5 pt-4 d-flex flex-column flex-sm-row justify-content-between gap-2">
                <span>© {{ year }} Agudatechy. All rights reserved.</span>
                <span class="d-flex gap-3">
                    <NuxtLink to="/privacy" class="legal-link">Privacy</NuxtLink>
                    <NuxtLink to="/terms" class="legal-link">Terms</NuxtLink>
                </span>
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
.site-footer .legal-link { display: inline; padding: 0; color: rgba(255, 255, 255, .55); }
.site-footer a:not(.app-logo):not(.legal-link) { display: block; color: rgba(255, 255, 255, .75); padding: .25rem 0; font-weight: 500; }
.site-footer a:not(.app-logo):hover, .site-footer .legal-link:hover { color: #00d4c9; }
.footer-bottom { border-top: 1px solid rgba(255, 255, 255, .1); color: rgba(255, 255, 255, .45); font-size: .875rem; }
</style>
