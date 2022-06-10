<template>
    <div class="card">
        <h4 class="px-3 pt-3 fw-bold text-capitalize">
            <div class="d-none d-md-inline">
                <button @click="router.go(-1)" class="btn btn-link text-decoration-none">
                    <i class="bi bi-arrow-left"></i> back
                </button>
            </div>
            <i class="bi bi-folder"></i> {{ group.name }}
        </h4>

        <div class="card-body">
            <div class="row gy-3">
                <tableComp :data="membersList()" />
                <settingComp :name="group.name" :name_bk="group.name" @delete="deleteGroup" @rename="renameGroup" />
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import tableComp from './groupTableComponent.vue'
import settingComp from './groupSettingsComponent.vue'
import { useRoute, useRouter } from 'vue-router'
import { useAdminStore } from '@/store/user/admin'
import server from '@/store/apiStore'
import { onMounted, reactive, ref } from 'vue';
import Swal from 'sweetalert2'
const orgId = useAdminStore().getData.org_id
const route = useRoute()
const router = useRouter()

const group = reactive({
    name: '...',
    id: '',
    members: <any>[]
})

const membersList: any = () => {
    let tableGroup = group.members
    tableGroup.forEach((x: { checked: boolean }) => {
        x.checked = false
    });
    return tableGroup
}


onMounted(() => {
    getGroupDetails()
})

async function getGroupDetails() {
    try {
        var { data } = await server.getGroupDetails(orgId, route.query.g);
        let details = data.details
        let members = data.members
        group.name = details.group_name
        group.id = details.id
        group.members = members
    } catch (error) {
        console.log(error);
    }
}

async function deleteGroup() {
    let hasMembers = group.members.some((x: { group_id: string }) => x.group_id == group.id)
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
                router.push({ name: 'Groups' })
            }
            else console.log(data);

        } catch (error) {
            console.log(error);
        }
    }
}

async function renameGroup(name: string) {
    if (group.name == name) {
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
            getGroupDetails()
        }

    } catch (error) {
        console.log(error);
    }

}

</script>

<style scoped>
</style>