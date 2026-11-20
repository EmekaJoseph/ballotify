<template>
    <div>
        <div v-if="loading" class="card-b pad"><div class="skeleton mb-3" style="height: 30px; width: 60%"></div><div class="skeleton" style="height: 160px"></div></div>
        <div v-else-if="notFound" class="card-b text-center px-4 py-5">
            <div class="icon-tile lg mx-auto mb-3"><i class="bi bi-search"></i></div>
            <h1 class="h4 fw-800 mb-2">{{ t('nom.notFound') }}</h1>
            <p class="text-muted mb-0">{{ t('nom.checkLink') }}</p>
        </div>

        <template v-else-if="form">
            <div class="intro card-b overflow-hidden mb-3">
                <div class="intro-band">
                    <img v-if="form.brand?.logo_url" :src="form.brand.logo_url" alt="" class="brand-logo mb-3" />
                    <span class="pill" :class="form.open ? 'pill-live' : ''" :style="form.open ? '' : 'background: rgba(255,255,255,.12); color: #fff'">
                        <span class="dot"></span>{{ form.open ? t('nom.open') : t('nom.closed') }}
                    </span>
                    <h1 class="h2 fw-800 text-white mt-3 mb-2 text-break">{{ form.name }}</h1>
                    <p class="intro-text mb-0">{{ t('nom.intro') }}</p>
                </div>
                <div v-if="form.ends_at || form.voting_starts_at" class="d-flex flex-wrap gap-3 px-4 py-3 small text-muted">
                    <span v-if="form.ends_at && form.open"><i class="bi bi-hourglass-split me-1"></i>{{ t('nom.closes', { date: formatDate(form.ends_at, true) }) }}</span>
                    <span v-if="form.voting_starts_at"><i class="bi bi-calendar-event me-1"></i>{{ t('nom.votingOpens', { date: formatDate(form.voting_starts_at, true) }) }}</span>
                </div>
            </div>

            <div v-if="!form.open" class="card-b text-center px-4 py-5">
                <div class="icon-tile lg mx-auto mb-3"><i class="bi bi-lock"></i></div>
                <h2 class="h5 fw-800 mb-2">{{ t('nom.closedTitle') }}</h2>
                <p class="text-muted mb-3">{{ t('nom.closedText') }}</p>
                <NuxtLink :to="`/vote/${token}`" class="btn btn-primary">{{ t('nom.goVote') }}</NuxtLink>
            </div>

            <div v-else-if="done" class="card-b text-center px-4 py-5 fade-up">
                <div class="icon-tile lg mx-auto mb-3" style="background: var(--success-50); color: var(--success)"><i class="bi bi-check2-circle"></i></div>
                <h2 class="h5 fw-800 mb-2">{{ t('nom.received') }}</h2>
                <p class="text-muted mb-4">{{ done }}</p>
                <button class="btn btn-primary" @click="again"><i class="bi bi-plus-lg"></i> {{ t('nom.another') }}</button>
            </div>

            <form v-else class="card-b pad" novalidate @submit.prevent="submit">
                <label for="nom-cat" class="form-label">{{ t('nom.category') }}</label>
                <select id="nom-cat" v-model.number="categoryId" class="form-select form-select-lg mb-3">
                    <option :value="0" disabled>{{ t('nom.chooseCategory') }}</option>
                    <option v-for="c in form.categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>

                <label for="nom-name" class="form-label">{{ t('nom.who') }}</label>
                <input id="nom-name" v-model.trim="nominee" class="form-control form-control-lg mb-3" maxlength="120" :placeholder="t('nom.fullName')" />

                <label for="nom-reason" class="form-label">{{ t('nom.why') }} <span class="text-muted fw-normal">{{ t('common.optional') }}</span></label>
                <textarea id="nom-reason" v-model="reason" class="form-control mb-1" rows="2" maxlength="280" :placeholder="t('nom.whyPh')"></textarea>
                <div class="form-text text-end mb-3">{{ reason.length }}/280</div>

                <label for="nom-email" class="form-label">{{ t('nom.email') }} <span class="text-muted fw-normal">{{ t('nom.emailHint') }}</span></label>
                <input id="nom-email" v-model.trim="email" type="email" class="form-control mb-3" autocomplete="email" maxlength="255" />

                <HumanCheck ref="human" v-model="captcha" class="mb-3" />
                <button class="btn btn-primary btn-lg w-100" :disabled="busy || !categoryId || nominee.length < 2">
                    <span v-if="busy" class="spinner-border spinner-border-sm"></span> {{ t('nom.send') }}
                </button>
                <p class="small text-muted text-center mt-3 mb-0">{{ t('nom.organiserDecides') }}</p>
            </form>
        </template>
    </div>
</template>

<script setup lang="ts">
import { publicApi } from '~/api/endpoints/public';
import type { NominationForm } from '~/types';

definePageMeta({ layout: 'ballot' });
useBallotModeFor('nominate');
const route = useRoute();
const token = String(route.params.token ?? '');
const { alert } = useAlert();

const form = ref<NominationForm | null>(null);
const loading = ref(true);
const notFound = ref(false);
const categoryId = ref(0);
const nominee = ref('');
const reason = ref('');
const email = ref('');
const captcha = ref('');
const human = ref<{ reset: () => void } | null>(null);
const busy = ref(false);
const done = ref('');

useHead({ title: computed(() => `${t('nom.title')} · ${form.value?.name ?? 'Ballotify'}`) });
useBrandTheme(() => form.value?.brand);

onMounted(async () => {
    try {
        form.value = (await publicApi.nominationForm(token)).data;
        setEventLanguage(form.value.language);
        if (form.value.categories.length === 1) categoryId.value = form.value.categories[0]!.id;
    } catch (e) {
        notFound.value = apiStatus(e) === 404;
        if (!notFound.value) await alert({ title: t('nom.cantLoad'), message: apiErrorMessage(e) });
    } finally {
        loading.value = false;
    }
});

async function submit() {
    busy.value = true;
    try {
        const { data } = await publicApi.nominate(token, {
            category_id: categoryId.value, nominee_name: nominee.value, reason: reason.value.trim() || undefined,
            email: email.value || undefined, captcha_token: captcha.value || undefined,
        });
        done.value = data.message;
    } catch (e) {
        human.value?.reset();
        const fields = apiFieldErrors(e);
        await alert({ title: t('nom.notSent'), message: Object.values(fields)[0] ?? apiErrorMessage(e) });
    } finally {
        busy.value = false;
    }
}

function again() {
    done.value = '';
    nominee.value = '';
    reason.value = '';
}
</script>

<style scoped>
.intro-band {
    padding: 1.75rem 1.5rem;
    background:
        radial-gradient(420px 220px at 100% 0%, rgba(124, 92, 255, .45), transparent 60%),
        radial-gradient(420px 220px at 0% 100%, rgba(20, 145, 138, .55), transparent 60%),
        var(--night);
}
.intro-band .pill-live { background: rgba(74, 222, 128, .15); color: #86efac; }
.intro-text { color: rgba(255, 255, 255, .75); }
.brand-logo { display: block; max-height: 48px; max-width: 180px; object-fit: contain; border-radius: 8px; background: rgba(255, 255, 255, .92); padding: 4px 8px; }
</style>
