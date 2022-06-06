<template>
    <div class="row">
        <List @openModal="openMemberModal = true" :members="memberList" :groups="groups" @delete="conFirmDelete" />
        <newMemberModal v-if="openMemberModal" @closeModal="openMemberModal = false" @saved="re_getMembers" />
    </div>
</template>

<script setup lang="ts">
import server from '@/store/apiStore'
import { ref, inject, onMounted } from 'vue';
import List from './membersTableComponent.vue'
import newMemberModal from './newMemberModalComponent.vue'
import Swal from 'sweetalert2'
import { useAdminStore } from '@/store/user/admin'
const { cc1, cc2, ccThk, ccBg, ccBtnH }: any = inject("c$");
const orgId = useAdminStore().getData.org_id


const openMemberModal = ref(false)
const memberList = ref<any>([])
const groups = ref<any>([])


onMounted(() => {
    getMembers()
})

async function getMembers() {
    try {
        var { data } = await server.getMembers(orgId)
        if (data) {
            memberList.value = data.data
            memberList.value.forEach((x: { checked: boolean }) => {
                x.checked = false
            })
            await getGroupNames()
        }
    } catch (error) {
        console.log(error);
    }
}


async function getGroupNames() {
    try {
        var { data } = await server.getGroupNames(orgId)
        if (data) {
            let grp = data.groups;
            groups.value = grp.map((x: { id: string; group_name: string; }) => ({ id: x.id, name: x.group_name }))
        }
    } catch (error) {
        console.log(error);
    }
}


async function re_getMembers() {
    await getMembers()
    Swal.fire({
        title: 'Added Successfully',
        text: "Do you want to add AGAIN?",
        icon: 'success',
        showCancelButton: true,
        confirmButtonColor: '#03787c',
        cancelButtonColor: '#767676',
        confirmButtonText: 'Continue adding'
    }).then((result) => {
        if (result.isConfirmed) {
            openMemberModal.value = true
        }
    })
}

function conFirmDelete(id: string) {
    Swal.fire({
        title: 'Confirm delete?',
        text: "entire record will be deleted!",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#767676',
        confirmButtonText: 'Confirm delete'
    }).then((result) => {
        if (result.isConfirmed) {
            deleteMember(id)

        }
    })
}

async function deleteMember(id: string) {
    try {
        var { data } = await server.deleteMember(orgId, id)
        if (data == 1) {
            Swal.fire({
                title: '',
                text: `Deleted successfully`,
                icon: 'success',
                showConfirmButton: false,
                timer: 1200,
                width: 350,
                padding: '3em',
            })
            getMembers()
        }
    } catch (error) {
        console.log(error);
    }
}

</script>

<style scoped>
</style>