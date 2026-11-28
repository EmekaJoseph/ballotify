<template>
    <BaseModal :open="open" :title="t('card.title')" :subtitle="candidate?.name" size="md" @close="emit('close')">
        <div class="seg mb-3" role="group" :aria-label="t('card.title')">
            <button type="button" :class="{ on: format === 'square' }" @click="format = 'square'"><i class="bi bi-square"></i> {{ t('card.square') }}</button>
            <button type="button" :class="{ on: format === 'story' }" @click="format = 'story'"><i class="bi bi-phone"></i> {{ t('card.story') }}</button>
        </div>
        <div class="preview" :class="format">
            <img v-if="previewUrl" :src="previewUrl" :alt="t('card.title')" />
            <div v-else class="making"><span class="spinner-border spinner-border-sm"></span> {{ t('card.making') }}</div>
        </div>
        <p class="small text-muted mt-3 mb-0">{{ t('card.hint') }}</p>
        <canvas ref="canvas" class="d-none"></canvas>
        <template #footer>
            <button v-if="canShare" type="button" class="btn btn-ghost" :disabled="!blob" @click="share"><i class="bi bi-share"></i> {{ t('card.share') }}</button>
            <button type="button" class="btn btn-primary" :disabled="!blob" @click="download"><i class="bi bi-download"></i> {{ t('card.download') }}</button>
        </template>
    </BaseModal>
</template>

<script setup lang="ts">
import { publicApi } from '~/api/endpoints/public';

const props = defineProps<{
    open: boolean;
    candidate: { id: number; name: string; image_url?: string | null } | null;
    categoryName: string;
    eventName: string;
    /** The event's public link token (voting page + photo). */
    token: string;
}>();
const emit = defineEmits<{ close: [] }>();

const format = ref<'square' | 'story'>('square');
const canvas = ref<HTMLCanvasElement | null>(null);
const blob = ref<Blob | null>(null);
const previewUrl = ref('');
const photo = ref<Blob | null>(null);
const canShare = ref(false);
const voteUrl = computed(() => `${window.location.origin}/vote/${props.token}`);
const fileName = computed(() => `${slugify(props.candidate?.name ?? 'candidate')}-${format.value}.png`);

async function loadPhoto() {
    photo.value = null;
    if (!props.candidate?.image_url) return;
    try {
        // Through the API (CORS), so the canvas can still be saved as an image.
        const res = await fetch(publicApi.candidatePhotoUrl(props.token, props.candidate.id));
        if (res.ok) photo.value = await res.blob();
    } catch { /* initials instead */ }
}

async function render() {
    if (!props.open || !props.candidate || !canvas.value) return;
    blob.value = null;
    if (previewUrl.value) URL.revokeObjectURL(previewUrl.value);
    previewUrl.value = '';
    await drawCampaignCard(canvas.value, {
        name: props.candidate.name,
        category: props.categoryName,
        eventName: props.eventName,
        photo: photo.value,
        voteUrl: voteUrl.value,
        format: format.value,
        labels: { vote: t('card.vote'), forCategory: t('card.for', { category: props.categoryName }), scan: t('card.scan') },
    });
    blob.value = await new Promise<Blob | null>(r => canvas.value!.toBlob(r, 'image/png'));
    if (blob.value) previewUrl.value = URL.createObjectURL(blob.value);
    const file = blob.value ? new File([blob.value], fileName.value, { type: 'image/png' }) : null;
    canShare.value = !!file && typeof navigator.canShare === 'function' && navigator.canShare({ files: [file] });
}

watch(() => [props.open, props.candidate?.id], async ([o]) => {
    if (!o) return;
    format.value = 'square';
    await nextTick();
    await loadPhoto();
    await render();
});
watch(format, () => render());
onBeforeUnmount(() => { if (previewUrl.value) URL.revokeObjectURL(previewUrl.value); });

function download() {
    if (!blob.value) return;
    const a = document.createElement('a');
    a.href = previewUrl.value;
    a.download = fileName.value;
    a.click();
}
async function share() {
    if (!blob.value) return;
    try {
        await navigator.share({ files: [new File([blob.value], fileName.value, { type: 'image/png' })], text: `${t('card.vote')} ${props.candidate?.name}: ${voteUrl.value}` });
    } catch { /* cancelled */ }
}
</script>

<style scoped>
.seg { display: inline-flex; padding: 3px; gap: 3px; border-radius: 12px; background: var(--surface-2); border: 1px solid var(--line); }
.seg button { border: 0; background: none; padding: .35rem .8rem; border-radius: 9px; font-size: .85rem; font-weight: 700; color: var(--muted); display: inline-flex; gap: .4rem; align-items: center; }
.seg button.on { background: var(--surface); color: var(--brand-600); box-shadow: var(--shadow-sm); }
.preview { display: grid; place-items: center; background: var(--surface-2); border: 1px solid var(--line); border-radius: 14px; overflow: hidden; }
.preview.square { aspect-ratio: 1 / 1; }
.preview.story { aspect-ratio: 9 / 16; max-height: 60vh; margin: 0 auto; }
.preview img { width: 100%; height: 100%; object-fit: contain; display: block; }
.making { color: var(--muted); font-size: .9rem; display: flex; gap: .5rem; align-items: center; }
</style>
