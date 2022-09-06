<template>
    <div>
        <div class="card">
            <h5 class="px-3 pt-3 ">
                <div class="d-none d-md-inline ">
                    <button @click="router.go(-1)" class="btn m-0 fw-bold  btn-link">
                        <i class="bi bi-chevron-left"></i> back
                    </button>
                </div>
                <div class="float-end me-2">
                    <span class="text-capitalize"><i class="bi bi-folder"></i> {{  group.name  }}</span>
                    <small class="created">created {{  group.created  }} </small>
                </div>

            </h5>

            <div class="card-body">
                <div class="row gy-3">
                    <tableComp @remove="confirmRemove" />
                    <settingComp :name="group.name" :name_bk="group.name" @delete="deleteGroup" @rename="renameGroup" />
                    <addMembersModal @add="confirmAdd" />
                </div>

            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import tableComp from './groupTableComponent.vue'
import settingComp from './groupSettingsComponent.vue'
import addMembersModal from './membersToGroupModal.vue';
import { useRouter } from 'vue-router'
import server from '@/store/apiStore'
import { onMounted, } from 'vue';
import Swal from 'sweetalert2'
import { dataStore } from '@/store/admin/dataStore';
import { groudDetailStore } from './groupDetailStore';
import { storeToRefs } from 'pinia'


const mStore = dataStore()
const { members, groups }: any = storeToRefs(mStore)
const groupStore = groudDetailStore()
const { group }: any = storeToRefs(groupStore)



const router = useRouter()

onMounted(() => {
    groupStore.$reset
    updateData()
})


async function updateData() {

    await mStore.getMembers()
    await mStore.getGroupNames()
    groupStore.setData(groups.value, members.value)
}



async function deleteGroup() {
    let hasMembers = members.value.some((x: { group_id: string }) => x.group_id == group.value.id)
    if (hasMembers) {
        Swal.fire({
            toast: true,
            icon: 'error',
            title: 'Group must be empty',
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: false,
        })
        return false
    }
    else {
        try {
            var { data } = await server.deleteGroup(group.value.id);
            if (data == 1) {
                Swal.fire({
                    toast: true,
                    icon: 'success',
                    title: `'${group.value.name}' deleted`,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: false,
                })
                // unwatch()
                await mStore.getGroupNames()
                router.push({ name: 'Groups' })
            }
            else console.log(data);

        } catch (error) {
            console.log(error);
        }
    }
}

async function renameGroup(name: string) {
    if (group.value.name.toLowerCase() == name.toLowerCase()) {
        return
    }
    let obj = {
        id: group.value.id,
        group_name: name
    }
    try {
        var { data } = await server.renameGroup(obj);
        if (data == 0) {
            Swal.fire({
                title: '',
                text: `'${name}' already exists`,
                icon: 'error',
                showConfirmButton: false,
                timer: 1200,
                width: 350,
                padding: '3em',
            })
            return
        }
        else {
            Swal.fire({
                toast: true,
                icon: 'success',
                title: `Renamed to '${name}'`,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: false,
            })
            updateData()
        }

    } catch (error) {
        console.log(error);
    }

}





function confirmRemove(arr: any[]) {
    Swal.fire({
        title: 'Confirm?',
        text: "Remove selection from group",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#767676',
        confirmButtonText: 'Remove'
    }).then((result) => {
        if (result.isConfirmed) {
            updateMembersGroup(arr)
        }
    })
}


function confirmAdd(arr: any[]) {
    let rec = arr.length == 1 ? 'record' : 'records'
    Swal.fire({
        title: `Add ${arr.length} ${rec}?`,
        text: "NB: will be removed from previous groups",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#03787c',
        cancelButtonColor: '#767676',
        confirmButtonText: 'Continue'
    }).then((result) => {
        if (result.isConfirmed) {
            updateMembersGroup(arr)
        }
    })
}


async function updateMembersGroup(arr: any[]) {
    try {
        await server.updateMembersGroup(JSON.stringify(arr));
        updateData()
    } catch (error) {
        console.log(error);
    }
}


</script>

<style scoped>
.created {
    font-size: 12px;
    margin-left: 10px;
    color: var(--bs-gray-500);
}
</style>