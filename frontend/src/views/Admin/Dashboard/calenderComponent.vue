<template>
    <div class="col-xl-4">
        <div class="mb-2">&nbsp;</div>
        <div class="card">
            <div class="card-header">Calender</div>
            <div class="card-body">
                <div class="row justify-content-center">
                    <!-- <v-date-picker v-model="date" /> -->
                    <v-calendar :attributes="attributes" @dayclick="onDayClick" is-expanded class="border-0" />
                    <div class="text-center fw-bold">Today:</div>
                    <div class="text-center">{{ dateFormat }}</div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref, inject, computed } from 'vue'
import { useNow, useDateFormat } from '@vueuse/core'
const { cc1, cc2, ccThk, ccBg, ccBtnH } = inject("c$");

const days = ref([new Date('2022-06-01'), new Date('2022-06-24'), new Date()])
const attributes = computed(() => {
    return days.value.map(date => ({
        highlight: {
            color: 'orange',
            fillMode: 'solid',
        },
        dates: date,
    }));
})


const dateFormat = useDateFormat(useNow(), 'DD-MM-YYYY')
function onDayClick(day) {
}

</script>

<style scoped>
</style>