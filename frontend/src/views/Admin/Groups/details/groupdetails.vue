<template>
    <div>
        <div v-if="mStore.internetError" class="alert alert-danger py-2 border-0" role="alert">
            <i class=" bi bi-wifi-off"></i> <b>App not connected, </b> please check your internet and refresh.
        </div>
        <div class="card">
            <h5 class="px-3 pt-3 text-capitalize">
                <div class="d-none d-md-inline me-5 float-end">
                    <button @click="router.go(-1)" class="btn p-1 px-3 btn-sm m-0 fw-bold ">
                        <i class="bi bi-arrow-left"></i> Go back
                    </button>
                </div>
                <i class="bi bi-folder"></i> {{ group.name }}

            </h5>

            <div class="card-body">
                <div class="row gy-3">
                    <tableComp :data="membersList()" @remove="confirmRemove" />
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
import { useRoute, useRouter } from 'vue-router'
import { useAdminStore } from '@/store/user/admin'
import server from '@/store/apiStore'
import { reactive, onMounted, ref } from 'vue';
import Swal from 'sweetalert2'
import { dataStore } from '@/store/dataStore';
import { storeToRefs } from 'pinia'


const mStore = dataStore()
const { members, groups }: any = storeToRefs(mStore)

const orgId = useAdminStore().getData.org_id
const route = useRoute()
const router = useRouter()
const query = ref<any>(route.query.g)

let thisGroup = groups.value.find((x: { id: any }) => x.id == query.value)
const group = reactive({
    name: thisGroup == undefined ? '' : thisGroup.name,
    id: query.value,
    members: members.value.filter((x: { group_id: any }) => x.group_id == query.value)
})

onMounted(async () => {
    mStore.currentGroup = query.value
    if (thisGroup == undefined) {
        updateData()
    }

})

async function updateData() {
    await mStore.getMembers(orgId)
    await mStore.getGroupNames(orgId)

    let grp = groups.value.find((x: { id: any }) => x.id == query.value)
    group.name = (group == undefined) ? '' : grp.name
    group.members = members.value.filter((x: { group_id: any }) => x.group_id == query.value)
}


const membersList: any = () => {
    let tableGroup = group.members
    tableGroup.forEach((x: { checked: boolean }) => {
        x.checked = false
    });
    return tableGroup
}


async function deleteGroup() {
    let hasMembers = members.value.some((x: { group_id: string }) => x.group_id == group.id)
    if (hasMembers) {
        Swal.fire({
            toast: true,
            icon: 'error',
            title: 'Group not empty',
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: false,
        })
        return false
    }
    else {
        try {
            var { data } = await server.deleteGroup(orgId, group.id);
            if (data == 1) {
                Swal.fire({
                    toast: true,
                    icon: 'success',
                    title: `'${group.name}' deleted`,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: false,
                })
                // unwatch()
                await mStore.getGroupNames(orgId)
                router.push({ name: 'Groups' })
            }
            else console.log(data);

        } catch (error) {
            console.log(error);
        }
    }
}

async function renameGroup(name: string) {
    if (group.name.toLowerCase() == name.toLowerCase()) {
        return
    }
    let obj = {
        id: group.id,
        org_id: orgId,
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
            mStore.getGroupNames(orgId)
            group.name = name
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
</style>