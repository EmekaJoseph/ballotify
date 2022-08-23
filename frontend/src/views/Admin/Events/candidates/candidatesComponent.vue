<template>
    <div class="card px-2">
        <div class="card-head"><i class="bi bi-person-check-fill"></i> Include candidates:</div>
        <div class="card-body">
            <div class="row justify-content-center gy-3">
                <div class="col-12 col-xl-7">
                    <v-select placeholder="select candidate.." :clearable="false" class="vSelect"
                        :options="data.members" />
                </div>
                <div class="col-12 col-xl-5">
                    <v-select placeholder="select position.." class="vSelect" :options="data.posts"
                        :disabled="!(data.posts.length)" />
                </div>
                <div class="col-12 col-xl-8">
                    <input type="text" class="form-control">
                </div>
                <div class="col-12 col-xl-4">
                    <button class="w-100 btn customBtn">Include <i
                            class="bi text-white bi-arrow-down-circle fs-6"></i></button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { reactive, computed } from 'vue';
import { dataStore } from '@/store/admin/dataStore';
import { eventStore } from '../event-data';
import { storeToRefs } from 'pinia'



const mStore = dataStore()
const { members }: any = storeToRefs(mStore)
const event_ = eventStore()
const { positions }: any = storeToRefs(event_)

const data: any = reactive({
    members: computed(() => {
        let data = members.value.map((x: any) => ({ id: x.id, label: x.firstname + ' ' + x.lastname + ', (' + x.gender + ')' }))
        return data
    }),
    posts: computed(() => {
        let data = positions.value.map((x: any) => ({ id: x.id, label: x.name }))
        return data
    })
})

</script>

<style scoped>
.card-head {
    padding: 20px 15px;
    background-color: #fff;
    border-bottom: 1px solid #eee;
}
</style>