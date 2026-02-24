<template>
    <div>
        <List @delete="deleteMember" @update="updateData" />
    </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue';
import server from '@/store/apiStore'
import List from './membersTableComponent.vue'
import Swal from 'sweetalert2'
import { dataStore } from '@/store/admin/dataStore';

onMounted(() => {
    mStore.getMembers()
})

const mStore = dataStore()

async function updateData() {
    await mStore.getMembers()
    await mStore.getGroupNames()
}


async function deleteMember(id: string) {
    try {
        var { data } = await server.deleteMember(id)
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
            mStore.getMembers()
        }
    } catch (error) {
        console.log(error);
    }
}


</script>
