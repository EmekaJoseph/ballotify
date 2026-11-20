<template>
    <div>
        <div v-if="loading" class="card-b pad"><div class="skeleton mb-3" style="height: 30px; width: 50%"></div><div class="skeleton" style="height: 180px"></div></div>
        <div v-else-if="!profile" class="card-b text-center px-4 py-5">
            <div class="icon-tile lg mx-auto mb-3"><i class="bi bi-link-45deg"></i></div>
            <h1 class="h4 fw-800 mb-2">This link doesn’t work</h1>
            <p class="text-muted mb-0">It may have been switched off. Ask the organiser for a new one.</p>
        </div>

        <template v-else>
            <div class="mb-3">
                <div class="small-caps">Your candidate profile</div>
                <h1 class="h4 fw-800 mb-0 text-break">{{ profile.event.name }}</h1>
                <div class="small text-muted">{{ profile.category }}</div>
            </div>

            <div class="card-b pad">
                <div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
                    <CandidateAvatar :name="profile.name" :src="preview" size="xl" />
                    <div class="min-w-0">
                        <div class="h5 fw-800 mb-1 text-break">{{ profile.name }}</div>
                        <div v-if="profile.editable" class="d-flex flex-wrap gap-2">
                            <label class="btn btn-soft btn-sm mb-0">
                                <i class="bi bi-image"></i> {{ preview ? 'Change photo' : 'Add a photo' }}
                                <input type="file" accept="image/png,image/jpeg,image/webp" class="visually-hidden" @change="onFile" />
                            </label>
                            <button v-if="preview" type="button" class="btn btn-ghost btn-sm" @click="clearPhoto">Remove</button>
                        </div>
                        <div class="form-text mt-1">A clear face photo works best. JPG, PNG or WebP, up to 5 MB.</div>
                    </div>
                </div>

                <label for="cp-desc" class="form-label">Short description</label>
                <textarea id="cp-desc" v-model="description" class="form-control" rows="3" maxlength="280" :disabled="!profile.editable"
                    placeholder="e.g. Final-year Law · campaigning for longer library hours"></textarea>
                <div class="form-text d-flex justify-content-between"><span>Shown under your name on the ballot and the results.</span><span>{{ description.length }}/280</span></div>

                <div v-if="profile.editable" class="d-flex justify-content-end mt-4">
                    <button class="btn btn-primary" :disabled="saving || !dirty" @click="save">
                        <span v-if="saving" class="spinner-border spinner-border-sm"></span> Save my profile
                    </button>
                </div>
                <div v-else class="alert alert-secondary border-0 rounded-4 small mt-3 mb-0"><i class="bi bi-lock"></i> Voting has closed, so the profile can’t change any more.</div>
            </div>
            <p class="small text-muted text-center mt-3"><i class="bi bi-shield-check"></i> Your name and category are set by the organiser. Changes you make are recorded in the event’s activity log.</p>
        </template>
    </div>
</template>

<script setup lang="ts">
import { publicApi } from '~/api/endpoints/public';
import type { CandidateProfile } from '~/types';

definePageMeta({ layout: 'ballot' });
useBallotModeFor('other');
useHead({ title: 'Candidate profile · Ballotify' });

const route = useRoute();
const token = String(route.params.token ?? '');
const toast = useToast();

const profile = ref<CandidateProfile | null>(null);
const loading = ref(true);
const saving = ref(false);
const description = ref('');
const preview = ref<string | null>(null);
const file = ref<File | null>(null);
const removePhoto = ref(false);
const dirty = computed(() => !!file.value || removePhoto.value || description.value.trim() !== (profile.value?.description ?? ''));

onMounted(async () => {
    try {
        profile.value = (await publicApi.candidateProfile(token)).data;
        description.value = profile.value.description ?? '';
        preview.value = profile.value.image_url;
    } catch { /* link not valid */ } finally { loading.value = false; }
});

function onFile(e: Event) {
    const input = e.target as HTMLInputElement;
    const f = input.files?.[0];
    input.value = '';
    if (!f) return;
    if (f.size > 5 * 1024 * 1024) { toast.error('That photo is larger than 5 MB.', 'Photo too large'); return; }
    if (preview.value?.startsWith('blob:')) URL.revokeObjectURL(preview.value);
    file.value = f;
    preview.value = URL.createObjectURL(f);
    removePhoto.value = false;
}
function clearPhoto() {
    file.value = null;
    preview.value = null;
    removePhoto.value = true;
}

async function save() {
    saving.value = true;
    const fd = new FormData();
    fd.append('description', description.value.trim());
    if (file.value) fd.append('image', file.value);
    if (removePhoto.value && !file.value) fd.append('remove_image', '1');
    try {
        const { data } = await publicApi.updateCandidateProfile(token, fd);
        profile.value = { ...profile.value!, ...data };
        description.value = data.description ?? '';
        preview.value = data.image_url;
        file.value = null;
        removePhoto.value = false;
        toast.success('Your profile is saved');
    } catch (e) {
        toast.error(Object.values(apiFieldErrors(e))[0] ?? apiErrorMessage(e), 'Profile not saved');
    } finally {
        saving.value = false;
    }
}
</script>
