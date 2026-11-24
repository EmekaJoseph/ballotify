<template>
    <div v-if="plans?.billing_enabled" class="card-b pad">
        <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
            <div class="d-flex gap-3 align-items-center">
                <span class="icon-tile pro"><i class="bi bi-chat-dots-fill"></i></span>
                <div>
                    <h3 class="h6 fw-800 mb-0">SMS credits</h3>
                    <div class="small text-muted">For SMS beyond what each Pro event includes (1 per voter).</div>
                </div>
            </div>
            <div class="text-end">
                <div class="balance">{{ formatNumber(auth.user?.sms_credits ?? 0) }}</div>
                <div class="small text-muted">credits left</div>
            </div>
        </div>
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3">
            <span class="small text-muted">{{ formatNaira(plans.sms_price_naira) }} per SMS · they don’t expire · email and WhatsApp are free</span>
            <button class="btn btn-primary btn-sm" @click="openBuySms()"><i class="bi bi-cart-plus"></i> Buy SMS credits</button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { useAuthStore } from '~/stores/auth';

const auth = useAuthStore();
const { plans, loadPlans, openBuySms } = useBilling();
onMounted(() => loadPlans());
</script>

<style scoped>
.balance { font-size: 1.6rem; font-weight: 800; color: var(--ink); line-height: 1; }
</style>
