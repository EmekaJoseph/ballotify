<template>
    <div>
        <div v-html="greet()" class="greeting d-md-none"> </div>
        <div class="fw-bold mb-2">Overview:</div>
        <div class="row justify-content-center gy-3">
            <InfoCard :name="spell('Member', iRates.Member)" icon="bi-people" color="#BD6C13" :rate="iRates.Member" />
            <InfoCard :name="spell('Group', iRates.Group)" icon="bi-folder-plus" color="#18B4F0" :rate="iRates.Group" />
            <InfoCard :name="'Voting  ' + spell('Event', iRates.Event)" icon="bi-list-check" color="#F01844"
                :rate="iRates.Event" />
            <InfoCard :name="spell('Message', iRates.Message)" icon="bi-app-indicator" color="#2c3e50"
                :rate="iRates.Message" />
        </div>

        <div class="mt-4">
            <div class="row justify-content-center gy-3">
                <Recents :data="recentsTable" />
                <Calender :days="birthdaysFormatted()" />
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { onMounted, ref, reactive } from 'vue'
import server from '@/store/apiStore'
import useFunc from '@/store/useFunction'

import InfoCard from './infoCardComponent.vue'
import Recents from './recentsComponent.vue'
import Calender from './calenderComponent.vue'

import { dataStore } from '@/store/admin/dataStore';

const mStore = dataStore()

const spell = useFunc.fx.spell


const recentsTable = ref([])
const birthdays = ref([])
const iRates = reactive({
    Member: 0,
    Group: 0,
    Event: 0,
    Message: 0
})

const birthdaysFormatted: any = () => {
    let bds = birthdays.value
    let newArr: any[] = []
    bds.forEach((x: string) => {
        let appended: any = x + '-' + (new Date()).getFullYear()
        newArr.push(new Date(appended));
    })
    return newArr
}

onMounted(async () => {
    try {
        var { data } = await server.getOverview()
        if (data) {
            iRates.Member = data.members
            iRates.Group = data.groups
            iRates.Event = data.events
            iRates.Message = data.messages
            birthdays.value = data.birthdays
        }
        mStore.internetError = false
    } catch (error) {
        mStore.internetError = true
        console.log(error);
    }
})


const greet = () => {
    let now: Date = new Date();
    let curHr = now.getHours();
    let str: string = '';
    if (curHr < 12) str = 'Good morning&nbsp;<i class="bi bi-emoji-sunglasses"></i>';
    else if (curHr < 16) str = 'Good afternoon&nbsp;<i class="bi bi-sun"></i>';
    else str = 'Good evening&nbsp;<i class="bi bi-moon"></i>';
    return str
}


</script>

<style scoped>
.greeting {
    display: flex;
    justify-content: end;
    padding: 15px;
    color: #d6d3d3;
    font-size: 17px;
    font-weight: 600;
}

@media (max-width: 991px) {
    .greeting {
        padding: 7px;
        font-size: 15px;
    }
}
</style>