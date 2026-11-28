<template>
    <div class="auth-wrap">
        <div class="auth-side d-none d-lg-flex">
            <div class="position-relative d-flex flex-column h-100 p-5">
                <AppLogo light />
                <div class="my-auto">
                    <h2 class="text-white fw-800 display-6 mb-3" style="letter-spacing: -.03em">Fair votes,<br />instant results.</h2>
                    <p class="side-text mb-4">Run elections, awards and polls with one-time voter codes and a live results board.</p>
                    <ul class="list-unstyled d-grid gap-3 side-text mb-0">
                        <li>Set up a ballot in minutes</li>
                        <li>Thousands of voter codes in one click</li>
                        <li>Works on any phone or laptop</li>
                    </ul>
                </div>
                <div class="side-text small">© {{ year }} Agudatechy</div>
            </div>
        </div>
        <div class="auth-main position-relative">
            <div class="d-lg-none mb-4"><AppLogo /></div>
            <div class="w-100" style="max-width: 420px">
                <slot />
            </div>
        </div>
        <SupportButton :context="`Sign-in ${$route.path}`" />
    </div>
</template>

<script setup lang="ts">
const year = new Date().getFullYear();
</script>

<style scoped>
.auth-wrap { min-height: 100vh; display: flex; background: var(--surface); }
.auth-side { position: relative; overflow: hidden; width: 46%; max-width: 620px; background: var(--night); }
.side-text { color: rgba(255, 255, 255, .72); }
.side-text .bi { color: #5eead4; margin-right: .5rem; }
.auth-wrap { background: var(--surface); }
.auth-main { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 2.5rem 1.25rem; }
</style>
