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

<script setup lang="ts">
import { computed } from 'vue'
import { useNow, useDateFormat } from '@vueuse/core'

const prop = defineProps({
    days: {
        type: Array,
        default: []
    }
})

const attributes = computed(() => {
    return prop.days.map(date => ({
        highlight: {
            color: 'orange',
            fillMode: 'solid',
        },
        dates: date,
    }));

})


const dateFormat = useDateFormat(useNow(), 'DD-MM-YYYY')
function onDayClick(day: Date) {
    console.log(day);
}

</script>

<style scoped>
</style>