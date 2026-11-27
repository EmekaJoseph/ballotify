<template>
    <div class="d-grid gap-4">
        <!-- Language of the voting pages -->
        <section class="card-b pad">
            <div class="opt-head">
                <span class="icon-tile"><i class="bi bi-translate"></i></span>
                <div class="flex-grow-1 min-w-0">
                    <h3 class="h6 fw-800 mb-1">Voting page language</h3>
                    <p class="small text-muted mb-0">
                        The language voters see first on the voting, nominations and results pages.
                        They can still switch with the language button at the top of the page.
                    </p>
                </div>
                <AppSelect id="opt-lang" :model-value="event.language || 'en'" :options="LANGUAGES.map(l => ({ value: l.code, label: l.name }))"
                    :block="false" class="lang-select" :disabled="busy === 'language'" aria-label="Voting page language" @change="saveLanguage" />
            </div>
        </section>

        <!-- Nominations -->
        <section class="card-b pad">
            <div class="opt-head">
                <span class="icon-tile violet"><i class="bi bi-megaphone"></i></span>
                <div class="flex-grow-1 min-w-0">
                    <h3 class="h6 fw-800 mb-1">Nominations</h3>
                    <p class="small text-muted mb-0">Before voting, let the public suggest nominees for each category. You choose who goes on the ballot.</p>
                </div>
                <div class="form-check form-switch m-0">
                    <input class="form-check-input" type="checkbox" role="switch" :checked="!!event.nominations_enabled" aria-label="Nominations"
                        :disabled="busy === 'nominations' || event.ballot_locked" @change="toggleNominations($event.target as HTMLInputElement)" />
                </div>
            </div>
            <div v-if="event.ballot_locked && !event.nominations_enabled" class="opt-body small text-muted">
                <i class="bi bi-lock"></i> Voting has started, so nominations are closed.
            </div>
            <div v-else-if="event.nominations_enabled" class="opt-body">
                <label for="nom-ends" class="form-label">Nominations close</label>
                <div class="d-flex flex-wrap gap-2 align-items-start">
                    <div class="flex-grow-1" style="max-width: 340px">
                        <DateTimePicker id="nom-ends" v-model="nomEnds" label="Nominations close" placeholder="When voting opens"
                            default-time="23:59" />
                    </div>
                    <button class="btn btn-soft" :disabled="busy === 'nomEnds'" @click="saveNomEnds">Save</button>
                </div>
                <div class="link-row mt-3">
                    <span class="mono small text-truncate">{{ nominateUrl }}</span>
                    <button class="btn btn-ghost btn-sm" @click="copyText(nominateUrl, 'Nomination link copied')"><i class="bi bi-copy"></i> Copy</button>
                    <a :href="whatsappLink(null, `Nominate your favourites for ${event.name}: ${nominateUrl}`)" target="_blank" rel="noopener" class="btn btn-wa btn-sm"><i class="bi bi-whatsapp"></i> Share</a>
                </div>
                <button class="btn btn-link btn-sm px-0 mt-2" @click="emit('go', 'nominations')">See the nominations <i class="bi bi-arrow-right"></i></button>
            </div>
        </section>

    </div>
</template>

<script setup lang="ts">
import { adminApi, type EventPayload } from '~/api/endpoints/admin';
import type { BallotEvent } from '~/types';

const props = defineProps<{ event: BallotEvent }>();
const emit = defineEmits<{ changed: []; go: [tab: string] }>();
const toast = useToast();

const busy = ref<string | null>(null);
const nomEnds = ref(toLocalInput(props.event.nominations_ends_at ?? null));
watch(() => props.event, (e) => { nomEnds.value = toLocalInput(e.nominations_ends_at ?? null); });

const { voteUrl } = useEventLinks(() => props.event.link_token);
const nominateUrl = computed(() => voteUrl.value.replace('/vote/', '/nominate/'));

async function patch(key: string, data: EventPayload, success: string) {
    busy.value = key;
    try {
        await adminApi.updateEvent(props.event.id, data);
        toast.success(success);
        emit('changed');
        return true;
    } catch (e) {
        toast.error(apiFieldErrors(e)[Object.keys(data)[0]!] ?? apiErrorMessage(e));
        return false;
    } finally {
        busy.value = null;
    }
}

async function saveLanguage(code: string) {
    const name = LANGUAGES.find(l => l.code === code)?.name ?? code;
    await patch('language', { language: code }, `Voting pages now open in ${name}`);
}

async function toggleNominations(el: HTMLInputElement) {
    const on = el.checked;
    if (!await patch('nominations', { nominations_enabled: on }, on ? 'Nominations are open' : 'Nominations switched off')) el.checked = !on;
}
function saveNomEnds() {
    patch('nomEnds', { nominations_ends_at: nomEnds.value ? fromLocalInput(nomEnds.value) : null }, 'Saved');
}
</script>

<style scoped>
.lang-select { width: auto; min-width: 140px; flex-shrink: 0; align-self: center; }
@media (max-width: 575.98px) { .lang-select { min-width: 0; max-width: 130px; } }
.opt-head { display: flex; gap: 1rem; align-items: flex-start; }
.opt-head .form-switch .form-check-input { width: 2.6em; height: 1.4em; cursor: pointer; }
.opt-body { margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--line); }
.link-row { display: flex; align-items: center; gap: .5rem; padding: .5rem .5rem .5rem .8rem; border: 1px solid var(--line); border-radius: 12px; background: var(--surface-2); }
.link-row .mono { flex: 1; min-width: 0; }
@media (max-width: 575.98px) { .opt-head { flex-wrap: wrap; } }
</style>
