<template>
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card-b overflow-hidden">
                <div class="card-header-b">
                    <div>
                        <h3 class="h6 fw-800 mb-0">Team</h3>
                        <div class="small text-muted">Your election committee: people who help run this event.</div>
                    </div>
                </div>

                <div v-if="loading" class="p-4 d-grid gap-2"><div v-for="i in 3" :key="i" class="skeleton" style="height: 44px"></div></div>
                <ul v-else class="list-unstyled mb-0">
                    <li class="member">
                        <span class="avatar sm" :style="{ background: colorFor(team?.owner.email) }">{{ initials(team?.owner.name) }}</span>
                        <div class="min-w-0 flex-grow-1">
                            <div class="fw-semibold text-ink text-truncate">{{ team?.owner.name }}</div>
                            <div class="small text-muted text-truncate">{{ team?.owner.email }}</div>
                        </div>
                        <span class="pill pill-brand"><i class="bi bi-star"></i> Owner</span>
                    </li>
                    <li v-for="m in team?.members ?? []" :key="m.id" class="member">
                        <span class="avatar sm" :style="{ background: colorFor(m.email) }">{{ initials(m.name || m.email) }}</span>
                        <div class="min-w-0 flex-grow-1">
                            <div class="fw-semibold text-ink text-truncate">{{ m.name || m.email }}</div>
                            <div class="small text-muted text-truncate">
                                <template v-if="m.name">{{ m.email }} · </template>
                                <span v-if="!m.joined" class="text-warning-emphasis"><i class="bi bi-hourglass-split"></i> Invited, not joined yet</span>
                                <span v-else>Joined</span>
                            </div>
                        </div>
                        <AppSelect v-if="isOwner" :model-value="m.role" :options="roleOptions" size="sm" :block="false" :menu-min-width="260"
                            class="role-select" :aria-label="`Role for ${m.email}`" @change="changeRole(m, $event as MemberRole)" />
                        <span v-else class="pill pill-completed">{{ ROLE_INFO[m.role].label }}</span>
                        <button v-if="isOwner || m.email === myEmail" class="btn btn-ghost btn-icon btn-sm text-danger"
                            :title="m.email === myEmail ? 'Leave this event' : 'Remove from team'" :aria-label="m.email === myEmail ? 'Leave this event' : 'Remove from team'"
                            @click="remove(m)"><i class="bi" :class="m.email === myEmail ? 'bi-box-arrow-right' : 'bi-x-lg'"></i></button>
                    </li>
                </ul>
                <div v-if="!loading && !team?.members.length" class="px-4 pb-4 small text-muted">No co-organisers yet.</div>
            </div>
        </div>

        <div class="col-lg-4 d-grid gap-4 align-content-start">
            <div v-if="isOwner" class="card-b pad">
                <h3 class="h6 fw-800 mb-1">Invite someone</h3>
                <p class="small text-muted mb-3">They get an email. If they don’t have an account yet, they create one with that address.</p>
                <form novalidate @submit.prevent="invite">
                    <label for="tm-email" class="form-label">Email</label>
                    <input id="tm-email" v-model.trim="email" type="email" class="form-control mb-3" placeholder="name@example.com" :disabled="busy" />
                    <label class="form-label">Role</label>
                    <div class="d-grid gap-2 mb-3">
                        <label v-for="r in roles" :key="r" class="choice" :class="{ selected: role === r }">
                            <input v-model="role" type="radio" class="visually-hidden" :value="r" />
                            <span class="icon-tile sm"><i class="bi" :class="ROLE_INFO[r].icon"></i></span>
                            <span><span class="d-block fw-bold text-ink">{{ ROLE_INFO[r].label }}</span><span class="d-block small text-muted">{{ ROLE_INFO[r].text }}</span></span>
                        </label>
                    </div>
                    <div v-if="error" class="field-error mb-2">{{ error }}</div>
                    <button class="btn btn-primary w-100" :disabled="busy || !/^\S+@\S+\.\S+$/.test(email)">
                        <span v-if="busy" class="spinner-border spinner-border-sm"></span><i v-else class="bi bi-person-plus"></i> Add to team
                    </button>
                </form>
            </div>
            <div class="card-b pad small text-muted">
                <h3 class="h6 fw-800 text-ink mb-2">Who can do what</h3>
                <ul class="list-unstyled d-grid gap-2 mb-0">
                    <li v-for="(info, key) in ROLE_INFO" :key="key"><b class="text-ink">{{ info.label }}:</b> {{ info.text }}</li>
                </ul>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { adminApi } from '~/api/endpoints/admin';
import { useAuthStore } from '~/stores/auth';
import type { BallotEvent, MemberRole, TeamInfo, TeamMember } from '~/types';

const props = defineProps<{ event: BallotEvent }>();
const emit = defineEmits<{ left: [] }>();
const toast = useToast();
const { confirm } = useConfirm();
const auth = useAuthStore();

const roles: MemberRole[] = ['editor', 'voters', 'viewer'];
const roleOptions = roles.map(r => ({ value: r as string, label: ROLE_INFO[r].label, hint: ROLE_INFO[r].text, icon: ROLE_INFO[r].icon }));
const team = ref<TeamInfo | null>(null);
const loading = ref(true);
const busy = ref(false);
const email = ref('');
const role = ref<MemberRole>('editor');
const error = ref('');

const isOwner = computed(() => props.event.my_role === 'owner');
const myEmail = computed(() => auth.user?.email?.toLowerCase());

async function load() {
    try { team.value = (await adminApi.getTeam(props.event.id)).data; }
    catch (e) { toast.error(apiErrorMessage(e)); }
    finally { loading.value = false; }
}
onMounted(load);

async function invite() {
    busy.value = true;
    error.value = '';
    try {
        const { data } = await adminApi.addMember(props.event.id, { email: email.value, role: role.value });
        toast.success(data.joined ? `${data.email} added to the team` : `Invitation sent to ${data.email}`);
        email.value = '';
        await load();
    } catch (e) {
        error.value = apiFieldErrors(e).email ?? apiErrorMessage(e);
    } finally {
        busy.value = false;
    }
}

async function changeRole(m: TeamMember, r: MemberRole) {
    try {
        await adminApi.updateMember(props.event.id, m.id, r);
        toast.success(`${m.name || m.email} is now ${ROLE_INFO[r].label.toLowerCase()}`);
        await load();
    } catch (e) {
        toast.error(apiErrorMessage(e));
    }
}

async function remove(m: TeamMember) {
    const leaving = m.email === myEmail.value;
    const ok = await confirm({
        title: leaving ? 'Leave this event?' : `Remove ${m.name || m.email}?`,
        message: leaving ? 'You will lose access to it.' : 'They will lose access to this event.',
        confirmText: leaving ? 'Leave' : 'Remove',
        danger: true,
    });
    if (!ok) return;
    try {
        await adminApi.removeMember(props.event.id, m.id);
        if (leaving) { emit('left'); return; }
        toast.success('Removed from the team');
        await load();
    } catch (e) {
        toast.error(apiErrorMessage(e));
    }
}
</script>

<style scoped>
.member { display: flex; align-items: center; gap: .8rem; padding: .8rem 1.25rem; border-bottom: 1px solid var(--line); flex-wrap: wrap; }
.member:last-child { border-bottom: 0; }
.role-select { flex-shrink: 0; }
.icon-tile.sm { width: 34px; height: 34px; font-size: 1rem; flex-shrink: 0; }
</style>
