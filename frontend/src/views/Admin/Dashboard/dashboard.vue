<template>
    <div>
        <div class="fw-bold mb-2">Overview:</div>
        <div class="row justify-content-center gy-3">
            <infoCard :name="spell('Member', iRates.Member)" icon="bi-people" color="#BD6C13" :rate="iRates.Member" />
            <infoCard :name="spell('Group', iRates.Group)" icon="bi-folder-plus" color="#18B4F0" :rate="iRates.Group" />
            <infoCard :name="'Voting  ' + spell('Event', iRates.Event)" icon="bi-list-check" color="#F01844"
                :rate="iRates.Event" />
            <infoCard :name="spell('Message', iRates.Message)" icon="bi-app-indicator" color="#09BD27"
                :rate="iRates.Message" />
        </div>

        <div class="mt-5">
            <div v-if="recentsTable.length" class="fw-bold mb-2">Recently Added:</div>
            <recents :data="recentsTable" />
        </div>
    </div>
</template>

<script setup lang="ts">
import { onMounted, ref, inject } from 'vue'
import server from '@/store/apiStore.js'
import useFunc from '@/store/useFunction'
import infoCard from './infoCardComponent.vue'
import recents from './recentsComponent.vue'
const { cc1, cc2, ccThk, ccBg, ccBtnH }: any = inject("c$");


const recentsTable = ref([])
const iRates = ref({
    Member: 0,
    Group: 0,
    Event: 0,
    Message: 0
})

const spell = useFunc.x.spell



</script>

<style scoped>
</style>