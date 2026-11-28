<template>
    <div>
        <DemoBanner text="Demo results with sample data. Votes trickle in to show live updates." />
        <ResultsHeader :name="results.event.name" status="active" :ends-at="results.event.ends_at" updated-at="just now">
            <NuxtLink to="/demo/vote" class="btn btn-light-on-dark btn-sm">Try the ballot</NuxtLink>
        </ResultsHeader>
        <ResultsBoard :results="results" />
    </div>
</template>

<script setup lang="ts">
definePageMeta({ layout: 'ballot', wide: true });
useHead({ title: 'Demo results · Ballotify' });

const extra = ref<Record<number, number>>({});
const results = computed(() => demoResults(extra.value));

let timer: ReturnType<typeof setInterval> | undefined;
onMounted(() => {
    timer = setInterval(() => {
        // Simulate a voter: one random candidate per category.
        const next = { ...extra.value };
        for (const cat of DEMO_EVENT.categories) {
            const options = DEMO_CANDIDATES.filter(c => c.category_id === cat.id);
            const pick = options[Math.floor(Math.random() * options.length)];
            if (pick) next[pick.id] = (next[pick.id] ?? 0) + 1;
        }
        extra.value = next;
    }, 2500);
});
onBeforeUnmount(() => clearInterval(timer));
</script>
