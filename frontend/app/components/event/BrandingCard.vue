<template>
    <div class="card-b pad">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h3 class="h6 fw-800 mb-0">Ballot branding</h3>
            <span class="pill pill-pro"><i class="bi bi-lightning-charge-fill"></i> Pro</span>
        </div>
        <p class="small text-muted mb-3">Your logo and colour on the voting and results pages.</p>

        <template v-if="event.limits?.custom_branding">
            <div class="d-flex align-items-center gap-3 mb-3">
                <div class="logo-box">
                    <img v-if="preview" :src="preview" alt="Logo preview" />
                    <i v-else class="bi bi-image text-muted"></i>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <label class="btn btn-soft btn-sm mb-0">
                        <i class="bi bi-upload"></i> {{ preview ? 'Change logo' : 'Upload logo' }}
                        <input type="file" accept="image/png,image/jpeg,image/webp" class="visually-hidden" @change="onFile" />
                    </label>
                    <button v-if="preview" type="button" class="btn btn-ghost btn-sm" :disabled="busy" @click="removeLogo">Remove</button>
                </div>
            </div>
            <label class="form-label" for="brand-color">Accent colour</label>
            <div class="d-flex align-items-center gap-2 mb-2">
                <input id="brand-color" v-model="color" type="color" class="form-control form-control-color" />
                <input v-model="color" class="form-control mono" maxlength="7" aria-label="Colour hex code" style="max-width: 120px" />
                <button v-if="event.brand_color" class="btn btn-link btn-sm text-muted" @click="color = DEFAULT">Reset</button>
            </div>
            <div class="d-flex flex-wrap gap-1 mb-3">
                <button v-for="c in SWATCHES" :key="c" type="button" class="swatch" :style="{ background: c }" :aria-label="`Use ${c}`" @click="color = c"></button>
            </div>
            <div v-if="error" class="field-error mb-2">{{ error }}</div>
            <button class="btn btn-primary w-100" :disabled="busy || !validColor" @click="save">
                <span v-if="busy" class="spinner-border spinner-border-sm"></span> Save branding
            </button>
        </template>
        <div v-else class="small d-flex flex-column gap-2">
            <span class="text-muted">Upgrade this event to Pro to use your own logo and colour (and remove “Powered by Ballotify”).</span>
            <button class="btn btn-soft btn-sm align-self-start" @click="emit('upgrade')"><i class="bi bi-lightning-charge"></i> Upgrade</button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { adminApi } from '~/api/endpoints/admin';
import type { BallotEvent } from '~/types';

const props = defineProps<{ event: BallotEvent }>();
const emit = defineEmits<{ upgrade: []; saved: [event: BallotEvent] }>();
const toast = useToast();

const DEFAULT = '#0E7C77';
const SWATCHES = ['#0E7C77', '#1D4ED8', '#7C3AED', '#BE185D', '#B91C1C', '#C2410C', '#15803D', '#0F172A'];

const color = ref(props.event.brand_color ?? DEFAULT);
const file = ref<File | null>(null);
const preview = ref<string | null>(props.event.brand_logo_url ?? null);
const busy = ref(false);
const error = ref('');
const validColor = computed(() => /^#[0-9a-f]{6}$/i.test(color.value));

function onFile(e: Event) {
    const input = e.target as HTMLInputElement;
    const f = input.files?.[0];
    input.value = '';
    if (!f) return;
    if (f.size > 2 * 1024 * 1024) { error.value = 'That logo is larger than 2 MB.'; return; }
    error.value = '';
    if (preview.value?.startsWith('blob:')) URL.revokeObjectURL(preview.value);
    file.value = f;
    preview.value = URL.createObjectURL(f);
}

async function send(form: FormData, message: string) {
    busy.value = true;
    error.value = '';
    try {
        const { data } = await adminApi.updateBranding(props.event.id, form);
        file.value = null;
        preview.value = data.brand_logo_url ?? null;
        toast.success(message);
        emit('saved', data);
    } catch (e) {
        error.value = Object.values(apiFieldErrors(e))[0] ?? apiErrorMessage(e);
    } finally {
        busy.value = false;
    }
}

function save() {
    const fd = new FormData();
    fd.append('brand_color', color.value.toUpperCase() === DEFAULT ? '' : color.value);
    if (file.value) fd.append('logo', file.value);
    send(fd, 'Branding saved');
}

function removeLogo() {
    const fd = new FormData();
    fd.append('remove_logo', '1');
    send(fd, 'Logo removed');
}
</script>

<style scoped>
.logo-box { width: 64px; height: 64px; border-radius: 14px; border: 1px dashed var(--line-strong); display: grid; place-items: center; overflow: hidden; background: var(--surface-2); flex-shrink: 0; }
.logo-box img { max-width: 100%; max-height: 100%; object-fit: contain; }
.swatch { width: 26px; height: 26px; border-radius: 8px; border: 2px solid var(--surface); box-shadow: 0 0 0 1px var(--line); }
</style>
