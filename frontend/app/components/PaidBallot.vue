<template>
    <div>
        <div v-if="!paymentsReady" class="alert alert-warning border-0 rounded-4 small d-flex gap-2 mb-3">
            <i class="bi bi-exclamation-triangle"></i> {{ t('paid.unavailable') }}
        </div>

        <!-- Category tabs (sticky) -->
        <div v-if="categories.length > 1" class="cat-tabs mb-3" role="tablist" :aria-label="t('paid.categories')">
            <button v-for="cat in categories" :key="cat.id" type="button" role="tab" class="cat-tab"
                :class="{ active: activeCat === cat.id }" :aria-selected="activeCat === cat.id" @click="scrollTo(cat.id)">
                {{ cat.name }}
            </button>
        </div>

        <section v-for="cat in categories" :id="`cat-${cat.id}`" :key="cat.id" class="mb-4 cat-section">
            <h2 class="h5 fw-800 mb-3">{{ cat.name }}</h2>
            <div class="row g-3">
                <div v-for="cand in byCategory[cat.id]" :key="cand.id" class="col-6 col-md-4">
                    <div class="nominee card-b h-100">
                        <div class="photo" :style="cand.image_url ? {} : { background: colorFor(cand.name) }">
                            <img v-if="cand.image_url" :src="cand.image_url" :alt="cand.name" loading="lazy" />
                            <span v-else class="initials">{{ initials(cand.name) }}</span>
                        </div>
                        <div class="p-3 d-flex flex-column flex-grow-1">
                            <div class="fw-800 text-ink text-break lh-sm">{{ cand.name }}</div>
                            <div v-if="cand.description" class="small text-muted mt-1 desc">{{ cand.description }}</div>
                            <div class="d-flex gap-2 mt-auto" style="margin-top: .75rem !important">
                                <button type="button" class="btn btn-primary btn-sm flex-grow-1" :disabled="!paymentsReady" @click="openBuy(cand, cat.name)">
                                    <i class="bi bi-heart-fill"></i> {{ t('paid.vote') }}
                                </button>
                                <button type="button" class="btn btn-ghost btn-icon btn-sm" :title="t('card.title')" :aria-label="`${t('card.title')}: ${cand.name}`"
                                    @click="cardFor = { cand, category: cat.name }"><i class="bi bi-share"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div v-if="!(byCategory[cat.id]?.length)" class="text-muted small">{{ t('paid.noNominees') }}</div>
        </section>

        <!-- Buy votes -->
        <BaseModal :open="!!buying" :title="buying ? t('paid.voteFor', { name: buying.name }) : ''" :subtitle="buying?.category" size="sm" @close="buying = null">
            <form v-if="buying" id="buy-form" novalidate @submit.prevent="pay">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <CandidateAvatar :name="buying.name" :src="buying.image_url" size="lg" />
                    <div class="small text-muted">{{ t('paid.priceLine', { price: price.toLocaleString('en-NG') }) }}</div>
                </div>

                <label class="form-label" for="qty">{{ t('paid.howMany') }}</label>
                <div class="stepper mb-2">
                    <button type="button" class="btn btn-ghost" :aria-label="t('paid.fewer')" :disabled="qty <= 1" @click="qty = Math.max(1, qty - 1)"><i class="bi bi-dash-lg"></i></button>
                    <input id="qty" v-model.number="qty" type="number" min="1" max="10000" inputmode="numeric" class="form-control text-center fw-800" />
                    <button type="button" class="btn btn-ghost" :aria-label="t('paid.more')" @click="qty = Math.min(10000, qty + 1)"><i class="bi bi-plus-lg"></i></button>
                </div>
                <div class="d-flex flex-wrap gap-1 mb-3">
                    <button v-for="n in quick" :key="n" type="button" class="chip" :class="{ on: qty === n }" @click="qty = n">{{ n }}</button>
                </div>

                <div class="mb-3">
                    <label for="buyer-email" class="form-label">{{ t('paid.email') }} <span class="text-muted fw-normal">{{ t('paid.forReceipt') }}</span></label>
                    <input id="buyer-email" v-model.trim="email" type="email" class="form-control" autocomplete="email" placeholder="you@example.com" required />
                </div>
                <div class="mb-2">
                    <label for="buyer-name" class="form-label">{{ t('paid.name') }} <span class="text-muted fw-normal">{{ t('common.optional') }}</span></label>
                    <input id="buyer-name" v-model.trim="name" type="text" class="form-control" autocomplete="name" maxlength="120" />
                </div>
                <HumanCheck ref="human" v-model="captcha" class="mt-3" />
            </form>
            <template #footer>
                <div class="w-100">
                    <div class="d-flex justify-content-between align-items-baseline mb-2">
                        <span class="text-muted small">{{ t('paid.total') }}</span>
                        <span class="fs-4 fw-800 text-ink">₦{{ total.toLocaleString('en-NG') }}</span>
                    </div>
                    <div v-if="tooSmall" class="small text-warning-emphasis mb-2">{{ t('paid.min', { min: minPayment, n: minQty }) }}</div>
                    <button type="submit" form="buy-form" class="btn btn-primary btn-lg w-100" :disabled="paying || tooSmall || !validEmail || qty < 1">
                        <span v-if="paying" class="spinner-border spinner-border-sm"></span>
                        {{ paying ? t('paid.wait') : t('paid.pay') }}
                    </button>
                    <div class="small text-muted text-center mt-2"><i class="bi bi-lock"></i> {{ t('paid.secure') }}</div>
                </div>
            </template>
        </BaseModal>
        <CampaignCardModal :open="!!cardFor" :candidate="cardFor?.cand ?? null" :category-name="cardFor?.category ?? ''" :event-name="eventName ?? ''"
            :token="token" :brand-color="brandColor" @close="cardFor = null" />
        <IdleWarning :show="idle.warning.value" :seconds="idle.secondsLeft.value" :total="30" @stay="idle.stay" />
    </div>
</template>

<script setup lang="ts">
import { publicApi } from '~/api/endpoints/public';
import type { Candidate, Category } from '~/types';

const props = defineProps<{
    token: string;
    categories: Pick<Category, 'id' | 'name'>[];
    candidates: Candidate[];
    price: number;
    minPayment: number;
    paymentsReady: boolean;
    eventName?: string;
    brandColor?: string | null;
}>();
const cardFor = ref<{ cand: Candidate; category: string } | null>(null);

const byCategory = computed(() => {
    const map: Record<number, Candidate[]> = {};
    for (const c of props.candidates) (map[c.category_id] ??= []).push(c);
    return map;
});

// ----- Category tabs follow the scroll position
const activeCat = ref<number | null>(props.categories[0]?.id ?? null);
function scrollTo(id: number) {
    activeCat.value = id;
    document.getElementById(`cat-${id}`)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}
let observer: IntersectionObserver | null = null;
onMounted(() => {
    observer = new IntersectionObserver((entries) => {
        const visible = entries.filter(e => e.isIntersecting).sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top)[0];
        if (visible) activeCat.value = Number(visible.target.id.replace('cat-', ''));
    }, { rootMargin: '-120px 0px -60% 0px' });
    document.querySelectorAll('.cat-section').forEach(el => observer!.observe(el));
});
onBeforeUnmount(() => observer?.disconnect());

// ----- Buying votes
const buying = ref<(Candidate & { category: string }) | null>(null);
const qty = ref(1);
const email = ref('');
const name = ref('');
const paying = ref(false);
const { alert } = useAlert();
const captcha = ref('');
const human = ref<{ reset: () => void } | null>(null);
// A payment form left open on a shared phone closes by itself (nothing is charged).
const idle = useIdleTimeout({
    idleMs: 90 * 1000, warnMs: 30 * 1000, // warning after 1 minute, closes 30 s later
    active: () => !!buying.value && !paying.value,
    onTimeout: () => { buying.value = null; name.value = ''; },
});

const minQty = computed(() => Math.max(1, Math.ceil(props.minPayment / Math.max(1, props.price))));
const quick = computed(() => [...new Set([minQty.value, 5, 10, 20, 50, 100].filter(n => n >= minQty.value))].slice(0, 6));
const total = computed(() => Math.max(0, Math.floor(qty.value || 0)) * props.price);
const tooSmall = computed(() => total.value < props.minPayment);
const validEmail = computed(() => /^\S+@\S+\.\S+$/.test(email.value));

function openBuy(cand: Candidate, category: string) {
    buying.value = { ...cand, category };
    qty.value = Math.max(qty.value, minQty.value);
    try { email.value ||= localStorage.getItem('paid-vote-email') ?? ''; } catch { /* private mode */ }
}

async function pay() {
    if (!buying.value || tooSmall.value || !validEmail.value) return;
    paying.value = true;
    try { localStorage.setItem('paid-vote-email', email.value); } catch { /* private mode */ }
    try {
        const { data } = await publicApi.buyVotes(props.token, {
            candidate_id: buying.value.id, quantity: Math.floor(qty.value), email: email.value, name: name.value || undefined, captcha_token: captcha.value || undefined,
        });
        window.location.assign(data.authorization_url);
    } catch (e) {
        paying.value = false;
        human.value?.reset(); // each check works once
        // Pops up over the vote dialog; "Try again" retries with the same choices.
        // Field errors (e.g. "choose at least 2 votes") read as one message too.
        const retry = await alert({
            title: t('paid.didntStart'),
            message: Object.values(apiFieldErrors(e))[0] ?? apiErrorMessage(e, t('paid.startFailed')),
            actionText: t('common.tryAgain'),
        });
        if (retry) pay();
    }
}
</script>

<style scoped>
.cat-tabs {
    position: sticky; top: 64px; z-index: 5; display: flex; gap: .35rem; overflow-x: auto; padding: .4rem;
    background: var(--surface); border: 1px solid var(--line); border-radius: 14px; scrollbar-width: none;
}
.cat-tabs::-webkit-scrollbar { display: none; }
.cat-tab { flex-shrink: 0; border: 0; background: none; padding: .4rem .8rem; border-radius: 10px; font-weight: 700; font-size: .85rem; color: var(--muted); white-space: nowrap; }
.cat-tab.active { background: var(--brand-50); color: var(--brand-700); }
.cat-section { scroll-margin-top: 130px; }
.nominee { display: flex; flex-direction: column; overflow: hidden; transition: transform .15s var(--ease), box-shadow .15s var(--ease); }
.nominee:hover { transform: translateY(-2px); box-shadow: var(--shadow-lg); }
.photo { aspect-ratio: 1; background: var(--surface-2); display: grid; place-items: center; overflow: hidden; }
.photo img { width: 100%; height: 100%; object-fit: cover; }
.initials { color: rgba(255, 255, 255, .95); font-weight: 800; font-size: clamp(2rem, 7vw, 3rem); letter-spacing: .02em; }
.desc { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.35; }
.stepper { display: flex; gap: .4rem; }
.stepper .form-control { font-size: 1.3rem; }
.stepper input::-webkit-inner-spin-button { -webkit-appearance: none; }
.chip { border: 1px solid var(--line); background: var(--surface-2); border-radius: 999px; padding: .2rem .7rem; font-weight: 700; font-size: .8rem; color: var(--ink-2); }
.chip.on { background: var(--brand-600); border-color: var(--brand-600); color: #fff; }
</style>
