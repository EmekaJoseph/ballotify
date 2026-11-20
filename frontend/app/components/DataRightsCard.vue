<template>
    <div class="card-b pad">
        <h3 class="h6 fw-800 mb-1">Your data</h3>
        <p class="small text-muted mb-3">Download a copy of everything we hold about your account, or delete the account. See our <NuxtLink to="/privacy" target="_blank">Privacy Policy</NuxtLink>.</p>
        <div class="d-flex flex-wrap gap-2">
            <button class="btn btn-ghost" :disabled="exporting" @click="exportData">
                <span v-if="exporting" class="spinner-border spinner-border-sm"></span><i v-else class="bi bi-download"></i> Download my data
            </button>
            <button class="btn btn-danger-soft" @click="open = true"><i class="bi bi-trash"></i> Delete my account</button>
        </div>

        <BaseModal :open="open" title="Delete your account?" size="sm" @close="open = false">
            <div class="d-flex gap-3 align-items-start mb-3">
                <div class="icon-tile danger flex-shrink-0"><i class="bi bi-exclamation-triangle"></i></div>
                <p class="small text-muted mb-0">
                    This permanently deletes your account and <b class="text-ink">all your events</b>, with their ballots, voter lists,
                    nominations and results. Payment records we must keep by law are kept. This can’t be undone.
                </p>
            </div>
            <form id="delete-form" novalidate @submit.prevent="remove">
                <label for="del-pw" class="form-label">Enter your password to confirm</label>
                <PasswordInput id="del-pw" v-model="password" autocomplete="current-password" />
            </form>
            <template #footer>
                <button class="btn btn-ghost" @click="open = false">Cancel</button>
                <button type="submit" form="delete-form" class="btn btn-danger" :disabled="deleting || !password">
                    <span v-if="deleting" class="spinner-border spinner-border-sm"></span> Delete account
                </button>
            </template>
        </BaseModal>
    </div>
</template>

<script setup lang="ts">
import { adminApi } from '~/api/endpoints/admin';
import { useAuthStore } from '~/stores/auth';

const toast = useToast();
const auth = useAuthStore();
const open = ref(false);
const password = ref('');
const exporting = ref(false);
const deleting = ref(false);

async function exportData() {
    exporting.value = true;
    try {
        const { data } = await adminApi.exportMyData();
        downloadFile('ballotify-my-data.json', JSON.stringify(data, null, 2), 'application/json');
        toast.success('Your data has been downloaded');
    } catch (e) {
        toast.error(apiErrorMessage(e), 'Download failed');
    } finally {
        exporting.value = false;
    }
}

async function remove() {
    deleting.value = true;
    try {
        await adminApi.deleteAccount(password.value);
        open.value = false;
        auth.clearSession(); // the token no longer exists on the server
        await navigateTo('/');
        toast.success('Your account has been deleted. Thank you for using Ballotify.');
    } catch (e) {
        toast.error(apiFieldErrors(e).password ?? apiErrorMessage(e), 'Account not deleted');
    } finally {
        deleting.value = false;
        password.value = '';
    }
}
</script>

<style scoped>
.icon-tile.danger { background: var(--danger-50); color: var(--danger); }
</style>
