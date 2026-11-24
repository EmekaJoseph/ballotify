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

        <!-- Self-registration -->
        <section v-if="!paid" class="card-b pad">
            <div class="opt-head">
                <span class="icon-tile"><i class="bi bi-person-vcard"></i></span>
                <div class="flex-grow-1 min-w-0">
                    <h3 class="h6 fw-800 mb-1">Voter self-registration <span class="pill pill-pro ms-1"><i class="bi bi-lightning-charge-fill"></i> Pro</span></h3>
                    <p class="small text-muted mb-0">
                        Optional. Voters enter an ID from your list (e.g. membership number) and their code is sent to the
                        email or phone <b>you</b> have on file, so knowing someone’s ID isn’t enough to vote as them.
                        You can still add voters and send codes yourself.
                    </p>
                </div>
                <div class="form-check form-switch m-0">
                    <input id="opt-selfreg" class="form-check-input" type="checkbox" role="switch" :checked="!!event.self_registration"
                        :disabled="busy === 'selfreg'" aria-label="Voter self-registration" @change="toggleSelfReg($event.target as HTMLInputElement)" />
                </div>
            </div>
            <div v-if="event.self_registration" class="opt-body">
                <label for="opt-label" class="form-label">What do you call the ID?</label>
                <div class="d-flex gap-2 flex-wrap">
                    <input id="opt-label" v-model.trim="label" class="form-control" style="max-width: 280px" maxlength="40" placeholder="Membership number" />
                    <button class="btn btn-soft" :disabled="busy === 'label' || label === (event.member_id_label || '')" @click="saveLabel">Save</button>
                </div>
                <div class="d-flex flex-wrap gap-2 mt-2">
                    <button v-for="s in LABELS" :key="s" type="button" class="chip" @click="label = s">{{ s }}</button>
                </div>
                <p class="small text-muted mt-3 mb-0">
                    <i class="bi bi-lightbulb"></i> Add the IDs to your voter list: an <b>ID</b> column when you import, or the ID field when you add a voter.
                    Voters see “Don’t have your code?” on the voting page.
                </p>
            </div>
        </section>

        <!-- USSD -->
        <section v-if="!paid" class="card-b pad">
            <div class="opt-head">
                <span class="icon-tile amber"><i class="bi bi-telephone"></i></span>
                <div class="flex-grow-1 min-w-0">
                    <h3 class="h6 fw-800 mb-1">USSD voting <span class="pill pill-pro ms-1"><i class="bi bi-lightning-charge-fill"></i> Pro</span></h3>
                    <p class="small text-muted mb-0">For voters without a smartphone or data: they dial a short code and vote with their keypad.</p>
                </div>
                <div class="form-check form-switch m-0">
                    <input class="form-check-input" type="checkbox" role="switch" :checked="!!event.ussd_enabled" aria-label="USSD voting"
                        :disabled="busy === 'ussd' || !event.ussd_service_code" @change="toggleUssd($event.target as HTMLInputElement)" />
                </div>
            </div>
            <div v-if="!event.ussd_service_code" class="opt-body small text-muted">
                <i class="bi bi-info-circle"></i> USSD isn’t set up on this platform yet. The platform admin connects an Africa’s Talking USSD code first.
            </div>
            <div v-else-if="event.ussd_enabled" class="opt-body">
                <div class="ussd-steps">
                    <div><span class="step">1</span> Dial <b class="mono">{{ event.ussd_service_code }}</b></div>
                    <div><span class="step">2</span> Event number <b class="mono">{{ event.ussd_code }}</b></div>
                    <div><span class="step">3</span> Their voting code, then pick by number</div>
                </div>
                <p class="small text-muted mt-3 mb-2">
                    New codes for this event are numbers only, because phone keypads can’t type letters. On USSD, voters pick one candidate per category.
                </p>
                <button class="btn btn-ghost btn-sm" :disabled="busy === 'numeric'" @click="numericCodes">
                    <span v-if="busy === 'numeric'" class="spinner-border spinner-border-sm"></span><i v-else class="bi bi-123"></i>
                    Switch existing codes to numbers
                </button>
            </div>
        </section>

        <!-- Meeting rules: weighted votes, proxies, quorum -->
        <section v-if="!paid" class="card-b pad">
            <div class="opt-head">
                <span class="icon-tile"><i class="bi bi-bank"></i></span>
                <div class="flex-grow-1 min-w-0">
                    <h3 class="h6 fw-800 mb-1">Meeting rules <span class="pill pill-pro ms-1"><i class="bi bi-lightning-charge-fill"></i> Pro</span></h3>
                    <p class="small text-muted mb-0">
                        Optional, for AGMs, cooperatives and associations with a constitution. Most elections don’t need them:
                        everything here stays off unless you switch it on.
                    </p>
                </div>
            </div>
            <div v-if="event.ballot_locked" class="opt-body small text-muted pb-0 border-0 mt-2 pt-0">
                <i class="bi bi-lock"></i> Voting has started, so these rules are fixed. Changing them now would change how votes already cast count.
            </div>

            <div class="rule">
                <div class="rule-head">
                    <div class="flex-grow-1 min-w-0">
                        <div class="fw-bold text-ink">Weighted votes</div>
                        <p class="small text-muted mb-0">
                            Each member’s ballot counts by their <b>weight</b>, such as shares, units or delegates.
                            <span class="example">Example: Mrs Ade holds 40 shares, so her one ballot counts as 40 votes.</span>
                        </p>
                    </div>
                    <div class="form-check form-switch m-0">
                        <input class="form-check-input" type="checkbox" role="switch" :checked="!!event.weighted_votes" aria-label="Weighted votes"
                            :disabled="busy === 'weighted' || event.ballot_locked" @change="toggleRule($event.target as HTMLInputElement, 'weighted')" />
                    </div>
                </div>
                <p v-if="event.weighted_votes" class="rule-note">
                    <i class="bi bi-lightbulb"></i> Set each member’s weight on the <b>Voters</b> tab: a <b>Weight</b> column when you import
                    (blank = 1), or the Weight field when you add or edit a voter. Results show votes by weight.
                </p>
            </div>

            <div class="rule">
                <div class="rule-head">
                    <div class="flex-grow-1 min-w-0">
                        <div class="fw-bold text-ink">Proxy voting</div>
                        <p class="small text-muted mb-0">
                            A member who can’t vote names another member to vote for them. The holder’s one ballot counts for both,
                            and the absent member’s own code stops working, so nobody votes twice.
                            <span class="example">Example: Chidi holds proxies for Amaka and Tunde, so his ballot counts as 3.</span>
                        </p>
                    </div>
                    <div class="form-check form-switch m-0">
                        <input class="form-check-input" type="checkbox" role="switch" :checked="!!event.proxies_enabled" aria-label="Proxy voting"
                            :disabled="busy === 'proxies' || event.ballot_locked" @change="toggleRule($event.target as HTMLInputElement, 'proxies')" />
                    </div>
                </div>
                <div v-if="event.proxies_enabled" class="rule-note">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                        <label for="opt-maxp" class="small fw-semibold text-ink mb-0">Most proxies one member may hold</label>
                        <input id="opt-maxp" v-model.number="maxProxies" type="number" min="1" max="50" class="form-control form-control-sm" style="width: 84px" />
                        <button class="btn btn-soft btn-sm" :disabled="busy === 'maxp' || !maxProxies || maxProxies === event.max_proxies" @click="saveMaxProxies">Save</button>
                    </div>
                    <i class="bi bi-lightbulb"></i> Assign proxies on the <b>Voters</b> tab: open a member and choose who votes for them.
                    You can do this until that member votes, even during voting. Every change is in the Activity log.
                </div>
            </div>

            <div class="rule">
                <div class="rule-head">
                    <div class="flex-grow-1 min-w-0">
                        <div class="fw-bold text-ink">Quorum</div>
                        <p class="small text-muted mb-0">
                            The least turnout for the result to count, as your constitution sets it. If it isn’t reached,
                            the results, report and certificate say clearly that the vote isn’t valid.
                            <span class="example">Example: 50% of 120 members means at least 60 must vote.</span>
                        </p>
                    </div>
                    <div class="form-check form-switch m-0">
                        <input class="form-check-input" type="checkbox" role="switch" :checked="!!event.quorum_type" aria-label="Quorum"
                            :disabled="busy === 'quorum' || event.ballot_locked" @change="toggleRule($event.target as HTMLInputElement, 'quorum')" />
                    </div>
                </div>
                <div v-if="event.quorum_type" class="rule-note">
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <span class="small fw-semibold text-ink">At least</span>
                        <input v-model.number="quorumValue" type="number" min="1" :max="quorumType === 'percent' ? 100 : undefined"
                            class="form-control form-control-sm" style="width: 96px" aria-label="Quorum" :disabled="event.ballot_locked" />
                        <AppSelect v-model="quorumType" :options="quorumOptions" :block="false" class="quorum-type" aria-label="Quorum type" :disabled="event.ballot_locked" />
                        <button v-if="!event.ballot_locked" class="btn btn-soft btn-sm" :disabled="busy === 'quorumSave' || !quorumDirty || !quorumValue" @click="saveQuorum">Save</button>
                    </div>
                    <div class="small text-muted mt-2">
                        <i class="bi bi-info-circle"></i>
                        <template v-if="event.weighted_votes">Counted by weight, because weighted votes are on.</template>
                        <template v-else>Members whose vote was cast by proxy count as having voted.</template>
                        The dashboard shows live progress towards the quorum.
                    </div>
                </div>
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

        <!-- Candidate self-service -->
        <section class="card-b pad">
            <div class="opt-head">
                <span class="icon-tile green"><i class="bi bi-person-badge"></i></span>
                <div class="flex-grow-1 min-w-0">
                    <h3 class="h6 fw-800 mb-1">Candidate profiles</h3>
                    <p class="small text-muted mb-0">
                        Optional. Send each candidate a private link to add their own photo and short description.
                        Find it on the <b>Ballot</b> tab: <i class="bi bi-link-45deg"></i> next to each candidate. Every change they make is in the Activity log.
                    </p>
                </div>
            </div>
        </section>
    </div>
</template>

<script setup lang="ts">
import { adminApi, type EventPayload } from '~/api/endpoints/admin';
import type { BallotEvent, QuorumType } from '~/types';

const props = defineProps<{ event: BallotEvent }>();
const emit = defineEmits<{ changed: []; upgrade: []; go: [tab: string] }>();
const toast = useToast();
const { confirm } = useConfirm();

const LABELS = ['Membership number', 'Staff ID', 'Registration number', 'Matric number'];

async function saveLanguage(code: string) {
    const name = LANGUAGES.find(l => l.code === code)?.name ?? code;
    await patch('language', { language: code }, `Voting pages now open in ${name}`);
}
const paid = computed(() => props.event.voting_mode === 'paid');
const busy = ref<string | null>(null);
const label = ref(props.event.member_id_label ?? '');
const nomEnds = ref(toLocalInput(props.event.nominations_ends_at ?? null));
watch(() => props.event, (e) => {
    label.value = e.member_id_label ?? '';
    nomEnds.value = toLocalInput(e.nominations_ends_at ?? null);
});

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
        if (planLimitReason(e)) {
            const ok = await confirm({ title: 'A Pro feature', message: apiErrorMessage(e), confirmText: 'Upgrade this event' });
            if (ok) emit('upgrade');
        } else {
            toast.error(apiFieldErrors(e)[Object.keys(data)[0]!] ?? apiErrorMessage(e));
        }
        return false;
    } finally {
        busy.value = null;
    }
}

/** Switches: if the change is refused, put the switch back. */
async function toggleSelfReg(el: HTMLInputElement) {
    const on = el.checked;
    const ok = await patch('selfreg', { self_registration: on, ...(on && !props.event.member_id_label ? { member_id_label: 'Membership number' } : {}) },
        on ? 'Voters can now request their own code' : 'Self-registration switched off');
    if (!ok) el.checked = !on;
}
function saveLabel() {
    patch('label', { member_id_label: label.value || null }, 'Saved');
}

async function toggleUssd(el: HTMLInputElement) {
    const on = el.checked;
    if (!await patch('ussd', { ussd_enabled: on }, on ? 'USSD voting switched on' : 'USSD voting switched off')) el.checked = !on;
}
async function numericCodes() {
    const ok = await confirm({
        title: 'Switch codes to numbers?',
        message: 'Every voter who hasn’t voted gets a new numbers-only code, and their old code stops working. Send the new codes afterwards.',
        confirmText: 'Switch codes',
    });
    if (!ok) return;
    busy.value = 'numeric';
    try {
        const { data } = await adminApi.numericCodes(props.event.id);
        toast.success(data.replaced ? `${data.replaced} code${data.replaced === 1 ? '' : 's'} switched to numbers. Send them again from the Voters tab.` : 'All codes are already numbers.');
        emit('changed');
    } catch (e) {
        toast.error(apiErrorMessage(e));
    } finally {
        busy.value = null;
    }
}

async function toggleNominations(el: HTMLInputElement) {
    const on = el.checked;
    if (!await patch('nominations', { nominations_enabled: on }, on ? 'Nominations are open' : 'Nominations switched off')) el.checked = !on;
}
// ------------------------------------------------------------------ meeting rules
const maxProxies = ref(props.event.max_proxies ?? 2);
const quorumType = ref<QuorumType>(props.event.quorum_type ?? 'percent');
const quorumValue = ref<number | null>(props.event.quorum_value ?? 50);
watch(() => props.event, (e) => {
    maxProxies.value = e.max_proxies ?? 2;
    quorumType.value = e.quorum_type ?? 'percent';
    quorumValue.value = e.quorum_value ?? 50;
});
const quorumOptions = computed(() => [
    { value: 'percent', label: props.event.weighted_votes ? '% of all votes (weight)' : '% of members' },
    { value: 'count', label: props.event.weighted_votes ? 'votes (by weight)' : 'members' },
]);
const quorumDirty = computed(() => quorumType.value !== props.event.quorum_type || quorumValue.value !== props.event.quorum_value);

async function toggleRule(el: HTMLInputElement, rule: 'weighted' | 'proxies' | 'quorum') {
    const on = el.checked;
    const [data, msg]: [EventPayload, string] = rule === 'weighted'
        ? [{ weighted_votes: on }, on ? 'Weighted votes on. Set weights on the Voters tab.' : 'Weighted votes off: every ballot counts as 1']
        : rule === 'proxies'
            ? [{ proxies_enabled: on }, on ? 'Proxy voting on. Assign proxies on the Voters tab.' : 'Proxy voting off']
            : [on ? { quorum_type: 'percent', quorum_value: 50 } : { quorum_type: null }, on ? 'Quorum set to 50% of members. Change it below.' : 'Quorum removed'];
    if (!on && rule === 'proxies') {
        const ok = await confirm({ title: 'Switch off proxy voting?', message: 'Any proxies you’ve assigned are cancelled, and those members vote for themselves.', confirmText: 'Switch off' });
        if (!ok) { el.checked = true; return; }
    }
    if (!await patch(rule, data, msg)) el.checked = !on;
}
function saveMaxProxies() {
    patch('maxp', { max_proxies: maxProxies.value }, `A member may now hold up to ${maxProxies.value} prox${maxProxies.value === 1 ? 'y' : 'ies'}`);
}
function saveQuorum() {
    if (quorumType.value === 'percent' && (quorumValue.value ?? 0) > 100) {
        toast.error('A percentage can’t be more than 100.');
        return;
    }
    patch('quorumSave', { quorum_type: quorumType.value, quorum_value: quorumValue.value }, 'Quorum saved');
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
.chip { border: 1px solid var(--line); background: var(--surface-2); border-radius: 999px; padding: .2rem .7rem; font-size: .8rem; font-weight: 600; color: var(--ink-2); }
.chip:hover { border-color: var(--brand-500); color: var(--brand-700); }
.ussd-steps { display: grid; gap: .5rem; }
.ussd-steps .step { display: inline-grid; place-items: center; width: 22px; height: 22px; border-radius: 7px; background: var(--brand-50); color: var(--brand-700); font-weight: 800; font-size: .75rem; margin-right: .4rem; }
.link-row { display: flex; align-items: center; gap: .5rem; padding: .5rem .5rem .5rem .8rem; border: 1px solid var(--line); border-radius: 12px; background: var(--surface-2); }
.link-row .mono { flex: 1; min-width: 0; }
@media (max-width: 575.98px) { .opt-head { flex-wrap: wrap; } }
.rule { margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--line); }
.rule-head { display: flex; gap: 1rem; align-items: flex-start; }
.rule-head .form-switch .form-check-input { width: 2.6em; height: 1.4em; cursor: pointer; }
.rule .example { display: block; margin-top: .25rem; color: var(--ink-2); font-style: italic; }
.rule-note { margin: .75rem 0 0; padding: .7rem .85rem; border-radius: 12px; background: var(--surface-2); font-size: .85rem; color: var(--ink-2); overflow-wrap: anywhere; }
.quorum-type { width: auto; min-width: 170px; }
@media (max-width: 575.98px) { .quorum-type { min-width: 0; flex: 1 1 140px; } }
</style>
