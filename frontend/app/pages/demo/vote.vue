<template>
    <div>
        <DemoBanner text="This is a demo ballot. Nothing you choose here is saved." />

        <VoteSuccess v-if="done" :event-name="DEMO_EVENT.name" results-to="/demo/results" />

        <div v-else class="fade-up">
            <div class="mb-3">
                <div class="small-caps">Official ballot</div>
                <h1 class="h4 fw-800 mb-1">{{ DEMO_EVENT.name }}</h1>
                <p class="text-muted mb-0">{{ DEMO_EVENT.description }}</p>
            </div>
            <BallotForm :categories="DEMO_EVENT.categories" :candidates="DEMO_CANDIDATES" mode="single"
                :submitting="submitting" @submit="submit" />
        </div>
    </div>
</template>

<script setup lang="ts">
definePageMeta({ layout: 'ballot' });
useHead({ title: 'Demo ballot · Ballotify' });

const done = ref(false);
const submitting = ref(false);

function submit() {
    submitting.value = true;
    setTimeout(() => {
        submitting.value = false;
        done.value = true;
        window.scrollTo({ top: 0 });
    }, 700);
}
</script>
