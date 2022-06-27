<template>
    <div>
        <List @delete="deleteMember" @update="updateData" />
    </div>
</template>

<script setup lang="ts">
import server from '@/store/apiStore'
import List from './membersTableComponent.vue'
import Swal from 'sweetalert2'
import { adminAccount } from '@/store/admin/account'
import { dataStore } from '@/store/admin/dataStore';
const orgId = adminAccount().getData.org_id

const mStore = dataStore()

async function updateData() {
    await mStore.getMembers(orgId)
    await mStore.getGroupNames(orgId)
}


async function deleteMember(id: string) {
    try {
        var { data } = await server.deleteMember(orgId, id)
        if (data == 1) {
            Swal.fire({
                toast: true,
                icon: 'success',
                title: 'Deleted',
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: false,
            })
            mStore.getMembers(orgId)
        }
    } catch (error) {
        console.log(error);
    }
}


</script>
