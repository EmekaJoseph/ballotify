<template>
    <div class="modal fade" id="mListModal" data-backdrop="static" data-keyboard="false" tabindex="-1"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header"> Add to group ({{ fGroupName(currentGroup) }})
                    <span class="float-end">
                        <button ref="btnX" class="btn btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </span>
                </div>
                <div v-if="mems.length" class="modal-body p-sm-4">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th class="smallCol"></th>
                                    <th>Name</th>
                                    <th>Group</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(v, i) in mems">
                                    <td><input v-model="v.checked" class="form-check-input" type="checkbox"></td>
                                    <td>{{ v.firstname }} {{ v.lastname }}, {{ v.gender }}</td>
                                    <td>{{ fGroupName(v.group_id) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div v-if="mems.length" class="modal-footer border-0">
                    <button @click="addToGroup()" v-if="isChecked.some" type="button" class=" customBtn btn-sm">Add
                        selection</button>
                    <button @click="addToGroup('all')" type="button" class="btn btn-outline-dark btn-sm">Add
                        all</button>
                </div>

                <div v-else class="modal-body text-center" style="padding: 50px;">
                    No record to add
                </div>

            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { dataStore } from '@/store/dataStore'
import { storeToRefs } from 'pinia'
import { ref, computed, reactive } from 'vue';

const emit = defineEmits(['add',])


const mStore = dataStore()
const { groups, groupAddArray: mems, currentGroup }: any = storeToRefs(mStore)

const isChecked = reactive({
    some: computed(() => { return mems.value.some((x: { checked: boolean; }) => x.checked == true) ? true : false })
})

const fGroupName = (id: string) => {
    let grpObj = groups.value.find((x: { id: any; }) => x.id == id)
    return grpObj == undefined ? 'None' : grpObj.name
}

const btnX = ref<any>(null)
function addToGroup(w?: string) {
    let arr = mems.value
    if (w != 'all') {
        arr = mems.value.filter((x: any) => x.checked == true)
    }
    arr = arr.map((x) => ({ id: x.id, group_id: currentGroup.value }))
    emit('add', arr)
    btnX.value.click()
}




</script>

<style scoped>
</style>