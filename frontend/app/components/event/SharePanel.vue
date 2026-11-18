<template>
    <div class="row g-4">
        <div class="col-lg-7 d-flex flex-column gap-4 min-w-0">
            <div v-for="link in links" :key="link.title" class="card-b pad">
                <div class="d-flex gap-3 align-items-start mb-3">
                    <div class="icon-tile" :class="link.tone"><i class="bi" :class="link.icon"></i></div>
                    <div>
                        <h3 class="h6 fw-800 mb-1">{{ link.title }}</h3>
                        <p class="small text-muted mb-0">{{ link.text }}</p>
                    </div>
                </div>
                <div class="link-box">
                    <span class="truncate mono small">{{ link.url }}</span>
                    <button class="btn btn-primary btn-sm flex-shrink-0" @click="copyText(link.url, 'Link copied')"><i class="bi bi-copy"></i> Copy</button>
                    <a :href="link.url" target="_blank" rel="noopener" class="btn btn-ghost btn-sm flex-shrink-0" aria-label="Open in new tab"><i class="bi bi-box-arrow-up-right"></i></a>
                </div>
            </div>

            <div class="card-b pad">
                <h3 class="h6 fw-800 mb-2">Message template</h3>
                <p class="small text-muted mb-3">Paste into email, WhatsApp or SMS. Add each voter's code where shown.</p>
                <pre class="message mb-3">{{ message }}</pre>
                <button class="btn btn-soft btn-sm" @click="copyText(message, 'Message copied')"><i class="bi bi-copy"></i> Copy message</button>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card-b pad text-center">
                <h3 class="h6 fw-800 mb-1">QR code</h3>
                <p class="small text-muted mb-3">Put it on posters and screens. Scanning it opens the ballot.</p>
                <div class="qr-frame mx-auto mb-3">
                    <img v-if="qr" :src="qr" alt="QR code for the voting link" width="240" height="240" />
                    <div v-else class="skeleton" style="width: 240px; height: 240px"></div>
                </div>
                <a v-if="qr" :href="qr" :download="`${slugify(event.name)}-qr.png`" class="btn btn-ghost btn-sm">
                    <i class="bi bi-download"></i> Download PNG
                </a>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import QRCode from 'qrcode';
import type { BallotEvent } from '~/types';

const props = defineProps<{ event: BallotEvent }>();
const { voteUrl, resultsUrl } = useEventLinks(() => props.event.link_token);
const qr = ref('');

const links = computed(() => [
    { title: 'Voting link', text: 'Send this to voters. They need their personal code to vote.', url: voteUrl.value, icon: 'bi-box-arrow-in-right', tone: '' },
    { title: 'Live results page', text: 'Public tally with no voter details. Show it on a screen or share it after voting closes.', url: resultsUrl.value, icon: 'bi-bar-chart-line', tone: 'violet' },
]);

const message = computed(() => [
    `You're invited to vote in "${props.event.name}".`,
    props.event.ends_at ? `Voting closes ${formatDate(props.event.ends_at, true)}.` : '',
    '',
    `1. Open: ${voteUrl.value}`,
    '2. Enter your code: [YOUR CODE]',
    '',
    'Your code can be used once only.',
].filter((l, i, arr) => l !== '' || arr[i - 1] !== '').join('\n'));

watchEffect(async () => {
    if (!voteUrl.value) return;
    qr.value = await QRCode.toDataURL(voteUrl.value, { width: 480, margin: 1, color: { dark: '#0a1222', light: '#ffffff' } });
});
</script>

<style scoped>
.link-box { display: flex; align-items: center; gap: .5rem; padding: .5rem .5rem .5rem .85rem; border: 1px solid var(--line); border-radius: 12px; background: var(--surface-2); }
.link-box > span { flex: 1; min-width: 0; color: var(--ink-2); }
.message { white-space: pre-wrap; overflow-wrap: anywhere; font-family: inherit; font-size: .9rem; background: var(--surface-2); border: 1px solid var(--line); border-radius: 12px; padding: 1rem; color: var(--ink-2); }
.qr-frame { width: 256px; max-width: 100%; padding: 8px; border: 1px solid var(--line); border-radius: 16px; background: #fff; }
.qr-frame img { width: 100%; height: auto; display: block; }
</style>
