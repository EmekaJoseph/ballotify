<template>
    <div>
        <PageHeader title="Account" subtitle="Your profile and sign-in details." />

        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card-b pad text-center">
                    <span class="avatar xl mx-auto mb-3" :style="{ background: colorFor(auth.user?.email) }">{{ initials(auth.user?.name) }}</span>
                    <h2 class="h5 fw-800 mb-1 text-break">{{ auth.user?.name || '…' }}</h2>
                    <p class="text-muted small mb-3 text-break">{{ auth.user?.email }}</p>
                    <div v-if="auth.user?.created_at" class="small text-muted">Member since {{ formatDate(auth.user.created_at) }}</div>
                    <div class="divider my-4"></div>
                    <button class="btn btn-ghost w-100" @click="logout"><i class="bi bi-box-arrow-right"></i> Sign out</button>
                </div>
            </div>

            <div class="col-lg-8 d-grid gap-4">
                <form class="card-b pad" novalidate @submit.prevent="saveProfile">
                    <h3 class="h6 fw-800 mb-4">Profile</h3>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="p-name" class="form-label">Full name</label>
                            <input id="p-name" v-model.trim="profile.name" class="form-control" :class="{ 'is-invalid': profileErrors.name }" maxlength="255" />
                            <div v-if="profileErrors.name" class="field-error">{{ profileErrors.name }}</div>
                        </div>
                        <div class="col-md-6">
                            <label for="p-email" class="form-label">Email</label>
                            <input id="p-email" v-model.trim="profile.email" type="email" class="form-control" :class="{ 'is-invalid': profileErrors.email }" maxlength="255" />
                            <div v-if="profileErrors.email" class="field-error">{{ profileErrors.email }}</div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end mt-4">
                        <button class="btn btn-primary" :disabled="savingProfile || !profileDirty">
                            <span v-if="savingProfile" class="spinner-border spinner-border-sm"></span> Save profile
                        </button>
                    </div>
                </form>

                <form class="card-b pad" novalidate @submit.prevent="savePassword">
                    <h3 class="h6 fw-800 mb-4">Change password</h3>
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="p-current" class="form-label">Current password</label>
                            <input id="p-current" v-model="pw.current" type="password" class="form-control" autocomplete="current-password"
                                :class="{ 'is-invalid': pwErrors.current_password }" style="max-width: 360px" />
                            <div v-if="pwErrors.current_password" class="field-error">{{ pwErrors.current_password }}</div>
                        </div>
                        <div class="col-md-6">
                            <label for="p-new" class="form-label">New password</label>
                            <input id="p-new" v-model="pw.next" type="password" class="form-control" autocomplete="new-password"
                                :class="{ 'is-invalid': pwErrors.password }" placeholder="At least 8 characters" />
                            <div v-if="pwErrors.password" class="field-error">{{ pwErrors.password }}</div>
                        </div>
                        <div class="col-md-6">
                            <label for="p-confirm" class="form-label">Confirm new password</label>
                            <input id="p-confirm" v-model="pw.confirm" type="password" class="form-control" autocomplete="new-password"
                                :class="{ 'is-invalid': pwErrors.confirm }" />
                            <div v-if="pwErrors.confirm" class="field-error">{{ pwErrors.confirm }}</div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end mt-4">
                        <button class="btn btn-primary" :disabled="savingPw || !pw.current || !pw.next">
                            <span v-if="savingPw" class="spinner-border spinner-border-sm"></span> Update password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { adminApi } from '~/api/endpoints/admin';
import { useAuthStore } from '~/stores/auth';

definePageMeta({ layout: 'dashboard', middleware: ['auth'] });
useHead({ title: 'Account · Ballotify' });

const auth = useAuthStore();
const toast = useToast();

const profile = reactive({ name: '', email: '' });
const profileErrors = ref<Record<string, string>>({});
const savingProfile = ref(false);
const profileDirty = computed(() => profile.name !== (auth.user?.name ?? '') || profile.email !== (auth.user?.email ?? ''));

const pw = reactive({ current: '', next: '', confirm: '' });
const pwErrors = ref<Record<string, string>>({});
const savingPw = ref(false);

// The user may load after this page mounts (fresh reload), so sync when it arrives.
watch(() => auth.user, (u) => {
    if (u) { profile.name = u.name; profile.email = u.email; }
}, { immediate: true });

onMounted(() => { if (!auth.user) auth.fetchUser(); });

async function saveProfile() {
    profileErrors.value = {};
    if (!profile.name) { profileErrors.value.name = 'Enter your name.'; return; }
    if (!/^\S+@\S+\.\S+$/.test(profile.email)) { profileErrors.value.email = 'Enter a valid email address.'; return; }
    savingProfile.value = true;
    try {
        const { data } = await adminApi.updateMe({ name: profile.name, email: profile.email });
        auth.user = data;
        toast.success('Profile saved');
    } catch (e) {
        profileErrors.value = apiFieldErrors(e);
        if (!Object.keys(profileErrors.value).length) toast.error(apiErrorMessage(e));
    } finally {
        savingProfile.value = false;
    }
}

async function savePassword() {
    pwErrors.value = {};
    if (pw.next.length < 8) { pwErrors.value.password = 'Password must be at least 8 characters.'; return; }
    if (pw.next !== pw.confirm) { pwErrors.value.confirm = 'Passwords do not match.'; return; }
    savingPw.value = true;
    try {
        await adminApi.updateMe({ current_password: pw.current, password: pw.next });
        Object.assign(pw, { current: '', next: '', confirm: '' });
        toast.success('Password updated');
    } catch (e) {
        pwErrors.value = apiFieldErrors(e);
        if (!Object.keys(pwErrors.value).length) toast.error(apiErrorMessage(e));
    } finally {
        savingPw.value = false;
    }
}

async function logout() {
    await auth.logout();
    await navigateTo('/login');
}
</script>
