<template>
    <div>
        <div class="card">
            <h5 class="px-3 pt-3 ">
                <span class="d-none d-md-inline ">
                    <button @click="router.go(-1)" class="btn m-0 me-3 fw-bold  btn-link">
                        <i class="bi bi-chevron-left"></i> back
                    </button>
                </span>
                <span class=" me-2">
                    <span class="text-capitalize"><i class="bi bi-folder"></i> {{ group.name }}</span>

                    <small class="created d-none d-md-inline">created {{ group.created }} </small>
                    <small class="created2 ">{{ group.members.length }}
                        {{group.members.length == 1? 'member': 'members'}}
                    </small>
                </span>
                <span v-if="!mStore.groupsLoading && !mStore.internetError" class="float-end">
                    <button @click="groupStore.membersAddQuery()" data-bs-toggle="modal" data-bs-target="#mListModal"
                        class="btn btn-sm customBtn p-1 px-4 m-0">
                        <i class="bi bi-person-plus-fill"></i>
                    </button>
                </span>
                <transition name="xSlide">
                    <span v-if="!mStore.groupsLoading && !mStore.internetError && removeList.length"
                        class="float-end me-2">
                        <button @click.prevent="confirmRemove" class="btn btn-sm btn btn-danger  p-1 px-2 m-0">
                            <i class="bi bi-folder-minus"></i> Remove
                            <span class="badge rounded-pill bg-white text-dark small">
                                {{ removeList.length }}
                            </span>
                        </button>
                    </span>
                </transition>
            </h5>

            <div class="card-body">
                <div v-if="mStore.groupsLoading || mStore.internetError" class="spinner-border spinner-border-sm"
                    role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <div v-else class="row gy-3">
                    <tableComp />
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
const { group, removeList }: any = storeToRefs(groupStore)



const router = useRouter()
onMounted(() => {
    groupStore.$reset
    updateData()
})


async function updateData() {
    mStore.groupsLoading = true
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





function confirmRemove() {
    Swal.fire({
        title: 'Confirm Remove?',
        text: "Selection will be removed from this group",
        showCancelButton: true,
        confirmButtonText: 'Yes, Remove',
        confirmButtonColor: '#A93226',
        cancelButtonColor: '#641E16',
        background: `#641E16`,
        color: '#fff',
        width: 'auto',
        reverseButtons: true,

    }).then((result) => {
        if (result.isConfirmed) {
            updateMembersGroup(removeList.value)
        }
    })
}


function confirmAdd(arr: any[]) {
    let rec = arr.length == 1 ? 'record' : 'records'
    Swal.fire({
        title: `Add ${arr.length} ${rec}?`,
        text: "..will be removed from previous groups!",
        showCancelButton: true,
        confirmButtonColor: '#03787c',
        cancelButtonColor: '#012A2B',
        confirmButtonText: 'Yes, Continue',
        background: `#012A2B`,
        color: '#fff',
        width: 'auto',
        reverseButtons: true,
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
    color: var(--bs-teal);
}

.created2 {
    font-size: 12px;
    margin-left: 10px;
    color: var(--bs-gray-500);
}
</style>