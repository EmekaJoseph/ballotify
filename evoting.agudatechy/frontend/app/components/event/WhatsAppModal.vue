<template>
    <BaseModal :open="open" title="Share codes on WhatsApp" size="lg"
        subtitle="Each button opens WhatsApp with that voter’s personal message ready. Press Send in WhatsApp, then come back for the next one."
        @close="emit('close')">
        <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
            <div class="segmented segmented-sm" role="tablist">
                <button type="button" :class="{ active: show === 'todo' }" @click="show = 'todo'">To send ({{ todo.length }})</button>
                <button type="button" :class="{ active: show === 'all' }" @click="show = 'all'">All not voted ({{ pending.length }})</button>
            </div>
            <label class="form-check form-switch small mb-0 ms-auto">
                <input v-model="reminder" class="form-check-input" type="checkbox" role="switch" />
                <span class="form-check-label">Word it as a reminder</span>
            </label>
        </div>

        <EmptyState v-if="!list.length" icon="bi-whatsapp" :title="show === 'todo' ? 'Everyone has their code' : 'Everyone has voted'"
            :text="show === 'todo' ? 'All voters who haven’t voted have been sent their code. Switch to “All not voted” to send reminders.' : ''" />
        <ul v-else class="list-unstyled mb-0 wa-list">
            <li v-for="v in list" :key="v.id" class="wa-row">
                <div class="min-w-0 flex-grow-1">
                    <div class="fw-semibold text-ink text-truncate">{{ v.name || 'Anonymous voter' }}</div>
                    <div class="small text-muted">
                        <span v-if="v.phone"><i class="bi bi-phone"></i> {{ v.phone }}</span>
                        <span v-else><i class="bi bi-person-lines-fill"></i> No number: you’ll pick the contact in WhatsApp</span>
                    </div>
                </div>
                <span v-if="sentNow.has(v.id)" class="pill pill-active flex-shrink-0"><i class="bi bi-check2"></i> Opened</span>
                <span v-else-if="v.invited_at" class="pill pill-completed flex-shrink-0 d-none d-sm-inline-flex">Sent before</span>
                <a :href="link(v)" target="_blank" rel="noopener" class="btn btn-wa btn-sm flex-shrink-0" @click="markShared(v)">
                    <i class="bi bi-whatsapp"></i> {{ sentNow.has(v.id) ? 'Again' : 'Send' }}
                </a>
            </li>
        </ul>

        <template #footer>
            <span class="small text-muted me-auto"><i class="bi bi-shield-lock"></i> Codes are personal: send each one only to its voter.</span>
            <button class="btn btn-ghost" @click="emit('close')">Done</button>
        </template>
    </BaseModal>
</template>

<script setup lang="ts">
import { adminApi } from '~/api/endpoints/admin';
import type { BallotEvent, Voter } from '~/types';

const props = defineProps<{ open: boolean; event: BallotEvent; voters: Voter[]; voteUrl: string }>();
const emit = defineEmits<{ close: []; shared: [voter: Voter] }>();

const show = ref<'todo' | 'all'>('todo');
const reminder = ref(false);
const sentNow = ref(new Set<number>());

const pending = computed(() => props.voters.filter(v => !v.used_at));
const todo = computed(() => pending.value.filter(v => !v.invited_at || sentNow.value.has(v.id)));
const list = computed(() => (show.value === 'todo' ? todo.value : pending.value));

watch(() => props.open, (o) => {
    if (!o) return;
    sentNow.value = new Set();
    show.value = todo.value.length ? 'todo' : 'all';
    reminder.value = !todo.value.length;
});

function link(v: Voter) {
    return whatsappLink(v.phone, voterCodeMessage({
        eventName: props.event.name, voterName: v.name, code: v.code, voteUrl: props.voteUrl,
        endsAt: props.event.ends_at, reminder: reminder.value,
    }));
}

/** The link opens in a new tab; record it as sent so it stops showing as "not sent". */
async function markShared(v: Voter) {
    sentNow.value = new Set(sentNow.value).add(v.id);
    try {
        const { data } = await adminApi.markShared(props.event.id, v.id);
        emit('shared', data);
    } catch {
        // Not critical: the message was still opened.
    }
}
</script>

<style scoped>
.wa-list { max-height: 55vh; overflow-y: auto; margin: 0 -.25rem; }
.wa-row { display: flex; align-items: center; gap: .75rem; padding: .65rem .25rem; border-bottom: 1px solid var(--line); }
.wa-row:last-child { border-bottom: 0; }
.btn-wa { --bs-btn-color: #fff; --bs-btn-bg: #1da851; --bs-btn-border-color: #1da851; --bs-btn-hover-color: #fff; --bs-btn-hover-bg: #178a43; --bs-btn-hover-border-color: #178a43; font-weight: 700; }
.segmented-sm button { padding: .35rem .7rem; font-size: .82rem; }
</style>
