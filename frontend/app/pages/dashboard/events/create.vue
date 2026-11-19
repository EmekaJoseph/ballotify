<template>
    <div>
        <PageHeader title="Create an event" subtitle="Set the basics. You'll add categories, candidates and voters next."
            back="/dashboard/events" back-label="Events" />

        <div class="row g-4">
            <div class="col-lg-8">
                <!-- At the free limit: explain up front instead of a form that will be refused. -->
                <div v-if="blocked" class="card-b">
                    <EmptyState icon="bi-lock" title="You’ve reached your free event limit"
                        text="Upgrade your open event to Pro, or close voting on it, to create another.">
                        <div class="d-flex flex-wrap gap-2 justify-content-center">
                            <NuxtLink to="/dashboard/events" class="btn btn-ghost">Back to events</NuxtLink>
                            <button class="btn btn-primary" @click="showLimit"><i class="bi bi-lightning-charge"></i> See options</button>
                        </div>
                    </EmptyState>
                </div>
                <div v-else class="card-b pad">
                    <div v-if="error" class="alert alert-danger border-0 rounded-4 small">{{ error }}</div>
                    <EventForm :busy="busy" :server-errors="fieldErrors" submit-label="Create event" @submit="create">
                        <template #actions>
                            <NuxtLink to="/dashboard/events" class="btn btn-ghost">Cancel</NuxtLink>
                        </template>
                    </EventForm>
                </div>
            </div>
            <div class="col-lg-4">
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

definePageMeta({ layout: 'dashboard', middleware: ['auth'] });
useHead({ title: 'New event · Ballotify' });

const busy = ref(false);
const error = ref('');
const fieldErrors = ref<Record<string, string>>({});
const next = [
    'Add categories (e.g. President, Treasurer).',
    'Add candidates to each category, with photos if you like.',
    'Generate voter codes and share the voting link.',
];

const { blocked, showLimit, refreshQuota } = useNewEvent();
onMounted(async () => {
    await refreshQuota();
    if (blocked.value) showLimit();
});

async function create(payload: EventPayload) {
    busy.value = true;
    error.value = '';
    try {
        const { data } = await adminApi.createEvent(payload);
        useToast().success('Event created. Now build your ballot.');
        await navigateTo(`/dashboard/events/${data.id}?tab=ballot`);
    } catch (e) {
        if (planLimitReason(e) === 'events') {
            // Limit reached since the page opened: explain in a modal, not a banner up top.
            await refreshQuota();
            showLimit();
            return;
        }
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
</style>
