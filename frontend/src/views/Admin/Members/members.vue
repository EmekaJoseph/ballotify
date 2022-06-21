<template>
    <div>
        <div v-if="mStore.internetError" class="alert alert-danger py-2 border-0" role="alert">
            <i class=" bi bi-wifi-off"></i> <b>App not connected, </b> please check your internet and refresh.
        </div>
        <List @delete="deleteMember" @update="loadData" />
    </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import server from '@/store/apiStore'
import List from './membersTableComponent.vue'
import Swal from 'sweetalert2'
import { useAdminStore } from '@/store/user/admin'
import { dataStore } from '@/store/dataStore';
const orgId = useAdminStore().getData.org_id

onMounted(() => {
    loadData()
})

const mStore = dataStore()

async function loadData() {
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

<style scoped>
</style>