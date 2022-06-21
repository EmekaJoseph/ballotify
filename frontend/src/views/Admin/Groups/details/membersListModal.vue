<template>
    <div class="modal fade" id="mListModal" data-backdrop="static" data-keyboard="false" tabindex="-1"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header"> Add Members
                    <span class="float-end">
                        <button ref="btnX" class="btn btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </span>
                </div>
                <div class="modal-body p-sm-4">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th class="smallCol"><input @change="toggleAll" v-model="allCheck"
                                            class="form-check-input" type="checkbox"></th>
                                    <th>Name</th>
                                    <th>Group</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(v, i) in memData">
                                    <td><input v-model="v.checked" class="form-check-input" type="checkbox"></td>
                                    <td>{{ v.firstname }} {{ v.lastname }}, {{ v.gender }}</td>
                                    <td>{{ fGroupName(v.group_id) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { dataStore } from '@/store/dataStore'
import { storeToRefs } from 'pinia'
import { ref, onMounted, watch } from 'vue';
import { useAdminStore } from '@/store/user/admin'

const orgId = useAdminStore().getData.org_id

const mStore = dataStore()
const { members, groups }: any = storeToRefs(mStore)

// interface Props {
//     data: string,
//     mCalled: boolean
// }
// const prop = defineProps<Props>()

onMounted(async () => {
    // console.log(prop.mCalled);
    populate()
})

// watch([prop.mCalled], () => {
//     console.log(prop.mCalled);

//     populate()
// })

const memData: any = ref([])

async function populate() {
    await mStore.getMembers(orgId)
    await mStore.getGroupNames(orgId)
    let tableGroup = members.value
    tableGroup.forEach((x: { checked: boolean }) => {
        x.checked = false
    });
    memData.value = tableGroup
    console.log(tableGroup);

}

const allCheck = ref(false)
function toggleAll() {
    memData.value.forEach(chk => {
        chk.checked = allCheck.value
    });
}


const fGroupName = (id: string) => {
    let grpObj = groups.value.find((x: { id: any; }) => x.id == id)
    return grpObj == undefined ? 'None' : grpObj.name
}




</script>

<style scoped>
</style>