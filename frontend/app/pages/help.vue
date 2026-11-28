<template>
    <div>
        <!-- Hero + search -->
        <section class="help-hero">
            <div class="container py-5 text-center">
                <div class="small-caps text-brand mb-2">Help centre</div>
                <h1 class="display-6 fw-800 mb-2">How can we help?</h1>
                <p class="text-muted mx-auto mb-4" style="max-width: 560px">Step-by-step guides for running your election, AGM vote, poll or award night.</p>
                <div class="search mx-auto">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <input v-model.trim="query" type="search" class="form-control form-control-lg" placeholder="Search, e.g. “send codes”, “motion”, “USSD”" aria-label="Search the help centre" />
                </div>
                <div v-if="support.whatsapp || support.email" class="d-flex flex-wrap justify-content-center gap-2 mt-3">
                    <a v-if="support.whatsapp" :href="support.whatsappHref('Help centre')" target="_blank" rel="noopener" class="btn btn-wa btn-sm">Chat with us on WhatsApp</a>
                    <a v-if="support.email" :href="`mailto:${support.email}`" class="btn btn-ghost btn-sm">{{ support.email }}</a>
                </div>
            </div>
        </section>

        <div class="container py-5">
            <!-- Search results -->
            <template v-if="query">
                <p class="text-muted mb-3">{{ results.length }} result{{ results.length === 1 ? '' : 's' }} for “{{ query }}”</p>
                <div class="d-grid gap-3">
                    <HelpArticleCard v-for="a in results" :id="`a-${a.id}`" :key="a.id" :article="a" open />
                </div>
                <EmptyState v-if="!results.length" class="card-b" icon="bi-search" title="Nothing found"
                    text="Try another word, or ask us directly." />
            </template>

            <template v-else>
                <!-- Video -->
                <section v-if="support.videoEmbed" class="mb-5">
                    <h2 class="h5 fw-800 mb-3">Watch the 3-minute tour</h2>
                    <div class="video card-b">
                        <iframe :src="support.videoEmbed" title="Ballotify tour" allow="accelerometer; encrypted-media; gyroscope; picture-in-picture; fullscreen"
                            allowfullscreen loading="lazy" referrerpolicy="strict-origin-when-cross-origin"></iframe>
                    </div>
                </section>

                <!-- Quick start -->
                <section class="mb-5">
                    <h2 class="h5 fw-800 mb-3">Your first event in 6 steps</h2>
                    <ol class="steps list-unstyled mb-0">
                        <li v-for="(s, i) in QUICK_START" :key="s.title">
                            <a :href="`#a-${s.article}`" class="step card-b" @click.prevent="openArticle(s.article)">
                                <span class="num">{{ i + 1 }}</span>
                                <span class="min-w-0">
                                    <span class="d-block fw-bold text-ink">{{ s.title }}</span>
                                    <span class="d-block small text-muted">{{ s.text }}</span>
                                </span>
                            </a>
                        </li>
                    </ol>
                </section>

                <!-- Guides by topic -->
                <section v-for="sec in HELP_SECTIONS" :id="`s-${sec.key}`" :key="sec.key" class="mb-5">
                    <h2 class="h5 fw-800 mb-3">{{ sec.label }}</h2>
                    <div class="d-grid gap-2">
                        <HelpArticleCard v-for="a in bySection(sec.key)" :id="`a-${a.id}`" :key="a.id" :article="a" :open="opened === a.id"
                            @toggle="opened = opened === a.id ? '' : a.id" />
                    </div>
                </section>
            </template>

            <!-- Still stuck -->
            <section class="card-b pad still text-center mt-4">
                <h2 class="h5 fw-800 mb-1">Still stuck?</h2>
                <p class="text-muted mb-3">We’re happy to help you set up your event, or to look at a problem with you.</p>
                <div class="d-flex flex-wrap justify-content-center gap-2">
                    <a v-if="support.whatsapp" :href="support.whatsappHref('Help centre')" target="_blank" rel="noopener" class="btn btn-wa">WhatsApp us</a>
                    <a v-if="support.email" :href="`mailto:${support.email}`" class="btn btn-ghost">Email us</a>
                    <NuxtLink to="/demo/vote" class="btn btn-ghost">Try the demo ballot</NuxtLink>
                </div>
            </section>
        </div>
    </div>
</template>

<script setup lang="ts">
definePageMeta({ layout: 'public' });
useHead({ title: 'Help centre · Ballotify', meta: [{ name: 'description', content: 'Guides for running elections, AGM votes, polls and award nights with Ballotify.' }] });

const support = useSupport();
const route = useRoute();
const query = ref(String(route.query.q ?? ''));
const opened = ref(String(route.hash || '').replace('#a-', ''));

const bySection = (key: string) => HELP_ARTICLES.filter(a => a.section === key);
const fold = (s: string) => s.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
const results = computed(() => {
    const words = fold(query.value).split(/\s+/).filter(Boolean);
    return HELP_ARTICLES.filter((a) => {
        const text = fold([a.title, a.summary, ...(a.steps ?? []), ...(a.tips ?? [])].join(' '));
        return words.every(w => text.includes(w));
    });
});

async function openArticle(id: string) {
    query.value = '';
    opened.value = id;
    await nextTick();
    document.getElementById(`a-${id}`)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}
onMounted(() => { if (opened.value) openArticle(opened.value); });
</script>

<style scoped>
.help-hero { background: none; border-bottom: 1px solid var(--line); }
.search { position: relative; max-width: 560px; }
.search > i { position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--muted); }
.search .form-control { padding-left: 2.7rem; border-radius: 16px; }
.steps { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: .75rem; }
.step { display: flex; gap: .85rem; align-items: flex-start; padding: 1rem; text-decoration: none; height: 100%; transition: border-color .15s, transform .15s; }
.step:hover { border-color: var(--brand-500); transform: translateY(-1px); }
.step .num { width: 30px; height: 30px; border-radius: 50%; display: grid; place-items: center; flex-shrink: 0; background: var(--brand-600); color: #fff; font-weight: 800; font-size: .9rem; }
.video { position: relative; aspect-ratio: 16 / 9; overflow: hidden; padding: 0; }
.video iframe { position: absolute; inset: 0; width: 100%; height: 100%; border: 0; }
section[id^='s-'], [id^='a-'] { scroll-margin-top: 90px; }
</style>
