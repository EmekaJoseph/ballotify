<template>
    <!-- A new account the admin hasn't approved yet: it can't create events until then. -->
    <div class="card-b awaiting text-center">
        <div class="icon-tile lg mx-auto mb-3"><i class="bi bi-hourglass-split"></i></div>
        <h2 class="h5 fw-800 mb-2">Your account is waiting for approval</h2>
        <p class="text-muted mb-3 mx-auto" style="max-width: 460px">
            Thanks for signing up to {{ appName }}. Agudatechy reviews every new account before it can run elections.
            You’ll be able to create events as soon as yours is approved.
        </p>
        <div class="d-flex flex-wrap justify-content-center gap-2">
            <a v-if="support.whatsapp" :href="support.whatsappHref('Account approval')" target="_blank" rel="noopener" class="btn btn-primary">
                <i class="bi bi-whatsapp"></i> Ask about my approval
            </a>
            <a v-else-if="support.email" :href="`mailto:${support.email}?subject=${encodeURIComponent('Account approval')}`" class="btn btn-primary">
                <i class="bi bi-envelope"></i> Ask about my approval
            </a>
            <button type="button" class="btn btn-ghost" :disabled="checking" @click="check">
                <span v-if="checking" class="spinner-border spinner-border-sm"></span><i v-else class="bi bi-arrow-clockwise"></i> Check again
            </button>
        </div>
        <p v-if="!auth.isVerified" class="small text-muted mt-3 mb-0">
            <i class="bi bi-envelope-check"></i> Meanwhile, confirm your email address with the link we sent you.
        </p>
    </div>
</template>

<script setup lang="ts">
import { useAuthStore } from '~/stores/auth';

const auth = useAuthStore();
const support = useSupport();
const appName = String(useRuntimeConfig().public.companyName || 'E-voting by Agudatechy');
const toast = useToast();
const checking = ref(false);

/** Ask the server again: approval may have come through since the page opened. */
async function check() {
    checking.value = true;
    try {
        await auth.fetchUser();
        if (!auth.user?.awaiting_approval) toast.success('Your account is approved. You can create events now.');
        else toast.info('Not approved yet. We’ll be with you shortly.');
    } finally {
        checking.value = false;
    }
}
</script>

<style scoped>
.awaiting { padding: 2.5rem 1.5rem; }
</style>
