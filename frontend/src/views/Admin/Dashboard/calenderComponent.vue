<template>
    <div class="col-xl-4">
        <div class="mb-2">&nbsp;</div>
        <div class="card">
            <div class="card-header">Calender</div>
            <div class="card-body">
                <div class="row justify-content-center">
                    <!-- <v-calendar class="border-0" /> -->
                    <v-date-picker class="border-0 w-100" v-model="date" />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref, inject, watch, watchEffect } from 'vue'
const { cc1, cc2, ccThk, ccBg, ccBtnH } = inject("c$");
const date = ref(new Date())

watch(date, (newDate) => { console.log('watch:', newDate); })

watchEffect(() => console.log('watcheffect:', date.value));
</script>

<style scoped>
</style>