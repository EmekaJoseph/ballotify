<template>
    <div class="card">
        <h4 class="px-3 pt-3 fw-bold text-capitalize">
            <div class="d-none d-md-inline">
                <button @click="router.go(-1)" class="btn btn-link text-info text-decoration-none">
                    <i class="bi bi-arrow-left"></i> back
                </button>
            </div>
            <i class="bi bi-folder"></i> {{ group.name }} group
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
            title: '',
            text: "Group not empty",
            icon: 'error',
            showConfirmButton: false,
            timer: 1500,
            width: 350,
            padding: '3em',
        })
        return false
    }
    else {
        try {
            var { data } = await server.deleteGroup(orgId, group.id);
            if (data == 1) {
                Swal.fire({
                    title: '',
                    text: `'${group.name}' deleted`,
                    icon: 'success',
                    showConfirmButton: false,
                    timer: 1000,
                    width: 350,
                    padding: '3em',
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
                title: '',
                text: `${group.name} changed to '${name}'`,
                icon: 'success',
                showConfirmButton: false,
                timer: 1200,
                width: 350,
                padding: '3em',
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