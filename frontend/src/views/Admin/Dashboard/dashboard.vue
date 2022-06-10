<template>
    <div>
        <div class="fw-bold mb-2">Overview:</div>
        <div class="row justify-content-center gy-3">
            <infoCard :name="spell('Member', iRates.Member)" icon="bi-people" color="#BD6C13" :rate="iRates.Member" />
            <infoCard :name="spell('Group', iRates.Group)" icon="bi-folder-plus" color="#18B4F0" :rate="iRates.Group" />
            <infoCard :name="'Voting  ' + spell('Event', iRates.Event)" icon="bi-list-check" color="#F01844"
                :rate="iRates.Event" />
            <infoCard :name="spell('Message', iRates.Message)" icon="bi-app-indicator" color="#2c3e50"
                :rate="iRates.Message" />
        </div>

        <div class="mt-4">
            <div class="row justify-content-center gy-3">
                <recents :data="recentsTable" />
                <calender :days="birthdaysFormatted()" />
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { onMounted, ref, reactive } from 'vue'
import server from '@/store/apiStore'
import useFunc from '@/store/useFunction'
import { useAdminStore } from '@/store/user/admin'

import infoCard from './infoCardComponent.vue'
import recents from './recentsComponent.vue'
import calender from './calenderComponent.vue'

const spell = useFunc.fx.spell

const orgId = useAdminStore().getData.org_id

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
    let newArr = <any>[]
    bds.forEach((x: string) => {
        let appended = x + '-' + (new Date()).getFullYear()
        newArr.push(new Date(appended));
    })
    return newArr
}



onMounted(async () => {
    try {
        var { data } = await server.getOverview(orgId)
        if (data) {
            iRates.Member = data.members
            iRates.Group = data.groups
            birthdays.value = data.birthdays
        }
    } catch (error) {
        console.log(error);
    }
})


</script>

<style scoped>
</style>