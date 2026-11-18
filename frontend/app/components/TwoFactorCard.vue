<template>
    <div class="card-b pad">
        <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
            <div>
                <h3 class="h6 fw-800 mb-1">Two-step login</h3>
                <p class="small text-muted mb-0">Ask for a code from an authenticator app (Google Authenticator, Microsoft Authenticator, Authy…) when signing in.</p>
            </div>
            <span v-if="enabled" class="pill pill-active flex-shrink-0"><i class="bi bi-shield-check"></i> On</span>
            <span v-else class="pill pill-completed flex-shrink-0">Off</span>
        </div>

        <!-- Recovery codes (shown once, right after enabling) -->
        <div v-if="recoveryCodes.length" class="recovery mb-3">
            <div class="fw-bold text-ink mb-1"><i class="bi bi-key"></i> Save your recovery codes</div>
            <p class="small text-muted mb-2">Each code works once if you lose your phone. They won’t be shown again.</p>
            <div class="codes mono mb-2">
                <span v-for="c in recoveryCodes" :key="c">{{ c }}</span>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <button class="btn btn-ghost btn-sm" @click="copyText(recoveryCodes.join('\n'), 'Recovery codes copied')"><i class="bi bi-copy"></i> Copy</button>
                <button class="btn btn-ghost btn-sm" @click="downloadCodes"><i class="bi bi-download"></i> Download</button>
                <button class="btn btn-primary btn-sm ms-auto" @click="recoveryCodes = []">I’ve saved them</button>
            </div>
        </div>

        <!-- Step: confirm password before setup / disable -->
        <form v-else-if="step === 'password'" class="row g-2 align-items-end" novalidate @submit.prevent="enabled ? disable() : startSetup()">
            <div class="col-sm">
                <label for="tfa-pw" class="form-label">Confirm your password</label>
                <input id="tfa-pw" v-model="password" type="password" class="form-control" autocomplete="current-password" :disabled="busy" />
            </div>
            <div class="col-sm-auto d-flex gap-2">
                <button type="button" class="btn btn-ghost" @click="reset">Cancel</button>
                <button type="submit" class="btn" :class="enabled ? 'btn-danger' : 'btn-primary'" :disabled="busy || !password">
                    <span v-if="busy" class="spinner-border spinner-border-sm"></span> {{ enabled ? 'Turn off' : 'Continue' }}
                </button>
            </div>
            <div v-if="error" class="field-error col-12">{{ error }}</div>
        </form>

        <!-- Step: scan + confirm code -->
        <div v-else-if="step === 'scan'" class="row g-4 align-items-center">
            <div class="col-sm-auto text-center">
                <div class="qr"><img v-if="qr" :src="qr" alt="QR code for your authenticator app" width="180" height="180" /></div>
            </div>
            <div class="col-sm">
                <ol class="small text-muted ps-3 mb-3">
                    <li>Open your authenticator app and scan this QR code.</li>
                    <li>Can’t scan? Enter this key: <span class="code-chip">{{ secret }}</span></li>
                    <li>Type the 6-digit code it shows.</li>
                </ol>
                <form class="d-flex gap-2" novalidate @submit.prevent="confirm">
                    <input v-model.trim="code" class="form-control mono text-center" inputmode="numeric" autocomplete="one-time-code"
                        maxlength="6" placeholder="000000" style="max-width: 150px; letter-spacing: .3em" :disabled="busy" />
                    <button class="btn btn-primary" :disabled="busy || code.length !== 6">
                        <span v-if="busy" class="spinner-border spinner-border-sm"></span> Turn on
                    </button>
                    <button type="button" class="btn btn-ghost" @click="reset">Cancel</button>
                </form>
                <div v-if="error" class="field-error">{{ error }}</div>
            </div>
        </div>

        <button v-else class="btn" :class="enabled ? 'btn-ghost' : 'btn-primary'" @click="step = 'password'">
            <i class="bi" :class="enabled ? 'bi-shield-x' : 'bi-shield-lock'"></i> {{ enabled ? 'Turn off two-step login' : 'Set up two-step login' }}
        </button>
    </div>
</template>

<script setup lang="ts">
import QRCode from 'qrcode';
import { adminApi } from '~/api/endpoints/admin';
import { useAuthStore } from '~/stores/auth';

const auth = useAuthStore();
const toast = useToast();
const enabled = computed(() => !!auth.user?.two_factor_enabled);

const step = ref<'idle' | 'password' | 'scan'>('idle');
const password = ref('');
const secret = ref('');
const qr = ref('');
const code = ref('');
const busy = ref(false);
const error = ref('');
const recoveryCodes = ref<string[]>([]);

function reset() {
    step.value = 'idle';
    password.value = '';
    code.value = '';
    secret.value = '';
    qr.value = '';
    error.value = '';
}

async function startSetup() {
    busy.value = true;
    error.value = '';
    try {
        const { data } = await adminApi.twoFactorSetup(password.value);
        secret.value = data.secret;
        qr.value = await QRCode.toDataURL(data.otpauth_url, { width: 360, margin: 1 });
        password.value = '';
        step.value = 'scan';
    } catch (e) {
        error.value = apiErrorMessage(e);
    } finally {
        busy.value = false;
    }
}

async function confirm() {
    busy.value = true;
    error.value = '';
    try {
        const { data } = await adminApi.twoFactorConfirm(code.value);
        auth.user = data.user;
        recoveryCodes.value = data.recovery_codes;
        reset();
        toast.success('Two-step login is on');
    } catch (e) {
        error.value = apiErrorMessage(e);
        code.value = '';
    } finally {
        busy.value = false;
    }
}

async function disable() {
    busy.value = true;
    error.value = '';
    try {
        const { data } = await adminApi.twoFactorDisable(password.value);
        auth.user = data.user;
        reset();
        toast.success('Two-step login is off');
    } catch (e) {
        error.value = apiErrorMessage(e);
    } finally {
        busy.value = false;
    }
}

function downloadCodes() {
    downloadFile('ballotify-recovery-codes.txt', `Ballotify recovery codes for ${auth.user?.email}\nEach code works once.\n\n${recoveryCodes.value.join('\n')}\n`, 'text/plain;charset=utf-8');
}
</script>

<style scoped>
.qr { width: 196px; padding: 8px; border-radius: 14px; background: #fff; border: 1px solid var(--line); margin: 0 auto; }
.qr img { width: 100%; height: auto; display: block; }
.recovery { padding: 1rem; border-radius: var(--radius); background: var(--warning-50); border: 1px solid var(--line); }
.codes { display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: .35rem .75rem; font-weight: 600; color: var(--ink); }
</style>
