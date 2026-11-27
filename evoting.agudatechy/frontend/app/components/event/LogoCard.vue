<template>
    <div class="card-b pad">
        <h3 class="h6 fw-800 mb-1">Event logo</h3>
        <p class="small text-muted mb-3">
            Your organisation’s logo on the voting, nominations and results pages, the report and the printed ballot.
            PNG, JPG or WebP, up to 2 MB. A square or wide logo on a transparent background looks best.
        </p>

        <div class="d-flex align-items-center gap-3 flex-wrap">
            <div class="logo-box">
                <img v-if="preview" :src="preview" alt="Event logo" />
                <i v-else class="bi bi-image text-muted fs-4"></i>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <label class="btn btn-soft btn-sm mb-0" :class="{ disabled: busy }">
                    <span v-if="busy" class="spinner-border spinner-border-sm"></span>
                    <i v-else class="bi bi-upload"></i> {{ preview ? 'Change logo' : 'Upload logo' }}
                    <input type="file" accept="image/png,image/jpeg,image/webp" class="visually-hidden" :disabled="busy" @change="onFile" />
                </label>
                <button v-if="preview" type="button" class="btn btn-ghost btn-sm text-danger" :disabled="busy" @click="remove">
                    <i class="bi bi-trash"></i> Remove
                </button>
            </div>
        </div>
        <div v-if="error" class="field-error mt-2">{{ error }}</div>
    </div>
</template>

<script setup lang="ts">
import { adminApi } from '~/api/endpoints/admin';
import type { BallotEvent } from '~/types';

const props = defineProps<{ event: BallotEvent }>();
const emit = defineEmits<{ saved: [] }>();
const toast = useToast();
const { confirm } = useConfirm();

const preview = ref<string | null>(props.event.brand_logo_url ?? null);
watch(() => props.event.brand_logo_url, (u) => { preview.value = u ?? null; });
const busy = ref(false);
const error = ref('');

async function send(form: FormData, message: string) {
    busy.value = true;
    error.value = '';
    try {
        const { data } = await adminApi.updateLogo(props.event.id, form);
        preview.value = data.brand_logo_url ?? null;
        toast.success(message);
        emit('saved');
    } catch (e) {
        error.value = Object.values(apiFieldErrors(e))[0] ?? apiErrorMessage(e);
    } finally {
        busy.value = false;
    }
}

/** Uploads straight away: picking a file is the "save". */
function onFile(e: Event) {
    const input = e.target as HTMLInputElement;
    const f = input.files?.[0];
    input.value = '';
    if (!f) return;
    if (f.size > 2 * 1024 * 1024) { error.value = 'That image is larger than 2 MB.'; return; }
    const fd = new FormData();
    fd.append('logo', f);
    send(fd, 'Logo saved');
}

async function remove() {
    if (!await confirm({ title: 'Remove the logo?', message: 'The voting pages will show without a logo.', confirmText: 'Remove', danger: true })) return;
    const fd = new FormData();
    fd.append('remove_logo', '1');
    send(fd, 'Logo removed');
}
</script>

<style scoped>
.logo-box { width: 88px; height: 88px; border-radius: 16px; border: 1px dashed var(--line-strong); display: grid; place-items: center; overflow: hidden; background: var(--surface-2); flex-shrink: 0; padding: .4rem; }
.logo-box img { max-width: 100%; max-height: 100%; object-fit: contain; }
</style>
