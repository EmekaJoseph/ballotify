<template>
    <div>
        <PageHeader :title="step === 'template' ? 'Create an event' : (chosen ? chosen.name : 'Blank event')"
            :subtitle="step === 'template' ? 'Start from a template or a blank ballot. You can change everything later.' : 'Set the basics. You’ll add candidates and voters next.'"
            back="/dashboard/events" back-label="Events" />

        <!-- Step 1: template -->
        <div v-if="step === 'template'" class="row g-3">
            <div class="col-sm-6 col-xl-4">
                <button type="button" class="tpl card-b h-100" @click="pick(null)">
                    <span class="icon-tile"><i class="bi bi-file-earmark-plus"></i></span>
                    <span class="fw-800 text-ink d-block mt-3">Blank event</span>
                    <span class="small text-muted d-block">Build your own ballot from scratch.</span>
                </button>
            </div>
            <template v-if="templates.length">
                <div v-for="t in templates" :key="t.key" class="col-sm-6 col-xl-4">
                    <button type="button" class="tpl card-b h-100" @click="pick(t)">
                        <span class="d-flex justify-content-between align-items-start">
                            <span class="icon-tile" :class="t.voting_mode === 'paid' ? 'violet' : ''"><i class="bi" :class="t.icon"></i></span>
                            <span v-if="t.voting_mode === 'paid'" class="pill pill-violet"><i class="bi bi-cash-coin"></i> Paid voting</span>
                        </span>
                        <span class="fw-800 text-ink d-block mt-3">{{ t.name }}</span>
                        <span class="small text-muted d-block mb-2">{{ t.summary }}</span>
                        <span class="small text-muted d-block cats">{{ t.categories.join(' · ') }}</span>
                    </button>
                </div>
            </template>
            <template v-else-if="loadingTemplates">
                <div v-for="i in 3" :key="i" class="col-sm-6 col-xl-4"><div class="skeleton" style="height: 170px"></div></div>
            </template>
        </div>

        <!-- Step 2: details -->
        <div v-else class="row g-4">
            <div class="col-lg-8">
                <div class="card-b pad">
                    <EventForm :key="chosen?.key ?? 'blank'" :busy="busy" :server-errors="fieldErrors" :defaults="chosen" submit-label="Create event" @submit="create">
                        <template #actions>
                            <button type="button" class="btn btn-ghost" @click="step = 'template'"><i class="bi bi-arrow-left"></i> Templates</button>
                        </template>
                    </EventForm>
                </div>
            </div>
            <div class="col-lg-4">
                <div v-if="chosen" class="card-b pad mb-4">
                    <div class="small-caps mb-2">Included in this template</div>
                    <ul class="list-unstyled d-grid gap-1 mb-0 small">
                        <li v-for="c in chosen.categories" :key="c" class="d-flex gap-2"><i class="bi bi-check2 text-brand"></i><span>{{ c }}</span></li>
                    </ul>
                </div>
                <div class="card-b pad">
                    <div class="small-caps mb-3">What happens next</div>
                    <ol class="list-unstyled d-grid gap-3 mb-0">
                        <li v-for="(s, i) in next" :key="s" class="d-flex gap-3">
                            <span class="step-dot">{{ i + 1 }}</span><span class="text-muted">{{ s }}</span>
                        </li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { adminApi, type EventPayload } from '~/api/endpoints/admin';
import type { EventTemplate } from '~/types';

definePageMeta({ layout: 'dashboard', middleware: ['auth'] });
useHead({ title: 'New event · Ballotify' });

const route = useRoute();
const busy = ref(false);
const error = ref('');
useErrorAlert(error, 'Couldn’t create the event');
const fieldErrors = ref<Record<string, string>>({});
const templates = ref<EventTemplate[]>([]);
const loadingTemplates = ref(true);
const chosen = ref<EventTemplate | null>(null);
const step = ref<'template' | 'details'>('template');

const next = computed(() => chosen.value?.voting_mode === 'paid'
    ? ['Add nominees to each category, with photos.', 'Share the voting link: fans pay per vote.', 'Watch the live leaderboard, then present the winners.']
    : ['Add candidates to each category, with photos if you like.', 'Add voters and send them their codes.', 'Share the voting link and watch the turnout.']);

function pick(t: EventTemplate | null) {
    chosen.value = t;
    step.value = 'details';
    window.scrollTo({ top: 0 });
}

onMounted(async () => {
    try {
        templates.value = (await adminApi.getTemplates()).data;
        // /dashboard/events/create?template=award_night skips straight to the form.
        const preset = templates.value.find(t => t.key === route.query.template);
        if (preset) pick(preset);
    } catch {
        // No templates: blank events still work.
    } finally {
        loadingTemplates.value = false;
    }
});

async function create(payload: EventPayload) {
    busy.value = true;
    error.value = '';
    try {
        const { data } = await adminApi.createEvent({ ...payload, template: chosen.value?.key ?? null });
        useToast().success(chosen.value ? 'Event created with its categories. Now add your candidates.' : 'Event created. Now build your ballot.');
        await navigateTo(`/dashboard/events/${data.id}?tab=ballot`);
    } catch (e) {
        fieldErrors.value = apiFieldErrors(e);
        error.value = apiErrorMessage(e, 'Could not create the event.');
    } finally {
        busy.value = false;
    }
}
</script>

<style scoped>
.step-dot {
    width: 26px; height: 26px; border-radius: 8px; flex-shrink: 0; display: inline-flex; align-items: center; justify-content: center;
    background: var(--brand-50); color: var(--brand-700); font-weight: 800; font-size: .8rem;
}
.tpl {
    width: 100%; text-align: left; padding: 1.4rem; display: block; cursor: pointer;
    transition: border-color .15s var(--ease), transform .15s var(--ease), box-shadow .15s var(--ease);
}
.tpl:hover { border-color: var(--brand-500); transform: translateY(-2px); box-shadow: var(--shadow-lg); }
.tpl:focus-visible { outline: 2px solid var(--brand-500); outline-offset: 2px; }
.cats { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
</style>
