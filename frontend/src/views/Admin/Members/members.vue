<template>
    <div>
        <List @delete="deleteMember" @update="loadData" />
    </div>
</template>

<script setup lang="ts">
import server from '@/store/apiStore'
import { ref, inject, onMounted } from 'vue';
import List from './membersTableComponent.vue'
import Swal from 'sweetalert2'
import { useAdminStore } from '@/store/user/admin'
import { membersData } from './members-data';
import { storeToRefs } from 'pinia'
const { cc1, cc2, ccThk, ccBg, ccBtnH }: any = inject("c$");
const orgId = useAdminStore().getData.org_id


const openMemberModal = ref(false)

const mStore = membersData()

onMounted(async () => {
    loadData()
})

function loadData() {
    mStore.getMembers(orgId)
    mStore.getGroupNames(orgId)
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